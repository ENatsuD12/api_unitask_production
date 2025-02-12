<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Building;
use App\Models\Room;
use App\Models\Category;
use App\Models\Good;
use App\Models\User;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use App\Models\Diagnostic;
use App\Models\Material;
use Illuminate\Support\Str;

class MovilController extends Controller
{
    //

    public function getAllReports()
    {
        $reports = DB::table('reports')
            ->join('buildings', 'reports.buildingID', '=', 'buildings.buildingID')
            ->join('rooms', 'reports.roomID', '=', 'rooms.roomID')
            ->join('types', 'rooms.typeID', '=', 'types.typeID')
            ->join('categories', 'reports.categoryID', '=', 'categories.categoryID')
            ->join('goods', 'reports.goodID', '=', 'goods.goodID')
            ->join('users', 'reports.userID', '=', 'users.userID')
            ->join('statuses', 'reports.statusID', '=', 'statuses.statusID')
            ->select(
                'reports.folio',
                'reports.buildingID',
                'buildings.name as building_name',
                'reports.roomID',
                'rooms.name as room_name',
                'rooms.key as room_key',
                'rooms.typeID',
                'types.name as type_name',
                'reports.categoryID',
                'categories.name as category_name',
                'reports.goodID',
                'goods.name as good_name',
                'reports.priority',
                'reports.description',
                'reports.image',
                'reports.userID',
                'users.name as user_name',
                'users.lastname as user_lastname',
                'reports.statusID',
                'statuses.name as status_name',
                'reports.requires_approval',
                'reports.involve_third_parties',
                'reports.created_at'
            )
            ->get();

        return response()->json($reports);
    }

    public function getReportByFolio(Request $request)
    {
        $validatedData = $request->validate([
            'folio' => 'required|string',
        ]);

        $folio = $validatedData['folio'];

        $report = DB::table('reports')
            ->join('buildings', 'reports.buildingID', '=', 'buildings.buildingID')
            ->join('rooms', 'reports.roomID', '=', 'rooms.roomID')
            ->join('types', 'rooms.typeID', '=', 'types.typeID')
            ->join('categories', 'reports.categoryID', '=', 'categories.categoryID')
            ->join('goods', 'reports.goodID', '=', 'goods.goodID')
            ->join('users', 'reports.userID', '=', 'users.userID')
            ->join('statuses', 'reports.statusID', '=', 'statuses.statusID')
            ->select(
                'reports.folio',
                'reports.buildingID',
                'buildings.name as building_name',
                'reports.roomID',
                'rooms.name as room_name',
                'rooms.key as room_key',
                'rooms.typeID',
                'types.name as type_name',
                'reports.categoryID',
                'categories.name as category_name',
                'reports.goodID',
                'goods.name as good_name',
                'reports.priority',
                'reports.description',
                'reports.image',
                'reports.userID',
                'users.name as user_name',
                'users.lastname as user_lastname',
                'reports.statusID',
                'statuses.name as status_name',
                'reports.requires_approval',
                'reports.involve_third_parties',
                'reports.created_at'
            )
            ->where('reports.folio', $folio)
            ->first();

        if ($report) {
            return response()->json($report);
        } else {
            return response()->json(['message' => 'Report not found'], 404);
        }
    }

    public function getAllDiagnostics()
    {
        $diagnostics = DB::table('diagnostics')
            ->join('reports', 'diagnostics.reportID', '=', 'reports.reportID')
            ->select(
                'diagnostics.diagnosticID',
                'diagnostics.reportID',
                'reports.folio as report_folio',
                'diagnostics.description',
                'diagnostics.images',
                'diagnostics.status',
                'diagnostics.created_at'
            )
            ->get();

        return response()->json($diagnostics);
    }

    public function getAllDiagnosticsStatus(Request $request)
    {
        $status = $request->status;

        $diagnostics = DB::table('diagnostics')
            ->join('reports', 'diagnostics.reportID', '=', 'reports.reportID')
            ->where('diagnostics.status', $status)
            ->select(
                'diagnostics.diagnosticID',
                'diagnostics.reportID',
                'reports.folio as report_folio',
                'diagnostics.description',
                'diagnostics.images',
                'diagnostics.status',
                'diagnostics.created_at'
            )
            ->get();

        return response()->json($diagnostics);
    }

