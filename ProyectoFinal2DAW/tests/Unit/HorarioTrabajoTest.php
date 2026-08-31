<?php

use App\Models\HorarioTrabajo;
use Carbon\Carbon;

it('normaliza una hora al bloque de 15 minutos que la contiene', function () {
    expect(HorarioTrabajo::normalizarHoraBloque('09:18:00'))->toBe('09:15:00')
        ->and(HorarioTrabajo::normalizarHoraBloque('09:33:00'))->toBe('09:30:00')
        ->and(HorarioTrabajo::normalizarHoraBloque('10:30:00'))->toBe('10:30:00');
});

it('obtiene el inicio del bloque para una cita fuera del eje', function () {
    $inicio = HorarioTrabajo::inicioBloque(Carbon::parse('2026-09-01 10:20:00'));

    expect($inicio->format('Y-m-d H:i:s'))->toBe('2026-09-01 10:15:00');
});

it('genera siempre bloques consecutivos de 15 minutos', function () {
    expect(HorarioTrabajo::generarBloquesHorarios('08:00', '09:00'))
        ->toBe([
            '08:00:00',
            '08:15:00',
            '08:30:00',
            '08:45:00',
            '09:00:00',
        ]);
});
