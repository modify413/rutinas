@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
<div class="login-wrap">
  <div class="card login-card">
    <h1 style="margin:0 0 4px">⏱️ Iniciar sesión</h1>
    <p class="muted" style="margin-top:0">Inicia sesión para gestionar tus rutinas.</p>
    <form method="POST" action="{{ route('login.attempt') }}">
      @csrf
      <label for="username">Usuario</label>
      <input id="username" name="username" value="{{ old('username', 'admin') }}" required autofocus autocomplete="username">
      <label for="password">Contraseña</label>
      <input id="password" name="password" type="password" required autocomplete="current-password">
      <div style="height:12px"></div>
      <button class="btn" style="width:100%" type="submit">Entrar</button>
      <p style="margin-top:12px;text-align:center"><a href="{{ route('password.forgot') }}" style="color:var(--muted)">¿Olvidaste tu contraseña?</a></p>
    </form>
  </div>
</div>
@endsection
