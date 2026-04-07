<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Http\Requests\UpdateProfileRequest;

class ProfileController extends Controller{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View{
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse{
        $user = Auth::user();

        // Los datos ya vienen validados y sanitizados del Form Request
        $validated = $request->validated();

        $user->nombre = $validated['nombre'];
        $user->apellidos = $validated['apellidos'];
        $user->telefono = $validated['telefono'] ?? null;
        $user->email = $validated['email'];
        $user->genero = $validated['genero'];
        $user->edad = $validated['edad'] ?? null;

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Actualizar contraseña si se proporcionó
        if ($request->filled('password')) {
            // Verificar que se proporcionó la contraseña actual
            if (!$request->filled('current_password')) {
                return Redirect::back()
                    ->withErrors(['current_password' => 'Debes proporcionar tu contraseña actual para cambiarla.'])
                    ->withInput();
            }

            // Verificar que la contraseña actual es correcta
            if (!Hash::check($request->current_password, $user->password)) {
                return Redirect::back()
                    ->withErrors(['current_password' => 'La contraseña actual no es correcta.'])
                    ->withInput();
            }

            // Actualizar la contraseña
            $user->password = Hash::make($request->password);
        }

        // FASE 6: Manejo de la foto de perfil usando disco 'public' de Stancl
        if ($request->hasFile('foto_perfil')) {
            // Eliminar la foto anterior si existe
            if ($user->foto_perfil && Storage::disk('public')->exists($user->foto_perfil)) {
                Storage::disk('public')->delete($user->foto_perfil);
            }
            
            // Guardar la nueva foto en el disco público del tenant (gestionado por Stancl)
            $user->foto_perfil = $request->file('foto_perfil')->store('perfiles', 'public');
        }

        $user->save();

        $statusMessage = 'profile-updated';
        if ($request->filled('password')) {
            $statusMessage = 'password-updated';
        }

        return Redirect::route('dashboard')->with('status', $statusMessage);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse{
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updatePassword(Request $request): RedirectResponse{
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'password-updated');
    }

}
