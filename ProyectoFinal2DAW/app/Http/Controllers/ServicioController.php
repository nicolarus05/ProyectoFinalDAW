<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Servicio;

class ServicioController extends Controller{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $servicios = Servicio::with('empleado')->get();
        return view('servicios.index', compact('servicios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        return view('servicios.create');
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
        return redirect()->route('servicios.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Servicio $servicio){
        return view('servicios.show', compact('servicio'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Servicio $servicio){
        return view('servicios.edit', compact('servicio'));
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

        Servicio::create($data);
        return redirect()->route('servicios.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Servicio $servicio){
        $servicio->delete();
        return redirect()->route('servicios.index')->with('success', 'El servicio ha sido eliminado con exito.');
    }
}
