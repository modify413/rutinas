@extends('layouts.app')

@section('title', 'Nueva contraseña')

@section('content')
<div class="login-wrap">
  <div class="card login-card">
    <h1 style="margin:0 0 4px">✏️ Nueva contraseña</h1>
    <p class="muted" style="margin-top:0">Identidad verificada. Elige tu nueva contraseña.</p>
    <form method="POST" action="{{ route('password.reset.save') }}">
      @csrf
      <label for="password">Nueva contraseña</label>
      <input id="password" name="password" type="password" required autocomplete="new-password">
      <label for="password_confirmation">Confirmar nueva contraseña</label>
      <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
      <div style="height:12px"></div>
      <button class="btn" style="width:100%" type="submit">Guardar contraseña</button>
    </form>
  </div>
</div>
@endsection
