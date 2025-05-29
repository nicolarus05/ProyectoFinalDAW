<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
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



        if ($request->hasFile('foto_perfil')) {
            $path = $request->file('foto_perfil')->store('fotos_perfil', 'public');
            $request->user()->foto_perfil = $path;
        }

        $user->save();

        return Redirect::route('dashboard')->with('status', 'profile-updated');
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
