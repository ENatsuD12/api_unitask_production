<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class TypeController extends Controller
{
    // Obtener todos los tipos
    public function index()
    {
        $types = Type::all();
        return response()->json($types, 200);
    }

    // Crear un nuevo tipo
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $type = Type::create([
            'name' => $request->name,
        ]);

        return response()->json($type, 201);
    }

    // Mostrar un tipo específico
    public function show($typeID)
    {
        $type = Type::findOrFail($typeID);
        return response()->json($type, 200);
    }

    // Actualizar un tipo existente
    public function update(Request $request, $typeID)
    {
        try {
            $type = Type::findOrFail($typeID);

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                // ...other fields...
            ]);

            $type->fill($validated);

            if ($type->isDirty()) {
                $type->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Tipo actualizado exitosamente.',
                'data' => $type
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'El tipo no fue encontrado.'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado, intente nuevamente.'
            ], 500);
        }
    }

    // Eliminar un tipo
    public function destroy($typeID)
    {
        $type = Type::findOrFail($typeID);
        $type->delete();
        return response()->json(['message' => 'Type deleted successfully'], 200);
    }
}
