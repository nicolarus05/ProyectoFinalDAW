<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Servicio;
use App\Http\Resources\ServicioResource;
use App\Traits\HasFlashMessages;
use App\Traits\HasCrudMessages;
use App\Traits\HasJsonResponses;
use App\Services\CacheService;

class ServicioController extends Controller{
    use HasFlashMessages, HasCrudMessages, HasJsonResponses;

    protected function getResourceName(): string
    {
        return 'servicio';
    }
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $servicios = Servicio::where('activo', true)->get();
        return view('servicios.index', compact('servicios'));
    }

    /**
     * Exportar listado de servicios a CSV
     */
    public function exportar()
    {
        $servicios = Servicio::orderBy('categoria')->orderBy('nombre')->get();
        $filename = 'servicios_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($servicios) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($handle, ['Nombre', 'Categoria', 'Precio (€)', 'Duracion (min)', 'Activo'], ';');
            foreach ($servicios as $s) {
                fputcsv($handle, [
                    $s->nombre,
                    ucfirst($s->categoria),
                    number_format($s->precio, 2, ',', '.'),
                    $s->tiempo_estimado,
                    $s->activo ? 'Sí' : 'No',
                ], ';');
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
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
            'nombre' => 'required|string|max:100',
            'tiempo_estimado' => 'required|integer|min:1',
            'precio' => 'required|numeric|min:0',
            'categoria' => 'required|in:peluqueria,estetica',
            'activo' => 'boolean'
        ]);


        Servicio::create($data);
        
        // Limpiar caché de servicios
        CacheService::clearServiciosCache();
        
        return redirect()->route('servicios.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Servicio $servicio){
        $servicio->load(['empleados.user']);
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
            'nombre' => 'required|string|max:100',
            'tiempo_estimado' => 'required|integer|min:1',
            'precio' => 'required|numeric|min:0',
            'categoria' => 'required|in:peluqueria,estetica',
            'activo' => 'boolean'
        ]);


        $servicio->update($data);
        
        // Limpiar caché de servicios
        CacheService::clearServiciosCache();
        
        return redirect()->route('servicios.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Servicio $servicio){
        $servicio->delete();
        
        // Limpiar caché de servicios
        CacheService::clearServiciosCache();
        
        return $this->redirectWithSuccess('servicios.index', $this->getDeletedMessage());
    }

    /**
     * Mostrar empleados asignados a un servicio
     */
    public function empleados(Servicio $servicio){
        $servicio->load(['empleados.user']);
        $empleadosAsignados = $servicio->empleados->pluck('id')->toArray();
        $empleadosDisponibles = \App\Models\Empleado::with('user')
            ->whereNotIn('id', $empleadosAsignados)
            ->get();
        
        return view('servicios.empleados', compact('servicio', 'empleadosDisponibles'));
    }

    /**
     * Asignar empleado a un servicio (sin restricción de categoría)
     */
    public function addEmpleado(Request $request, Servicio $servicio){
        $request->validate([
            'id_empleado' => 'required|exists:empleados,id'
        ]);

        // Verificar si ya está asignado
        if ($servicio->empleados()->where('id_empleado', $request->id_empleado)->exists()) {
            return $this->redirectWithWarning(
                "servicios.empleados",
                'Este empleado ya está asignado al servicio.',
                ['servicio' => $servicio->id]
            );
        }

        // Asignar empleado al servicio (sin restricción de categoría)
        $servicio->empleados()->attach($request->id_empleado);

        $empleado = \App\Models\Empleado::find($request->id_empleado);
        
        return $this->redirectWithSuccess(
            "servicios.empleados",
            "Empleado {$empleado->user->nombre} {$empleado->user->apellidos} asignado correctamente al servicio.",
            ['servicio' => $servicio->id]
        );
    }

    /**
     * Remover empleado de un servicio
     */
    public function removeEmpleado(Servicio $servicio, $empleadoId){
        $empleado = \App\Models\Empleado::findOrFail($empleadoId);
        
        $servicio->empleados()->detach($empleadoId);

        return $this->redirectWithSuccess(
            "servicios.empleados",
            "Empleado {$empleado->user->nombre} {$empleado->user->apellidos} removido del servicio.",
            ['servicio' => $servicio->id]
        );
    }
}
