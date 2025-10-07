<?php

namespace App\Http\Controllers;

use App\Models\Productos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductosController extends Controller{

    /**
     * Lista paginada de productos (admin).
     */
    public function index(Request $request)
    {
        $query = Productos::query();

        if ($q = $request->input('q')) {
            $query->where('nombre', 'LIKE', "%{$q}%");
        }

        $productos = $query->orderBy('nombre')->paginate(20)->withQueryString();

        return view('productos.index', compact('productos', 'q'));
    }

    /**
     * Formulario creación
     */
    public function create()
    {
        return view('productos.create');
    }

    /**
     * Guardar nuevo producto (sin transacciones).
     */
    public function store(Request $request)
    {
        $data = $request->only(['nombre', 'descripcion', 'precio_venta', 'precio_coste', 'stock', 'activo']);

        $validator = Validator::make($data, [
            'nombre' => 'required|string|max:255|unique:productos,nombre',
            'descripcion' => 'nullable|string',
            'precio_venta' => 'required|numeric|min:0',
            'precio_coste' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            Productos::create([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'precio_venta' => $data['precio_venta'],
                'precio_coste' => $data['precio_coste'] ?? 0,
                'stock' => $data['stock'],
                'activo' => isset($data['activo']) ? (bool)$data['activo'] : true,
            ]);

            return redirect()->route('productos.index')->with('success', 'Producto creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error creando producto: '.$e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Ocurrió un error al crear el producto.'])->withInput();
        }
    }

    /**
     * Mostrar (opcional)
     */
    public function show(Productos $producto)
    {
        return view('productos.show', compact('producto'));
    }

    /**
     * Formulario edición
     */
    public function edit(Productos $producto)
    {
        return view('productos.edit', compact('producto'));
    }

    /**
     * Actualizar producto (sin transacciones).
     */
    public function update(Request $request, Productos $producto)
    {
        $data = $request->only(['nombre', 'descripcion', 'precio_venta', 'precio_coste', 'stock', 'activo']);

        $validator = Validator::make($data, [
            'nombre' => 'required|string|max:255|unique:productos,nombre,' . $producto->id,
            'descripcion' => 'nullable|string',
            'precio_venta' => 'required|numeric|min:0',
            'precio_coste' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $producto->update([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'precio_venta' => $data['precio_venta'],
                'precio_coste' => $data['precio_coste'] ?? $producto->precio_coste,
                'stock' => $data['stock'],
                'activo' => isset($data['activo']) ? (bool)$data['activo'] : $producto->activo,
            ]);

            return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error actualizando producto ID '.$producto->id.': '.$e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Ocurrió un error al actualizar el producto.'])->withInput();
        }
    }

    /**
     * Eliminar producto.
     */
    public function destroy(Productos $producto)
    {
        try {
            $producto->delete();
            return redirect()->route('productos.index')->with('success', 'Producto eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error eliminando producto ID '.$producto->id.': '.$e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Ocurrió un error al eliminar el producto.']);
        }
    }

    /**
     * Endpoint JSON para modal (productos activos).
     */
    public function available()
    {
        $productos = Productos::where('activo', true)
            ->select('id', 'nombre', 'precio_venta', 'stock')
            ->orderBy('nombre')
            ->get();

        return response()->json($productos);
    }
}
