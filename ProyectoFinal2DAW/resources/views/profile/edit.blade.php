<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <h1>Editar Perfil</h1>

    {{-- Mensajes de éxito --}}
    @if (session('status') === 'profile-updated')
        <div class="alert alert-success">Perfil actualizado correctamente.</div>
    @elseif (session('status') === 'password-updated')
        <div class="alert alert-success">Contraseña actualizada correctamente.</div>
    @endif

    {{-- Mostrar errores --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulario de actualización de perfil --}}
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        {{-- Foto actual --}}
        @if ($user->foto_perfil)
            <div>
                <img src="{{ asset('storage/' . $user->foto_perfil) }}" alt="Foto de perfil" width="150">
            </div>
        @endif

        <label for="foto_perfil">Foto de perfil:</label>
        <input type="file" name="foto_perfil" accept="image/*">

        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" value="{{ old('nombre', $user->nombre) }}" required>

        <label for="apellidos">Apellidos:</label>
        <input type="text" name="apellidos" value="{{ old('apellidos', $user->apellidos) }}" required>

        <label for="telefono">Teléfono:</label>
        <input type="text" name="telefono" value="{{ old('telefono', $user->telefono) }}">

        <label for="email">Correo electrónico:</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>

        <label for="genero">Género:</label>
        <select name="genero" required>
            <option value="">Seleccione</option>
            <option value="masculino" {{ $user->genero == 'masculino' ? 'selected' : '' }}>Masculino</option>
            <option value="femenino" {{ $user->genero == 'femenino' ? 'selected' : '' }}>Femenino</option>
            <option value="otro" {{ $user->genero == 'otro' ? 'selected' : '' }}>Otro</option>
        </select>

        <label for="edad">Edad:</label>
        <input type="number" name="edad" value="{{ old('edad', $user->edad) }}">

        <label for="current_password">Contraseña Actual:</label>
        <input type="password" name="current_password">

        <label for="password">Nueva Contraseña:</label>
        <input type="password" name="password">

        <label for="password_confirmation">Confirmar Nueva Contraseña:</label>
        <input type="password" name="password_confirmation">

        <br>
        <button type="submit">Actualizar Perfil</button>
    </form>
    
    {{-- Enlace para volver al dashboard --}}
    <a href="{{ route('dashboard') }}">Volver</a>
</body>
</html>
