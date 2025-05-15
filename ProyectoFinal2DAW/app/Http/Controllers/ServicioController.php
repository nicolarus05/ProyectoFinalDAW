<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Servicio;

class ServicioController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $servicios = Servicio::all();
        return view('Servicios.index', compact('servicios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        return view('Servicios.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        $data = $request->validate([
            'nombre' => 'required|string',
            'tiempo_estimado' => 'required|integer',
            'precio' => 'required|numeric',
        ]);

        Servicio::create($data);
        return redirect()->route('Servicios.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Servicio $servicio){
        return view('Servicios.show', compact('servicio'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Servicio $servicio){
        return view('Servicios.edit', compact('servicio'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Servicio $servicio){
        $data = $request->validate([
            'nombre' => 'required|string',
            'tiempo_estimado' => 'required|integer',
            'precio' => 'required|numeric',
        ]);

        $servicio->update($data);
        return redirect()->route('Servicios.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Servicio $servicio){
        $servicio->delete();
        return redirect()->route('Servicios.index')->with('success', 'El servicio ha sido eliminado con exito.');
    }
}
