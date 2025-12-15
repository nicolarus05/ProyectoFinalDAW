<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\Servicio;
use App\Models\Empleado;
use App\Models\BonoPlantilla;

class CacheService
{
    /**
     * Duración del caché en segundos
     * Por defecto: 1 hora (3600 segundos)
     */
    const CACHE_DURATION = 3600;

    /**
     * Obtener todos los servicios activos con caché
     */
    public static function getServiciosActivos()
    {
        return Cache::remember('servicios_activos', self::CACHE_DURATION, function () {
            return Servicio::where('activo', true)
                ->orderBy('categoria')
                ->orderBy('nombre')
                ->get();
        });
    }

    /**
     * Obtener empleados con sus usuarios con caché
     */
    public static function getEmpleados()
    {
        return Cache::remember('empleados_list', self::CACHE_DURATION, function () {
            return Empleado::with('user')
                ->orderBy('id', 'asc')
                ->get();
        });
    }

    /**
     * Obtener empleados disponibles con caché
     * (Nota: Si en el futuro se agrega columna 'disponible', actualizar este método)
     */
    public static function getEmpleadosDisponibles()
    {
        return Cache::remember('empleados_disponibles', self::CACHE_DURATION, function () {
            return Empleado::with('user')
                ->get();
        });
    }

    /**
     * Obtener bonos plantilla activos con caché
     */
    public static function getBonosPlantilla()
    {
        return Cache::remember('bonos_plantilla_activos', self::CACHE_DURATION, function () {
            return BonoPlantilla::with('servicios')
                ->where('activo', true)
                ->get();
        });
    }

    /**
     * Obtener servicio por ID con caché
     */
    public static function getServicio($id)
    {
        return Cache::remember("servicio_{$id}", self::CACHE_DURATION, function () use ($id) {
            return Servicio::find($id);
        });
    }

    /**
     * Obtener empleado por ID con caché
     */
    public static function getEmpleado($id)
    {
        return Cache::remember("empleado_{$id}", self::CACHE_DURATION, function () use ($id) {
            return Empleado::with('user')->find($id);
        });
    }

    /**
     * Limpiar caché de servicios
     */
    public static function clearServiciosCache()
    {
        Cache::forget('servicios_activos');
    }

    /**
     * Limpiar caché de empleados
     */
    public static function clearEmpleadosCache()
    {
        Cache::forget('empleados_list');
        Cache::forget('empleados_disponibles');
    }

    /**
     * Limpiar caché de bonos plantilla
     */
    public static function clearBonosPlantillaCache()
    {
        Cache::forget('bonos_plantilla_activos');
    }

    /**
     * Limpiar todo el caché de datos maestros
     */
    public static function clearAllMasterDataCache()
    {
        self::clearServiciosCache();
        self::clearEmpleadosCache();
        self::clearBonosPlantillaCache();
    }

    /**
     * Limpiar caché de un servicio específico
     */
    public static function clearServicioCache($id)
    {
        Cache::forget("servicio_{$id}");
        self::clearServiciosCache();
    }

    /**
     * Limpiar caché de un empleado específico
     */
    public static function clearEmpleadoCache($id)
    {
        Cache::forget("empleado_{$id}");
        self::clearEmpleadosCache();
    }
}
