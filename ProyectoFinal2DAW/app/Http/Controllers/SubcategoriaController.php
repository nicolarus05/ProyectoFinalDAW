<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subcategoria;
use App\Services\CacheService;

class SubcategoriaController extends Controller
{
    public function index()
    {
        $subcategorias = Subcategoria::orderBy('categoria')->orderBy('nombre')->get();
        return view('subcategorias.index', compact('subcategorias'));
    }

    public function create()
    {
        return view('subcategorias.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:100',
            'categoria' => 'required|in:peluqueria,estetica',
            'color'     => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'activo'    => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);

        Subcategoria::create($data);
        CacheService::clearServiciosCache();

        return redirect()->route('subcategorias.index')
                         ->with('success', 'Subcategoría creada correctamente.');
    }

    public function edit(Subcategoria $subcategoria)
    {
        return view('subcategorias.edit', compact('subcategoria'));
    }

    public function update(Request $request, Subcategoria $subcategoria)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:100',
            'categoria' => 'required|in:peluqueria,estetica',
            'color'     => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'activo'    => 'boolean',
        ]);

        $data['activo'] = $request->boolean('activo', true);

        $subcategoria->update($data);
        CacheService::clearServiciosCache();

        return redirect()->route('subcategorias.index')
                         ->with('success', 'Subcategoría actualizada correctamente.');
    }

    public function destroy(Subcategoria $subcategoria)
    {
        // Desasociar servicios antes de eliminar
        $subcategoria->servicios()->update(['subcategoria_id' => null]);
        $subcategoria->delete();
        CacheService::clearServiciosCache();

        return redirect()->route('subcategorias.index')
                         ->with('success', 'Subcategoría eliminada correctamente.');
    }
}
