<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UsersSeeder;
use Database\Seeders\EmpleadosSeeder;
use Database\Seeders\ClientesSeeder;
use Database\Seeders\ServiciosSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void{
        $this->call(UsersSeeder::class);
        $this->call(EmpleadosSeeder::class);
        $this->call(ClientesSeeder::class);
        $this->call(ServiciosSeeder::class);
    }
}
