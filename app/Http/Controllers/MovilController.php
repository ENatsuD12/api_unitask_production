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

class MovilController extends Controller
{
    // Obtener todos los reportes con statusID 1
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
            ->where('reports.statusID', 1)
            ->get();

        return response()->json($reports);
    }

    // Obtener un reporte por folio con statusID 1
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
            ->where('reports.statusID', 1)
            ->first();

        if ($report) {
            return response()->json($report);
        } else {
            return response()->json(['message' => 'Report not found'], 404);
        }
    }

    // Cambiar el estado de un reporte
    // Espera recibir: { "reportID": 1, "status": "diagnosticando" }
    public function changeReportStatus(Request $request)
    {
        $validatedData = $request->validate([
            'reportID' => 'required|integer|exists:reports,reportID',
            'status' => 'required|string',
        ]);

        $report = Report::findOrFail($validatedData['reportID']);

        if ($validatedData['status'] === 'diagnosticando') {
            $report->statusID = 2; // In Progress
        }

        $report->save();

        return response()->json(['message' => 'Report status updated successfully'], 200);
    }

    // Obtener el estado de un reporte
    // Espera recibir: { "reportID": 1 }
    public function  getReportStatus($reportID)
    {
        $report = Report::findOrFail($reportID);
        return response()->json(['statusID' => $report->statusID], 200);
    }

    // Crear un diagnóstico y actualizar el estado del reporte a 4
    // Espera recibir: { "reportID": 1, "description": "Descripción", "images": "base64_encoded_image_string", "status": "Pending" }
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

        // Actualizar el estado del reporte a 4 (Diagnostiqued)
        $report = Report::findOrFail($validatedData['reportID']);
        $report->statusID = 4;
        $report->save();

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

    // Obtener reportes por prioridad
    // Espera recibir: { "priority": "Immediate" }
    public function getReportsByPriority(Request $request)
    {
        $validatedData = $request->validate([
            'priority' => 'required|string|in:Immediate,Normal',
        ]);

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
            ->where('reports.priority', $validatedData['priority'])
            ->get();

        return response()->json($reports);
    }
}
