<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class CategoryController extends Controller
{
    // Mostrar todas las categorías
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories, 200);
    }

    // Crear una nueva categoría
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
        ]);

        $category = Category::create([
            'name' => $request->input('name'),
        ]);

        return response()->json($category, 201);
    }

    // Mostrar una categoría específica
    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category, 200);
    }

    // Actualizar una categoría existente
    public function update(Request $request, $categoryID)
    {
        try {
            $category = Category::findOrFail($categoryID);

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                // ...other fields...
            ]);

            $category->fill($validated);

            if ($category->isDirty()) {
                $category->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente.',
                'data' => $category
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La categoría no fue encontrada.'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error inesperado, intente nuevamente.'
            ], 500);
        }
    }

    // Eliminar una categoría
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}
