<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HorarioTrabajo;
use App\Models\Empleado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RegenerarHorariosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horarios:regenerar 
                            {--empleado= : ID del empleado (opcional, si no se especifica se regeneran todos)}
                            {--mes= : Mes a regenerar (1-12, opcional)}
                            {--anio= : Año a regenerar (opcional, default año actual)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera los horarios de trabajo rellenando hora_inicio y hora_fin correctamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando regeneración de horarios...');

        $empleadoId = $this->option('empleado');
        $mes = $this->option('mes');
        $anio = $this->option('anio') ?? Carbon::now()->year;

        // Obtener empleados a procesar
        $empleados = $empleadoId 
            ? Empleado::where('id', $empleadoId)->get() 
            : Empleado::all();

        if ($empleados->isEmpty()) {
            $this->error('No se encontraron empleados.');
            return Command::FAILURE;
        }

        $totalActualizados = 0;
        $totalCreados = 0;

            foreach ($empleados as $empleado) {
            $this->info("Procesando empleado ID: {$empleado->id}");

            // Si se especifica mes, solo ese mes, sino todo el año
            $meses = $mes ? [$mes] : range(1, 12);

            foreach ($meses as $mesActual) {
                $fechaInicio = Carbon::create($anio, $mesActual, 1);
                $fechaFin = $fechaInicio->copy()->endOfMonth();

                $fecha = $fechaInicio->copy();
                
                while ($fecha <= $fechaFin) {
                    // Solo días laborables (lunes a sábado)
                    if (in_array($fecha->dayOfWeek, HorarioTrabajo::DIAS_LABORABLES)) {
                        
                        // Obtener horario específico para este día
                        $horarioDia = HorarioTrabajo::obtenerHorarioPorFecha($fecha);
                        
                        if (!$horarioDia) {
                            // Día no laborable, saltar
                            $fecha->addDay();
                            continue;
                        }
                        
                        // Generar bloques horarios de 15 minutos
                        $bloques = HorarioTrabajo::generarBloquesHorarios(
                            $horarioDia['inicio'],
                            $horarioDia['fin']
                        );

                        foreach ($bloques as $hora) {
                            // Verificar si ya existe este bloque
                            $horarioExistente = HorarioTrabajo::where('id_empleado', $empleado->id)
                                ->where('fecha', $fecha->format('Y-m-d'))
                                ->where('hora', $hora)
                                ->first();

                            if (!$horarioExistente) {
                                // Crear registro de bloque individual
                                HorarioTrabajo::create([
                                    'id_empleado' => $empleado->id,
                                    'fecha' => $fecha->format('Y-m-d'),
                                    'hora' => $hora,
                                    'disponible' => true,
                                ]);
                                $totalCreados++;
                            }
                        }

                        // También crear/actualizar un registro general con hora_inicio y hora_fin para el calendario de citas
                        $horarioGeneral = HorarioTrabajo::where('id_empleado', $empleado->id)
                            ->where('fecha', $fecha->format('Y-m-d'))
                            ->whereNull('hora')
                            ->first();

                        if (!$horarioGeneral) {
                            HorarioTrabajo::create([
                                'id_empleado' => $empleado->id,
                                'fecha' => $fecha->format('Y-m-d'),
                                'hora_inicio' => $horarioDia['inicio'],
                                'hora_fin' => $horarioDia['fin'],
                                'disponible' => true,
                            ]);
                        }
                    }
                    $fecha->addDay();
                }
            }
        }        $this->info("Proceso completado!");
        $this->info("Total registros creados: {$totalCreados}");
        $this->info("Total registros actualizados: {$totalActualizados}");

        return Command::SUCCESS;
    }
}
