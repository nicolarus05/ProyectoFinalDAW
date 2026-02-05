<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BonoCliente;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExpirarBonos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonos:expirar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina automáticamente los bonos que han superado su fecha de expiración, sin importar cuántos usos les queden';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Buscar todos los bonos activos que han expirado
        $bonosExpirados = BonoCliente::where('estado', 'activo')
            ->where('fecha_expiracion', '<', Carbon::now())
            ->get();

        $cantidad = $bonosExpirados->count();

        if ($cantidad === 0) {
            $this->info('No hay bonos expirados para eliminar.');
            Log::info('Tarea bonos:expirar ejecutada - No hay bonos expirados');
            return 0;
        }

        // Registrar información antes de eliminar
        foreach ($bonosExpirados as $bono) {
            Log::info('Bono expirado eliminado', [
                'bono_id' => $bono->id,
                'cliente_id' => $bono->cliente_id,
                'plantilla' => $bono->plantilla->nombre ?? 'Desconocida',
                'fecha_expiracion' => $bono->fecha_expiracion->format('Y-m-d'),
                'servicios_restantes' => $bono->servicios->sum(function($servicio) {
                    return $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
                })
            ]);
        }

        // Eliminar los bonos expirados (cascade eliminará automáticamente las relaciones)
        $bonosExpirados->each(function($bono) {
            $bono->delete();
        });

        $this->info("Se eliminaron {$cantidad} bono(s) expirado(s).");
        Log::info("Se eliminaron {$cantidad} bono(s) expirado(s)");
        
        return 0;
    }
}
