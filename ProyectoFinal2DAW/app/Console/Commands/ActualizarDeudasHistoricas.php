<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Deuda;
use App\Models\RegistroCobro;
use Illuminate\Support\Facades\DB;

class ActualizarDeudasHistoricas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deudas:actualizar-historicas 
                            {--dry-run : Simular sin realizar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el campo deuda de cobros antiguos basándose en el estado actual de las deudas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 MODO SIMULACIÓN - No se realizarán cambios en la base de datos');
        } else {
            $this->warn('⚠️  Este comando modificará la base de datos');
            if (!$this->confirm('¿Deseas continuar?')) {
                $this->info('Operación cancelada');
                return 0;
            }
        }

        $this->info('Procesando deudas históricas...');
        $this->newLine();

        // Obtener todas las deudas con saldo pendiente = 0 (ya pagadas)
        $deudasPagadas = Deuda::where('saldo_pendiente', 0)
            ->where('saldo_total', '>', 0)
            ->with('movimientos')
            ->get();

        $this->info("📊 Deudas completamente pagadas encontradas: {$deudasPagadas->count()}");
        $cobrosActualizados = 0;

        foreach ($deudasPagadas as $deuda) {
            // Buscar cobros de este cliente que aún tienen deuda > 0
            $cobrosConDeuda = RegistroCobro::where('id_cliente', $deuda->id_cliente)
                ->where('deuda', '>', 0)
                ->get();

            if ($cobrosConDeuda->count() > 0) {
                $this->line("Cliente ID {$deuda->id_cliente}: {$cobrosConDeuda->count()} cobros con deuda pendiente (pero deuda ya pagada)");
                
                foreach ($cobrosConDeuda as $cobro) {
                    $this->line("  → Cobro #{$cobro->id}: {$cobro->deuda}€ → 0€");
                    
                    if (!$dryRun) {
                        $cobro->deuda = 0;
                        $cobro->save();
                    }
                    
                    $cobrosActualizados++;
                }
            }
        }

        $this->newLine();

        // Ahora procesar deudas parcialmente pagadas
        $deudasParciales = Deuda::where('saldo_pendiente', '>', 0)
            ->where('saldo_total', '>', 0)
            ->get();

        $this->info("📊 Deudas parcialmente pagadas: {$deudasParciales->count()}");

        foreach ($deudasParciales as $deuda) {
            $saldoCalculado = $deuda->saldo_pendiente;
            
            // Obtener cobros con deuda de este cliente
            $cobrosConDeuda = RegistroCobro::where('id_cliente', $deuda->id_cliente)
                ->where('deuda', '>', 0)
                ->orderBy('created_at', 'asc')
                ->get();

            $totalDeudaCobros = $cobrosConDeuda->sum('deuda');

            // Si el total de deuda en cobros no coincide con saldo_pendiente, ajustar
            if ($totalDeudaCobros != $saldoCalculado) {
                $this->line("Cliente ID {$deuda->id_cliente}: Ajustando deuda de cobros");
                $this->line("  Saldo pendiente: {$saldoCalculado}€");
                $this->line("  Total en cobros: {$totalDeudaCobros}€");
                
                $montoPorDistribuir = $saldoCalculado;
                
                foreach ($cobrosConDeuda as $cobro) {
                    if ($montoPorDistribuir <= 0) {
                        // Ya no queda deuda, poner en 0
                        $this->line("  → Cobro #{$cobro->id}: {$cobro->deuda}€ → 0€");
                        if (!$dryRun) {
                            $cobro->deuda = 0;
                            $cobro->save();
                        }
                        $cobrosActualizados++;
                    } elseif ($montoPorDistribuir < $cobro->deuda) {
                        // Deuda parcial en este cobro
                        $this->line("  → Cobro #{$cobro->id}: {$cobro->deuda}€ → {$montoPorDistribuir}€");
                        if (!$dryRun) {
                            $cobro->deuda = $montoPorDistribuir;
                            $cobro->save();
                        }
                        $montoPorDistribuir = 0;
                        $cobrosActualizados++;
                    } else {
                        // Este cobro mantiene toda su deuda
                        $montoPorDistribuir -= $cobro->deuda;
                    }
                }
            }
        }

        $this->newLine();
        
        if ($dryRun) {
            $this->info("✅ Simulación completada: {$cobrosActualizados} cobros serían actualizados");
            $this->info("💡 Ejecuta sin --dry-run para aplicar los cambios");
        } else {
            $this->info("✅ Actualización completada: {$cobrosActualizados} cobros actualizados");
        }

        return 0;
    }
}