    public function postDiagnostic(Request $request)
    {
        $validatedData = $request->validate([
            'reportID' => 'required|integer',
            'description' => 'required|string',
            'images' => 'nullable|string',
            'status' => 'required|string',
            'materials' => 'nullable|array',
            'materials.*.name' => 'required_with:materials|string',
            'materials.*.supplier' => 'required_with:materials|string',
            'materials.*.quantity' => 'required_with:materials|integer',
            'materials.*.price' => 'required_with:materials|numeric',
        ]);

        $diagnosticID = DB::table('diagnostics')->insertGetId([
            'reportID' => $validatedData['reportID'],
            'description' => $validatedData['description'],
            'images' => $validatedData['images'],
            'status' => $validatedData['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (isset($validatedData['materials'])) {
            $materials = [];
            foreach ($validatedData['materials'] as $materialData) {
                $materials[] = [
                    'name' => $materialData['name'],
                    'supplier' => $materialData['supplier'],
                    'quantity' => $materialData['quantity'],
                    'price' => $materialData['price'],
                    'diagnosticID' => $diagnosticID,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('materials')->insert($materials);
        }

        return response()->json(['diagnosticID' => $diagnosticID], 201);
    }

    public function updateDiagnosticStatus(Request $request)
    {
        $validatedData = $request->validate([
            'diagnosticID' => 'required|integer',
            'status' => 'required|string',
        ]);

        $diagnostic = Diagnostic::findOrFail($validatedData['diagnosticID']);
        $diagnostic->status = $validatedData['status'];
        $diagnostic->save();

        return response()->json($diagnostic);
    }

    public function postMaterials(Request $request)
    {
        $validatedData = $request->validate([
            'materials' => 'required|array',
            'materials.*.name' => 'required|string',
            'materials.*.supplier' => 'required|string',
            'materials.*.quantity' => 'required|integer',
            'materials.*.price' => 'required|numeric',
            'materials.*.diagnosticID' => 'required|integer',
        ]);

        $materials = [];
        foreach ($validatedData['materials'] as $materialData) {
            $materials[] = [
                'name' => $materialData['name'],
                'supplier' => $materialData['supplier'],
                'quantity' => $materialData['quantity'],
                'price' => $materialData['price'],
                'diagnosticID' => $materialData['diagnosticID'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('materials')->insert($materials);

        return response()->json(['message' => 'Materials added successfully'], 201);
    }

    public function getMaterialsByDiagnostic($diagnosticID)
    {
        $materials = DB::table('materials')
            ->where('diagnosticID', $diagnosticID)
            ->select('name', 'supplier', 'quantity', 'price')
            ->get();

        return response()->json($materials);
    }

    public function postReports(Request $request)
    {
        $request->validate([
            'buildingID' => 'required|string|max:10|exists:buildings,buildingID',
            'roomID' => 'required|string|max:10|exists:rooms,roomID',
            'categoryID' => 'required|integer|exists:categories,categoryID',
            'goodID' => 'required|integer|exists:goods,goodID', 
            'priority' => 'required|in:Immediate,Normal',
            'description' => 'required|string',
            'image' => 'nullable|string',
            'userID' => 'required|integer|exists:users,userID',
            'status' => 'required|in:Pending,In Progress,Completed',
        ]);

        // Generar un folio único si no se proporciona
        $folio = $request->input('folio') ?? $this->generateUniqueFolio();

        $reportData = $request->all();
        $reportData['folio'] = $folio;

        $report = Report::create($reportData);

        return response()->json($report, 201);
    }

    // Método para generar un folio único
    private function generateUniqueFolio()
    {
        do {
            $folio = 'REP' . Str::upper(Str::random(7));
        } while (Report::where('folio', $folio)->exists());

        return $folio;
    }
}
