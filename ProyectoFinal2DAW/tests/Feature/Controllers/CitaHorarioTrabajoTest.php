<?php

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\HorarioTrabajo;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function crearBloquesHorario(Empleado $empleado, string $fecha, string $inicio, string $fin): void
{
    foreach (HorarioTrabajo::generarBloquesHorarios($inicio, $fin) as $hora) {
        HorarioTrabajo::create([
            'id_empleado' => $empleado->id,
            'fecha' => $fecha,
            'hora' => $hora,
            'disponible' => true,
        ]);
    }
}

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('construye la agenda con los bloques reales generados en verano', function () {
    $empleado = Empleado::factory()->peluqueria()->create();

    crearBloquesHorario($empleado, '2026-07-08', '08:30', '14:00'); // miercoles
    crearBloquesHorario($empleado, '2026-07-09', '08:30', '19:00'); // jueves

    $miercoles = $this->get(route('citas.index', ['fecha' => '2026-07-08']));
    $jueves = $this->get(route('citas.index', ['fecha' => '2026-07-09']));

    $miercoles->assertOk();
    $jueves->assertOk();

    expect($miercoles->viewData('horariosArray'))
        ->toContain('14:00:00')
        ->not->toContain('18:45:00');

    expect($jueves->viewData('horariosArray'))
        ->toContain('18:45:00')
        ->toContain('19:00:00');
});

it('valida las citas usando los bloques reales del empleado', function () {
    Mail::fake();

    $empleado = Empleado::factory()->peluqueria()->create();
    $cliente = Cliente::factory()->create();
    $servicio = Servicio::factory()->peluqueria()->create([
        'tiempo_estimado' => 30,
    ]);

    crearBloquesHorario($empleado, '2026-07-08', '08:30', '14:00'); // miercoles
    crearBloquesHorario($empleado, '2026-07-09', '08:30', '19:00'); // jueves

    $jueves = $this->post(route('citas.store'), [
        'id_cliente' => $cliente->id,
        'id_empleado' => $empleado->id,
        'servicios' => [$servicio->id],
        'fecha_hora' => '2026-07-09 16:00:00',
    ]);

    $jueves->assertSessionDoesntHaveErrors();
    expect(Cita::whereDate('fecha_hora', '2026-07-09')->count())->toBe(1);

    $miercoles = $this->post(route('citas.store'), [
        'id_cliente' => $cliente->id,
        'id_empleado' => $empleado->id,
        'servicios' => [$servicio->id],
        'fecha_hora' => '2026-07-08 16:00:00',
    ]);

    $miercoles->assertSessionHasErrors('id_empleado');
    expect(Cita::whereDate('fecha_hora', '2026-07-08')->count())->toBe(0);
});
