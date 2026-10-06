@extends('layouts.app')

@section('title', 'Verificar identidad')

@section('content')
<div class="login-wrap">
  <div class="card login-card">
    <h1 style="margin:0 0 4px">🛡️ Verifica tu identidad</h1>
    <p class="muted" style="margin-top:0">Responde tu pregunta de seguridad.</p>
    <form method="POST" action="{{ route('password.verify') }}">
      @csrf
      <input type="hidden" name="username" value="{{ $username }}">
      <label>Pregunta</label>
      <p style="margin:4px 0 8px"><strong>{{ $question }}</strong></p>
      <label for="answer">Respuesta</label>
      <input id="answer" name="answer" required autofocus autocomplete="off">
      <div style="height:12px"></div>
      <button class="btn" style="width:100%" type="submit">Verificar</button>
      <p style="margin-top:12px;text-align:center"><a href="{{ route('login') }}" style="color:var(--muted)">Cancelar</a></p>
    </form>
  </div>
</div>
@endsection
