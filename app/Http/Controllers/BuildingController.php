<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class BuildingController extends Controller
{
    // Mostrar todos los edificios
    public function index()
    {
        $buildings = Building::with('rooms')->get(); 
        return response()->json($buildings, 200);
    }

    // Crear un nuevo edificio
    public function store(Request $request)
    {
        $request->validate([
            'buildingID' => 'required|string|max:10|unique:buildings,buildingID',
        ]);

        $building = Building::create($request->all());
        return response()->json($building, 201);
    }

    // Mostrar un edificio específico por ID
    public function show($buildingID)
    {
        $building = Building::with('rooms')->findOrFail($buildingID);
        return response()->json($building, 200);
    }

    // Actualizar un edificio
    public function update(Request $request, $buildingID)
    {
        try {
            $building = Building::findOrFail($buildingID);

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                // ...other fields...
            ]);

            $building->fill($validated);

            if ($building->isDirty()) {
                $building->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Edificio actualizado exitosamente.',
                'data' => $building
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'El edificio no fue encontrado.'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado, intente nuevamente.'
            ], 500);
        }
    }

    // Eliminar un edificio
    public function destroy($buildingID)
    {
        $building = Building::findOrFail($buildingID);
        $building->delete();
        return response()->json(['message' => 'Building deleted successfully'], 200);
    }
}
