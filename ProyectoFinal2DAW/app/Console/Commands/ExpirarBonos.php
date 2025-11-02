<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BonoCliente;
use Carbon\Carbon;

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
    protected $description = 'Marca como expirados los bonos que han superado su fecha de expiración';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bonosExpirados = BonoCliente::where('estado', 'activo')
            ->where('fecha_expiracion', '<', Carbon::now())
            ->update(['estado' => 'expirado']);

        $this->info("Se marcaron {$bonosExpirados} bono(s) como expirados.");
        
        return 0;
    }
}
