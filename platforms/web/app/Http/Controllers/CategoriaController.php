<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Models\Categoria;
use App\Support\BaseCategoryConfig;
use App\Support\CategoryIconCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestiona las peticiones HTTP relacionadas con las categorias.
 *
 * @autor Antonio Martin Leon
 */
class CategoriaController extends Controller
{
    /**
     * Inyecta el repositorio encargado de las categorias.
     */
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    /**
     * Muestra la pantalla de categorias o devuelve su payload en JSON.
     */
    public function index(Request $request): View|JsonResponse
    {
        BaseCategoryConfig::syncToDatabase();
        $payload = $this->categories->getIndexPayload();

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return view('welcome', ['appData' => $payload]);
    }

    /**
     * Crea una nueva categoria con los datos validados de la peticion.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateCategoria($request);

        $categoria = $this->categories->create($validated);

        return response()->json([
            'message' => 'Categoria creada correctamente.',
            'categoria' => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'color' => $categoria->color,
                'icono' => $categoria->icono,
                'gastos_count' => $categoria->gastos_count,
                'is_base' => BaseCategoryConfig::isBaseCategory($categoria),
                'can_delete' => ! BaseCategoryConfig::isBaseCategory($categoria),
            ],
        ]);
    }

    /**
     * Actualiza una categoria existente.
     */
    public function update(Request $request, Categoria $categoria): JsonResponse
    {
        $this->ensureOwned($categoria);
        $validated = $this->validateCategoria($request, $categoria);

        $categoria = $this->categories->update($categoria, $validated);

        return response()->json([
            'message' => 'Categoria actualizada correctamente.',
            'categoria' => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'color' => $categoria->color,
                'icono' => $categoria->icono,
                'gastos_count' => $categoria->gastos_count,
                'is_base' => BaseCategoryConfig::isBaseCategory($categoria),
                'can_delete' => ! BaseCategoryConfig::isBaseCategory($categoria),
            ],
        ]);
    }

    /**
     * Elimina una categoria si no tiene gastos asociados.
     */
    public function destroy(Categoria $categoria): JsonResponse
    {
        $this->ensureOwned($categoria);
        if (BaseCategoryConfig::isBaseCategory($categoria)) {
            return response()->json([
                'message' => 'No se puede borrar una categoria base incluida en el JSON.',
            ], 422);
        }

        try {
            $this->categories->delete($categoria);
        } catch (QueryException) {
            return response()->json([
                'message' => 'No se puede borrar la categoria porque tiene gastos asociados.',
            ], 422);
        }

        return response()->json([
            'message' => 'Categoria eliminada correctamente.',
            'id' => $categoria->id,
        ]);
    }

    /**
     * Valida los datos de entrada necesarios para crear o actualizar categorias.
     *
     * @return array<string, mixed>
     */
    private function validateCategoria(Request $request, ?Categoria $categoria = null): array
    {
        $uniqueRule = Rule::unique('categorias', 'nombre')
            ->where('user_id', $request->user()->id)
            ->ignore($categoria?->id);

        return $request->validate(
            [
                'nombre' => ['required', 'string', 'max:255', $uniqueRule],
                'color' => ['required', 'string', 'max:20'],
                'icono' => ['nullable', 'string', 'max:255', Rule::in(CategoryIconCatalog::names())],
            ],
            [
                'nombre.required' => 'El nombre de la categoría es obligatorio.',
                'nombre.max' => 'El nombre de la categoría no puede superar los 255 caracteres.',
                'nombre.unique' => 'Ya existe una categoría con ese nombre.',
                'color.required' => 'El color de la categoría es obligatorio.',
                'color.max' => 'El color de la categoría no puede superar los 20 caracteres.',
                'icono.string' => 'El icono de la categoría no es válido.',
                'icono.max' => 'El icono de la categoría no es válido.',
                'icono.in' => 'El icono seleccionado no es válido.',
            ]
        );
    }
}
