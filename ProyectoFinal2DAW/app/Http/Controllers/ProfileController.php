<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use App\Models\User;

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
    public function update(Request $request): RedirectResponse{
        $user = Auth::user();

        $validated = $request->validate([
            'nombre'     => 'required|string|max:255',
            'apellidos'  => 'required|string|max:255',
            'telefono'   => 'nullable|string|max:20',
            'email'      => 'required|email|max:255|unique:users,email,' . $user->id,
            'genero'     => 'required|string|in:masculino,femenino,otro',
            'edad'       => 'nullable|integer|min:0',
            'foto_perfil' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'current_password' => 'nullable|string',
            'password' => 'nullable|confirmed|min:8',
        ]);

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

        // FASE 6: Manejo de la foto de perfil con storage tenant-aware
        if ($request->hasFile('foto_perfil')) {
            // Eliminar la foto anterior si existe
            if ($user->foto_perfil) {
                tenant_storage()->delete($user->foto_perfil, true);
            }
            
            // Guardar la nueva foto en el storage del tenant
            $user->foto_perfil = tenant_upload(
                $request->file('foto_perfil'),
                'perfiles',
                true
            );
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
