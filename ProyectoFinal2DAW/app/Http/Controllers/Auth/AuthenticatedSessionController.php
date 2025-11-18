<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        Log::info('=== INTENTO DE LOGIN ===');
        Log::info('Email: ' . $request->email);
        Log::info('Host recibido: ' . $request->getHost());
        Log::info('HTTP Host header: ' . $request->getHttpHost());
        Log::info('URL completa: ' . $request->fullUrl());
        Log::info('Datos del request:', $request->all());
        
        try {
            $request->authenticate();
            Log::info('Autenticación exitosa');
        } catch (\Exception $e) {
            Log::error('Error en autenticación: ' . $e->getMessage());
            throw $e;
        }

        $request->session()->regenerate();
        Log::info('Sesión regenerada, redirigiendo a dashboard');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
