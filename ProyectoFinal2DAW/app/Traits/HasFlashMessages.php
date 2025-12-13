<?php

namespace App\Traits;

use Illuminate\Http\RedirectResponse;

/**
 * Trait HasFlashMessages
 * 
 * Proporciona métodos consistentes para mensajes flash (success, error, warning, info)
 * Centraliza la lógica de redirección con mensajes
 */
trait HasFlashMessages
{
    /**
     * Redirigir con mensaje de éxito
     */
    protected function redirectWithSuccess(string $route, string $message, array $params = []): RedirectResponse
    {
        return redirect()->route($route, $params)->with('success', $message);
    }

    /**
     * Redirigir con mensaje de error
     */
    protected function redirectWithError(string $route, string $message, array $params = []): RedirectResponse
    {
        return redirect()->route($route, $params)->with('error', $message);
    }

    /**
     * Redirigir con mensaje de advertencia
     */
    protected function redirectWithWarning(string $route, string $message, array $params = []): RedirectResponse
    {
        return redirect()->route($route, $params)->with('warning', $message);
    }

    /**
     * Redirigir con mensaje de información
     */
    protected function redirectWithInfo(string $route, string $message, array $params = []): RedirectResponse
    {
        return redirect()->route($route, $params)->with('info', $message);
    }

    /**
     * Volver atrás con mensaje de éxito
     */
    protected function backWithSuccess(string $message): RedirectResponse
    {
        return back()->with('success', $message);
    }

    /**
     * Volver atrás con mensaje de error
     */
    protected function backWithError(string $message): RedirectResponse
    {
        return back()->with('error', $message);
    }

    /**
     * Volver atrás con mensaje de advertencia
     */
    protected function backWithWarning(string $message): RedirectResponse
    {
        return back()->with('warning', $message);
    }

    /**
     * Volver atrás con mensaje de información
     */
    protected function backWithInfo(string $message): RedirectResponse
    {
        return back()->with('info', $message);
    }
}
