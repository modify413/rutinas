<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Rutinas')</title>
<style>
:root{--bg:#0a0a0a;--card:#141414;--card2:#1f1f1f;--accent:#e10600;--accent2:#b00500;--warn:#e5e5e5;--danger:#e10600;--text:#f5f5f5;--muted:#a3a3a3;--line:#2a2a2a;--sidebar-w:220px;--sidebar-c:64px}
*{box-sizing:border-box}body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
a{color:inherit}
.topbar{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:#000;border-bottom:2px solid var(--accent);position:sticky;top:0;z-index:50}
.topbar .left{display:flex;align-items:center;gap:10px}
.icon-btn{background:transparent;border:1px solid var(--line);color:var(--text);border-radius:10px;padding:8px 12px;font-size:1.1rem;cursor:pointer}
.icon-btn:hover{border-color:var(--accent);color:#fff}
.topbar .user{font-size:.9rem;color:var(--muted)}
.container{max-width:1100px;margin:0 auto;padding:16px}
.btn{border:0;border-radius:10px;padding:10px 16px;font-weight:700;cursor:pointer;background:var(--accent);color:#fff}
.btn:hover{background:var(--accent2)}.btn.secondary{background:var(--card2);color:var(--text);border:1px solid var(--line)}
.btn.secondary:hover{border-color:var(--accent)}.btn.danger{background:var(--danger);color:#fff}.btn.warn{background:#e5e5e5;color:#000}.btn.ghost{background:transparent;border:1px solid var(--line);color:var(--text)}
.btn.small{padding:6px 10px;font-size:.85rem;border-radius:8px}
.card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:16px}
input,select,textarea{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #333;background:#000;color:var(--text)}
input:focus,select:focus{outline:1px solid var(--accent);border-color:var(--accent)}
label{font-size:.85rem;color:var(--muted);display:block;margin:8px 0 4px}
.alert{background:#1a0505;border:1px solid var(--accent);padding:10px 12px;border-radius:10px;margin:10px 0}
.error{background:#1a0505;border:1px solid var(--accent);padding:10px 12px;border-radius:10px;margin:10px 0}
.login-wrap{min-height:80vh;display:flex;align-items:center;justify-content:center;padding:16px}
.login-card{width:100%;max-width:400px;border-top:3px solid var(--accent)}
/* ---- Menú lateral: pegado al margen izquierdo, expandible ---- */
.dash{display:block}
.dash-main{min-width:0;margin-left:var(--sidebar-w);transition:margin-left .2s ease}
.sidebar{position:fixed;left:0;top:0;bottom:0;width:var(--sidebar-w);background:#000;border-right:1px solid var(--line);padding:70px 10px 10px;display:flex;flex-direction:column;gap:8px;z-index:40;transition:transform .2s ease}
.tab-btn{display:flex;align-items:center;gap:12px;width:100%;padding:12px;border-radius:12px;border:1px solid transparent;background:transparent;color:var(--text);font-weight:700;cursor:pointer;font-size:.95rem;text-align:left;white-space:nowrap}
.tab-btn .ico{font-size:1.25rem;width:28px;text-align:center;flex-shrink:0}
.tab-btn:hover{background:var(--card2)}
.tab-btn.active{background:var(--accent);color:#fff}
body.nav-collapsed .sidebar{transform:translateX(-105%)}
body.nav-collapsed .dash-main{margin-left:0}
.tab-panel{display:none}.tab-panel.active{display:block;min-width:0}
.grid2{display:grid;grid-template-columns:300px 1fr;gap:16px}
.nav-scrim{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:90}
@media(max-width:900px){
  .dash-main{margin-left:0}
  .sidebar{z-index:100}
  body:not(.nav-open) .sidebar{transform:translateX(-105%)}
  body.nav-open .sidebar{transform:none}
  body.nav-open .nav-scrim{display:block}
  .grid2{grid-template-columns:1fr}
}
.routine-list{display:flex;flex-direction:column;gap:8px}
.routine-item{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;background:#000;border:1px solid var(--line);cursor:pointer}
.routine-item.selected{border-color:var(--accent);box-shadow:0 0 0 1px var(--accent)}
.section{border:1px dashed #444;border-radius:12px;padding:12px;margin:12px 0;background:#101010}
.section-head{display:flex;gap:8px;align-items:center;margin-bottom:8px}
.section-head input{flex:1}
.item{border:1px solid #333;border-radius:10px;padding:10px;margin:8px 0;background:#000}
.item.rest{border-left:6px solid #737373}
.item.exercise{border-left:6px solid var(--accent)}
.item-row{display:grid;grid-template-columns:1fr 130px auto;gap:8px;align-items:end}
@media(max-width:560px){.item-row{grid-template-columns:1fr}}
.item-actions{display:flex;gap:6px;justify-content:flex-end;margin-top:8px;flex-wrap:wrap}
.badge{display:inline-block;font-size:.72rem;font-weight:800;padding:3px 8px;border-radius:999px;background:#333}
.badge.exercise{background:#3d0a08;color:#ffb4ab}.badge.rest{background:#262626;color:#e5e5e5}
.total{font-size:1.3rem;font-weight:800;margin:12px 0}.total span{color:var(--accent)}
.timer-stage{text-align:center;padding:24px 12px}
.timer-time{font-size:4.2rem;font-weight:900;letter-spacing:2px;font-variant-numeric:tabular-nums}
.timer-name{font-size:1.8rem;font-weight:800;margin:8px 0;text-transform:uppercase}
.timer-section{color:var(--muted);font-size:.9rem}
.timer-next{margin-top:10px;color:var(--muted)}
.timer-gif{max-width:320px;width:100%;max-height:300px;object-fit:contain;border-radius:12px;margin:12px auto;display:block;background:#000}
.timer-rest .timer-time{color:#fff}.timer-exercise .timer-time{color:var(--accent)}
.progress{height:10px;background:#000;border:1px solid var(--line);border-radius:999px;overflow:hidden;margin:12px 0}
.progress>div{height:100%;background:var(--accent);width:0%}
.timer-controls{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:14px}
.finished{font-size:2rem;font-weight:900}
.muted{color:var(--muted);font-size:.9rem}
.row{display:flex;gap:8px;flex-wrap:wrap}.row>*{flex:1;min-width:140px}
/* ---- Adaptación a móviles ---- */
img{max-width:100%}
body{overflow-x:hidden}
.routine-item>span{min-width:0;overflow-wrap:anywhere}
@media(max-width:900px){
  .container{padding:12px}
}
@media(max-width:640px){
  .item-row{grid-template-columns:1fr}
  .row>*{min-width:120px}
}
@media(max-width:560px){
  .container{padding:10px}
  .topbar{padding:10px 12px}
  .topbar .user{display:none}
  .card{padding:12px}
  input,select,textarea{font-size:16px} /* evita zoom automático al enfocar en iOS */
  .btn{min-height:44px}
  .timer-stage{padding:16px 4px}
  .timer-time{font-size:clamp(3rem,19vw,4.2rem)}
  .timer-name{font-size:1.3rem}
  .finished{font-size:1.5rem}
  .total{font-size:1.1rem}
  .section{padding:10px}
  .section-head{flex-wrap:wrap}
  .section-head input{flex:1 1 100%}
  .timer-gif{max-height:240px}
}
/* ---- Biblioteca: botones estándar + modal ---- */
.lib-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px;margin-top:8px}
.lib-btn{padding:12px 8px;min-height:48px;border-radius:10px;border:1px solid var(--line);background:var(--card2);color:var(--text);font-weight:700;font-size:.9rem;cursor:pointer;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.lib-btn:hover{border-color:var(--accent)}
.lib-btn.selected{background:var(--accent);border-color:var(--accent);color:#fff}
.modal-scrim{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;padding:16px;overflow-y:auto}
.modal-scrim.open{display:block}
.modal{max-width:560px;margin:5vh auto;border-top:3px solid var(--accent)}
/* ---- Popups (toasts) para todas las alertas ---- */
.toast-wrap{position:fixed;top:14px;left:50%;transform:translateX(-50%);z-index:400;display:flex;flex-direction:column;gap:8px;align-items:center;pointer-events:none;width:min(92vw,480px)}
.toast{background:#000;border:1px solid #e5e5e5;color:var(--text);border-radius:12px;padding:12px 16px;font-weight:600;text-align:center;box-shadow:0 8px 30px rgba(0,0,0,.6);opacity:1;transition:opacity .3s ease;width:100%}
.toast.err{border-color:var(--accent);background:#1a0505}
.toast.out{opacity:0}
/* ---- Pantalla completa del timer: solo se ve el timer ---- */
#timer-fs{background:var(--card);border-radius:14px}
#timer-fs:fullscreen{background:#000;border-radius:0;padding:32px 16px;overflow-y:auto;display:flex;flex-direction:column;justify-content:center}
#timer-fs:fullscreen .timer-time{font-size:clamp(5rem,22vw,11rem)}
#timer-fs:fullscreen .timer-name{font-size:clamp(1.6rem,6vw,3rem)}
#timer-fs:fullscreen .timer-gif{max-width:min(480px,80vw);max-height:40vh}
#timer-fs:fullscreen .no-fs{display:none !important}
.only-fs{display:none !important}
#timer-fs:fullscreen .only-fs{display:inline-block !important}
</style>
@yield('head')
</head>
<body>
<div class="topbar">
  <div class="left">
    @auth
      <button class="icon-btn" id="nav-toggle" type="button" title="Mostrar/ocultar menú">☰</button>
    @endauth
  </div>
  <div>
    @auth
      <span class="user">Hola, {{ auth()->user()->username ?? auth()->user()->name }}</span>
      <form action="{{ route('logout') }}" method="POST" style="display:inline">
        @csrf
        <button class="btn small secondary" type="submit">Salir</button>
      </form>
    @endauth
  </div>
</div>
<div class="nav-scrim" id="nav-scrim"></div>
<div class="container">
  @if(session('status'))<div class="alert">{{ session('status') }}</div>@endif
  @if($errors->any())
    <div class="error">
      <ul style="margin:0;padding-left:18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif
  @yield('content')
</div>
@yield('scripts')
</body>
</html>
