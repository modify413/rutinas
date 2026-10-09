@extends('layouts.app')

@section('title', 'Recuperar contraseña')

@section('content')
<div class="login-wrap">
  <div class="card login-card">
    <h1 style="margin:0 0 4px">🔑 Recuperar contraseña</h1>
    <p class="muted" style="margin-top:0">Escribe tu nombre de usuario para ver tu pregunta de seguridad.</p>
    <form method="POST" action="{{ route('password.ask') }}">
      @csrf
      <label for="username">Usuario</label>
      <input id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
      <div style="height:12px"></div>
      <button class="btn" style="width:100%" type="submit">Continuar</button>
      <p style="margin-top:12px;text-align:center"><a href="{{ route('login') }}" style="color:var(--muted)">Volver al inicio de sesión</a></p>
    </form>
  </div>
</div>
@endsection
