<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cita;
use App\Models\Empleado;
use App\Models\HorarioTrabajo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RegenerarHorariosCommand extends Command
{
    protected $signature = 'horarios:regenerar
                            {--empleado= : ID del empleado (opcional, si no se especifica se regeneran todos)}
                            {--mes= : Mes a regenerar (1-12, opcional)}
                            {--anio= : Año a regenerar (opcional, default año actual)}
                            {--reemplazar : Sustituye los horarios existentes del periodo seleccionado}';

    protected $description = 'Regenera los horarios de trabajo sin mezclar estructuras incompatibles';

    private function citasQueBloqueanHorarios(
        int $empleadoId,
        string $fechaInicio,
        string $fechaFin
    ): \Illuminate\Support\Collection {
        return Cita::with('servicios')
            ->where('id_empleado', $empleadoId)
            ->whereIn('estado', ['pendiente', 'confirmada', 'completada'])
            ->whereDate('fecha_hora', '>=', $fechaInicio)
            ->whereDate('fecha_hora', '<=', $fechaFin)
            ->get()
            ->groupBy(fn (Cita $cita) => Carbon::parse($cita->fecha_hora)->toDateString());
    }

    public function handle()
    {
        $this->info('Iniciando regeneración de horarios...');

        $empleadoId = $this->option('empleado');
        $mes = $this->option('mes');
        $anio = (int) ($this->option('anio') ?? Carbon::now()->year);
        $reemplazar = (bool) $this->option('reemplazar');

        if ($mes !== null && (!is_numeric($mes) || (int) $mes < 1 || (int) $mes > 12)) {
            $this->error('El mes debe estar entre 1 y 12.');
            return Command::FAILURE;
        }

        $empleados = $empleadoId
            ? Empleado::where('id', $empleadoId)->get()
            : Empleado::all();

        if ($empleados->isEmpty()) {
            $this->error('No se encontraron empleados.');
            return Command::FAILURE;
        }

        $totalCreados = 0;
        $totalOmitidos = 0;
        $totalErrores = 0;
        $meses = $mes !== null ? [(int) $mes] : range(1, 12);

        foreach ($empleados as $empleado) {
            $this->info("Procesando empleado ID: {$empleado->id}");

            foreach ($meses as $mesActual) {
                $fechaInicio = Carbon::create($anio, $mesActual, 1);
                $fechaFin = $fechaInicio->copy()->endOfMonth();

                $hayHorariosExistentes = HorarioTrabajo::where('id_empleado', $empleado->id)
                    ->whereBetween('fecha', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
                    ->exists();

                if ($hayHorariosExistentes && !$reemplazar) {
                    $this->warn(
                        "Se omite {$fechaInicio->format('Y-m')} para el empleado {$empleado->id}: "
                        . 'ya existen horarios. Usa --reemplazar de forma explícita.'
                    );
                    $totalOmitidos++;
                    continue;
                }

                $bloquesPorFecha = [];
                $citasPorFecha = $this->citasQueBloqueanHorarios(
                    $empleado->id,
                    $fechaInicio->toDateString(),
                    $fechaFin->toDateString()
                );
                $fecha = $fechaInicio->copy();
                $configuracionValida = true;

                while ($fecha <= $fechaFin) {
                    if (in_array($fecha->dayOfWeek, HorarioTrabajo::DIAS_LABORABLES, true)) {
                        $horarioDia = $empleado->obtenerHorario($fecha)
                            ?: HorarioTrabajo::obtenerHorarioPorFecha($fecha);

                        if ($horarioDia) {
                            $error = HorarioTrabajo::validarRangoHorario(
                                $horarioDia['inicio'] ?? null,
                                $horarioDia['fin'] ?? null
                            );

                            if ($error !== null) {
                                $this->error(
                                    "Configuración inválida para el empleado {$empleado->id} "
                                    . "el {$fecha->toDateString()}: {$error}"
                                );
                                $configuracionValida = false;
                                break;
                            }

                            $bloquesPorFecha[$fecha->toDateString()] = [
                                'inicio' => $horarioDia['inicio'],
                                'fin' => $horarioDia['fin'],
                                'bloques' => HorarioTrabajo::generarBloquesHorarios(
                                    $horarioDia['inicio'],
                                    $horarioDia['fin']
                                ),
                            ];
                        }
                    }

                    $fecha->addDay();
                }

                if (!$configuracionValida) {
                    $totalErrores++;
                    continue;
                }

                DB::transaction(function () use (
                    $empleado,
                    $fechaInicio,
                    $fechaFin,
                    $bloquesPorFecha,
                    $citasPorFecha,
                    $reemplazar,
                    &$totalCreados
                ) {
                    if ($reemplazar) {
                        HorarioTrabajo::where('id_empleado', $empleado->id)
                            ->whereBetween('fecha', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
                            ->delete();
                    }

                    foreach ($bloquesPorFecha as $fecha => $datos) {
                        $citasDelDia = $citasPorFecha->get($fecha, collect());

                        foreach ($datos['bloques'] as $hora) {
                            HorarioTrabajo::create([
                                'id_empleado' => $empleado->id,
                                'fecha' => $fecha,
                                'hora' => $hora,
                                'disponible' => !HorarioTrabajo::bloqueOcupadoPorCitas(
                                    $citasDelDia,
                                    $fecha,
                                    $hora
                                ),
                            ]);
                            $totalCreados++;
                        }

                        HorarioTrabajo::create([
                            'id_empleado' => $empleado->id,
                            'fecha' => $fecha,
                            'hora_inicio' => $datos['inicio'],
                            'hora_fin' => $datos['fin'],
                            'disponible' => true,
                        ]);
                    }
                });
            }
        }

        $this->info('Proceso completado.');
        $this->info("Total registros creados: {$totalCreados}");
        $this->info("Periodos omitidos: {$totalOmitidos}");
        $this->info("Periodos con errores: {$totalErrores}");

        return $totalErrores > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
