@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
  $selectedId = (int) request('routine_id', $routines->first()?->id ?? 0);
@endphp

<div class="dash">
  <aside class="sidebar" id="sidebar">
    <button class="tab-btn" data-tab="rutinas"><span class="ico">📝</span><span class="lbl">Crear rutinas</span></button>
    <button class="tab-btn" data-tab="timer"><span class="ico">⏱️</span><span class="lbl">Timer</span></button>
    <button class="tab-btn" data-tab="config"><span class="ico">⚙️</span><span class="lbl">Configuración</span></button>
  </aside>
  <div class="dash-main">

{{-- PESTAÑA 1: RUTINAS --}}
<div class="tab-panel" id="panel-rutinas">
  <div class="grid2">
    <div class="card">
      <h3 style="margin-top:0">Mis rutinas</h3>
      <div class="routine-list" id="routine-list">
        @forelse($routines as $r)
          <div class="routine-item {{ $r->id === $selectedId ? 'selected' : '' }}" data-id="{{ $r->id }}">
            <span><strong>{{ $r->name }}</strong><br><small class="muted">{{ $r->sections->sum(fn($s) => $s->items->sum('duration_seconds')) }}s · {{ $r->sections->count() }} sec.</small></span>
          </div>
        @empty
          <p class="muted">Aún no tienes rutinas. Crea la primera abajo.</p>
        @endforelse
      </div>
      <hr style="border-color:#2b3b52;margin:14px 0">
      <form method="POST" action="{{ route('routines.store') }}">
        @csrf
        <label for="new-name">+ Nueva rutina</label>
        <input id="new-name" name="name" placeholder="Ej: Rutina de pecho" required maxlength="255">
        <div style="height:8px"></div>
        <button class="btn" style="width:100%" type="submit">Crear rutina</button>
      </form>
    </div>

    <div class="card">
      <div id="editor-empty" style="display:none">
        <p class="muted">Selecciona una rutina de la lista para editarla.</p>
      </div>
      <div id="editor-box">
        <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
          <div style="flex:1;min-width:200px">
            <label for="routine-name">Nombre de la rutina</label>
            <input id="routine-name" maxlength="255">
          </div>
          <form id="delete-form" method="POST" style="margin:0">
            @csrf
            @method('DELETE')
            <button class="btn small danger" type="submit" onclick="return confirm('¿Eliminar esta rutina?')">Eliminar</button>
          </form>
        </div>

        <div class="total">Tiempo total: <span id="total-time">0:00</span></div>

        <div id="sections"></div>

        <div class="row">
          <button class="btn secondary" id="btn-add-section" type="button">+ Agregar sección</button>
          <button class="btn" id="btn-save" type="button">💾 Guardar rutina</button>
        </div>
        <p class="muted" id="editor-status"></p>
        <p class="muted">Duración: escribe segundos (<b>90</b>) o minutos:segundos (<b>1:30</b>). El total se calcula en tiempo real e incluye ejercicios y descansos.</p>
      </div>
    </div>
  </div>
</div>

{{-- PESTAÑA 2: TIMER --}}
<div class="tab-panel" id="panel-timer">
  <div class="card">
    <h3 style="margin-top:0">Ejecutar rutina</h3>
    <div class="row no-fs">
      <div>
        <label for="timer-select">Seleccionar rutina</label>
        <select id="timer-select">
          <option value="">— Elige una rutina —</option>
          @foreach($routines as $r)
            <option value="{{ $r->id }}">{{ $r->name }}</option>
          @endforeach
        </select>
      </div>
      <div style="display:flex;align-items:flex-end;gap:8px">
        <button class="btn" id="timer-start" type="button">▶ Iniciar</button>
        <button class="btn secondary" id="timer-stop" type="button">⏹ Detener</button>
        <button class="btn secondary" id="timer-fs-btn" type="button" title="Ver solo el timer en pantalla completa">⛶ Pantalla completa</button>
      </div>
    </div>

    <div id="timer-empty" class="no-fs">
      <p class="muted">Selecciona una rutina y pulsa Iniciar.</p>
    </div>

    <div id="timer-fs">
    <div id="timer-run" style="display:none" class="timer-exercise">
      <div class="timer-stage">
        <div class="timer-section" id="t-section"></div>
        <div class="timer-time" id="t-time">00:00</div>
        <div class="timer-name" id="t-name"></div>
        <img class="timer-gif" id="t-gif" alt="" style="display:none">
        <div class="timer-next" id="t-next"></div>
        <div class="progress"><div id="t-progress"></div></div>
        <div class="muted" id="t-count"></div>
      </div>
      <div class="timer-controls">
        <button class="btn warn" id="timer-pause" type="button">⏸ Pausar</button>
        <button class="btn secondary" id="timer-prev" type="button">⏮ Anterior</button>
        <button class="btn secondary" id="timer-nextbtn" type="button">⏭ Siguiente</button>
        <button class="btn ghost" id="timer-restart" type="button">↻ Reiniciar</button>
        <button class="btn ghost only-fs" id="timer-fs-exit" type="button">⛶ Salir de pantalla completa</button>
      </div>
    </div>

    <div id="timer-done" style="display:none;text-align:center;padding:24px">
      <div class="finished">🏁 RUTINA FINALIZADA</div>
      <p>¡Buen trabajo!</p>
      <button class="btn" id="timer-again" type="button">↻ Reiniciar</button>
      <button class="btn secondary" id="timer-back" type="button">Volver a selección</button>
      <button class="btn ghost only-fs" id="timer-fs-exit2" type="button">⛶ Salir de pantalla completa</button>
    </div>
    </div><!-- /timer-fs -->
  </div>
</div>

{{-- PESTAÑA 3: CONFIG --}}
<div class="tab-panel" id="panel-config">
  <div class="grid2">
    <div class="card">
      <h3 style="margin-top:0">Cambiar nombre de usuario</h3>
      <form method="POST" action="{{ route('settings.username') }}">
        @csrf @method('PUT')
        <label for="username">Nombre de usuario</label>
        <input id="username" name="username" value="{{ old('username', $user->username) }}" required maxlength="255">
        <div style="height:8px"></div>
        <button class="btn" type="submit">Guardar usuario</button>
      </form>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Cambiar contraseña</h3>
      <form method="POST" action="{{ route('settings.password') }}">
        @csrf @method('PUT')
        <label for="current_password">Contraseña actual</label>
        <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
        <label for="password">Nueva contraseña</label>
        <input id="password" name="password" type="password" required autocomplete="new-password">
        <label for="password_confirmation">Confirmar nueva contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        <div style="height:8px"></div>
        <button class="btn" type="submit">Guardar contraseña</button>
      </form>
    </div>
  </div>
</div>
  </div><!-- /dash-main -->
</div><!-- /dash -->
@endsection

@section('scripts')
<script>
window.ROUTINES = @json($routines->keyBy('id'));
window.SELECTED_ID = {{ $selectedId }};
window.INIT_TAB = @json(request('tab', 'rutinas'));
const CSRF = @json(csrf_token());

// ---------- Menú lateral expandible ----------
const navToggle = document.getElementById('nav-toggle');
const navScrim = document.getElementById('nav-scrim');
const mqMobile = window.matchMedia('(max-width: 900px)');
try { if (localStorage.getItem('app_nav') === 'collapsed') document.body.classList.add('nav-collapsed'); } catch(e) {}
function toggleNav() {
  if (mqMobile.matches) {
    document.body.classList.toggle('nav-open');
  } else {
    document.body.classList.toggle('nav-collapsed');
    try { localStorage.setItem('app_nav', document.body.classList.contains('nav-collapsed') ? 'collapsed' : 'expanded'); } catch(e) {}
  }
}
if (navToggle) navToggle.addEventListener('click', toggleNav);
if (navScrim) navScrim.addEventListener('click', () => document.body.classList.remove('nav-open'));

// ---------- Tabs ----------
const tabBtns = document.querySelectorAll('.tab-btn');
const panels = { rutinas: document.getElementById('panel-rutinas'), timer: document.getElementById('panel-timer'), config: document.getElementById('panel-config') };
function showTab(name) {
  tabBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === name));
  Object.entries(panels).forEach(([k, el]) => el.classList.toggle('active', k === name));
  try { localStorage.setItem('app_tab', name); } catch(e) {}
  document.body.classList.remove('nav-open');
  document.dispatchEvent(new CustomEvent('dashboard-tab', { detail: name }));
}
tabBtns.forEach(b => b.addEventListener('click', () => showTab(b.dataset.tab)));
showTab(['rutinas','timer','config'].includes(window.INIT_TAB) ? window.INIT_TAB : (localStorage.getItem('app_tab') || 'rutinas'));

// ---------- Helpers duración ----------
function parseDuration(str) {
  str = String(str ?? '').trim();
  if (!str) return 0;
  if (str.includes(':')) {
    const parts = str.split(':').map(p => p.trim());
    if (parts.length === 2) {
      const m = parseInt(parts[0], 10) || 0, s = parseInt(parts[1], 10) || 0;
      return m * 60 + s;
    }
    if (parts.length === 3) {
      const h = parseInt(parts[0],10)||0, m = parseInt(parts[1],10)||0, s = parseInt(parts[2],10)||0;
      return h*3600 + m*60 + s;
    }
    return 0;
  }
  return Math.max(0, parseInt(str, 10) || 0);
}
function fmt(total) {
  total = Math.max(0, Math.round(total));
  const m = Math.floor(total / 60), s = total % 60;
  return m + ':' + String(s).padStart(2, '0');
}
function fmtClock(total) {
  total = Math.max(0, Math.ceil(total));
  const m = Math.floor(total / 60), s = total % 60;
  return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
}
function esc(s){ return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ---------- Editor de rutinas ----------
let selectedId = window.SELECTED_ID || null;
const sectionsEl = document.getElementById('sections');
const nameEl = document.getElementById('routine-name');
const totalEl = document.getElementById('total-time');
const statusEl = document.getElementById('editor-status');
const deleteForm = document.getElementById('delete-form');

function currentRoutine() { return selectedId ? window.ROUTINES[selectedId] : null; }

function recalcTotal() {
  let total = 0;
  sectionsEl.querySelectorAll('[data-dur]').forEach(inp => { total += parseDuration(inp.value); });
  totalEl.textContent = fmt(total);
}

function itemHtml(item, sIdx, iIdx) {
  const isRest = item.type === 'rest';
  return `<div class="item ${isRest ? 'rest' : 'exercise'}" data-item>
    <div><span class="badge ${isRest ? 'rest' : 'exercise'}">${isRest ? 'DESCANSO' : 'EJERCICIO'}</span></div>
    <div class="item-row" style="margin-top:8px">
      <div><label>Nombre${isRest ? ' (ej: Descanso, Hidratación)' : ''}</label>
        <input data-name value="${esc(item.name || '')}" placeholder="${isRest ? 'Descanso' : 'Ej: Flexiones'}" maxlength="255"></div>
      <div><label>Tiempo (s o m:ss)</label>
        <input data-dur value="${esc(item.duration_display ?? item.duration_seconds ?? '30')}" placeholder="30 o 1:30"></div>
      <div><label>Tipo</label>
        <select data-type>
          <option value="exercise" ${!isRest ? 'selected' : ''}>Ejercicio</option>
          <option value="rest" ${isRest ? 'selected' : ''}>Descanso</option>
        </select></div>
    </div>
    <div class="gif-row" style="${isRest ? 'display:none' : ''};margin-top:8px">
      <label>GIF del ejercicio (opcional)</label>
      <div style="display:flex;gap:6px;margin-bottom:6px">
        <button type="button" class="btn small ${item.gif_path ? 'secondary' : ''}" data-gifmode="url" style="${item.gif_path ? '' : 'background:var(--accent)'}">🔗 URL</button>
        <button type="button" class="btn small secondary" data-gifmode="file" style="${item.gif_path ? 'background:var(--accent);border-color:var(--accent)' : ''}">📁 Mi PC</button>
        <button type="button" class="btn small ghost" data-gifclear>Quitar</button>
      </div>
      <input data-gif value="${esc(item.gif_url || '')}" placeholder="Enlace directo a la imagen (termina en .gif)" maxlength="2048" style="${item.gif_path ? 'display:none' : ''}">
      <input type="file" data-giffile accept="image/gif,image/*" style="display:none;margin-top:6px">
      <input type="hidden" data-gifpath value="${esc(item.gif_path || '')}">
      <div><img data-gifpreview alt="" style="max-width:180px;max-height:140px;display:none;border-radius:8px;margin-top:6px;background:#000"></div>
      <div class="muted" data-gifmsg style="margin-top:4px">${item.gif_path ? '📁 Archivo subido al bucket ✓' : ''}</div>
      <div class="muted" style="font-size:.75rem">En Giphy/Tenor usa clic derecho → "Copiar dirección de imagen". La página del GIF no funciona, debe ser el enlace directo.</div>
    </div>
    <div class="item-actions">
      <button type="button" class="btn small secondary" data-move-item="-1">↑</button>
      <button type="button" class="btn small secondary" data-move-item="1">↓</button>
      <button type="button" class="btn small danger" data-del-item>Eliminar</button>
    </div>
  </div>`;
}

function renderEditor() {
  const r = currentRoutine();
  document.querySelectorAll('.routine-item').forEach(el => el.classList.toggle('selected', +el.dataset.id === +selectedId));
  if (!r) {
    document.getElementById('editor-box').style.display = 'none';
    document.getElementById('editor-empty').style.display = 'block';
    return;
  }
  document.getElementById('editor-box').style.display = 'block';
  document.getElementById('editor-empty').style.display = 'none';
  nameEl.value = r.name;
  deleteForm.action = `/routines/${r.id}`;
  sectionsEl.innerHTML = '';
  (r.sections || []).forEach((s, sIdx) => {
    const div = document.createElement('div');
    div.className = 'section';
    div.dataset.section = '';
    if (s.id) div.dataset.sid = s.id;
    div.innerHTML = `
      <div class="section-head">
        <input data-title value="${esc(s.title || ('Sección ' + (sIdx+1)))}" maxlength="255">
        <button type="button" class="btn small secondary" data-move-section="-1">↑</button>
        <button type="button" class="btn small secondary" data-move-section="1">↓</button>
        <button type="button" class="btn small danger" data-del-section>✕</button>
      </div>
      <div data-items></div>
      <div class="row">
        <button type="button" class="btn small secondary" data-add="exercise">+ Agregar ejercicio</button>
        <button type="button" class="btn small secondary" data-add="rest">+ Agregar descanso</button>
      </div>`;
    const itemsBox = div.querySelector('[data-items]');
    (s.items || []).forEach(it => {
      const w = document.createElement('div');
      w.innerHTML = itemHtml({ ...it, duration_display: fmt(it.duration_seconds ?? 30) }, sIdx, 0);
      const node = w.firstElementChild;
      if (it.id) node.dataset.iid = it.id;
      itemsBox.appendChild(node);
    });
    sectionsEl.appendChild(div);
  });
  recalcTotal();
}

document.getElementById('routine-list').addEventListener('click', e => {
  const el = e.target.closest('.routine-item');
  if (el) { selectedId = +el.dataset.id; renderEditor(); }
});

document.getElementById('btn-add-section').addEventListener('click', () => {
  const n = sectionsEl.querySelectorAll('[data-section]').length + 1;
  const div = document.createElement('div');
  div.className = 'section'; div.dataset.section = '';
  div.innerHTML = `
    <div class="section-head">
      <input data-title value="Sección ${n}" maxlength="255">
      <button type="button" class="btn small secondary" data-move-section="-1">↑</button>
      <button type="button" class="btn small secondary" data-move-section="1">↓</button>
      <button type="button" class="btn small danger" data-del-section>✕</button>
    </div>
    <div data-items></div>
    <div class="row">
      <button type="button" class="btn small secondary" data-add="exercise">+ Agregar ejercicio</button>
      <button type="button" class="btn small secondary" data-add="rest">+ Agregar descanso</button>
    </div>`;
  sectionsEl.appendChild(div);
  recalcTotal();
});

sectionsEl.addEventListener('click', e => {
  const addBtn = e.target.closest('[data-add]');
  if (addBtn) {
    const sec = addBtn.closest('[data-section]');
    const type = addBtn.dataset.add;
    const w = document.createElement('div');
    w.innerHTML = itemHtml({ type, name: type === 'rest' ? 'Descanso' : '', duration_seconds: type === 'rest' ? 15 : 30, duration_display: type === 'rest' ? '0:15' : '0:30', gif_url: '' });
    sec.querySelector('[data-items]').appendChild(w.firstElementChild);
    recalcTotal();
    return;
  }
  if (e.target.closest('[data-del-section]')) { e.target.closest('[data-section]').remove(); recalcTotal(); return; }
  if (e.target.closest('[data-del-item]')) { e.target.closest('[data-item]').remove(); recalcTotal(); return; }
  const gifMode = e.target.closest('[data-gifmode]');
  if (gifMode) {
    const item = gifMode.closest('[data-item]');
    const urlIn = item.querySelector('[data-gif]');
    const fileIn = item.querySelector('[data-giffile]');
    const msg = item.querySelector('[data-gifmsg]');
    const isFile = gifMode.dataset.gifmode === 'file';
    urlIn.style.display = isFile ? 'none' : '';
    fileIn.style.display = isFile ? '' : 'none';
    gifMode.parentElement.querySelectorAll('[data-gifmode]').forEach(b => { b.style.background = ''; b.style.borderColor = ''; });
    gifMode.style.background = 'var(--accent)'; gifMode.style.borderColor = 'var(--accent)';
    if (isFile) {
      msg.textContent = item.querySelector('[data-gifpath]').value
        ? '📁 Ya hay un archivo subido ✓ (elige otro para reemplazarlo).'
        : 'Elige un GIF de tu PC (máx 8 MB). Se sube al guardar.';
    } else {
      fileIn.value = ''; item._gifFile = null;
      updateGifPreview(item);
    }
    return;
  }
  if (e.target.closest('[data-gifclear]')) {
    const item = e.target.closest('[data-item]');
    item.querySelector('[data-gif]').value = '';
    item.querySelector('[data-gifpath]').value = '';
    item.querySelector('[data-giffile]').value = '';
    item._gifFile = null;
    const prev = item.querySelector('[data-gifpreview]');
    prev.removeAttribute('src'); prev.style.display = 'none';
    item.querySelector('[data-gifmsg]').textContent = '';
    return;
  }
  const mvS = e.target.closest('[data-move-section]');
  if (mvS) {
    const sec = mvS.closest('[data-section]');
    const dir = +mvS.dataset.moveSection;
    if (dir < 0 && sec.previousElementSibling) sectionsEl.insertBefore(sec, sec.previousElementSibling);
    if (dir > 0 && sec.nextElementSibling) sectionsEl.insertBefore(sec.nextElementSibling, sec);
    return;
  }
  const mvI = e.target.closest('[data-move-item]');
  if (mvI) {
    const item = mvI.closest('[data-item]');
    const box = item.parentElement;
    const dir = +mvI.dataset.moveItem;
    if (dir < 0 && item.previousElementSibling) box.insertBefore(item, item.previousElementSibling);
    if (dir > 0 && item.nextElementSibling) box.insertBefore(item.nextElementSibling, item);
    return;
  }
});

sectionsEl.addEventListener('input', e => { recalcTotal(); });

function updateGifPreview(item) {
  const urlInput = item.querySelector('[data-gif]');
  const url = urlInput.value.trim();
  const prev = item.querySelector('[data-gifpreview]');
  const msg = item.querySelector('[data-gifmsg]');
  const savedPath = item.querySelector('[data-gifpath]').value;
  if (!url) {
    prev.removeAttribute('src'); prev.style.display = 'none';
    msg.textContent = savedPath ? '📁 Archivo subido al bucket ✓' : '';
    return;
  }
  msg.textContent = '⏳ Comprobando enlace…';
  const probe = new Image();
  probe.onload = () => { prev.src = url; prev.style.display = 'block'; msg.textContent = '✅ El GIF se ve correctamente.'; };
  probe.onerror = () => { prev.removeAttribute('src'); prev.style.display = 'none'; msg.textContent = '❌ Ese enlace no carga como imagen. Usa el enlace directo (termina en .gif).'; };
  probe.src = url;
}

sectionsEl.addEventListener('input', e => {
  if (e.target.matches('[data-gif]')) updateGifPreview(e.target.closest('[data-item]'));
});
sectionsEl.addEventListener('change', e => {
  if (e.target.matches('[data-giffile]')) {
    const item = e.target.closest('[data-item]');
    const f = e.target.files[0] || null;
    item._gifFile = f;
    const prev = item.querySelector('[data-gifpreview]');
    const msg = item.querySelector('[data-gifmsg]');
    if (f) {
      prev.src = URL.createObjectURL(f);
      prev.style.display = 'block';
      msg.textContent = `📁 ${f.name} listo. Se sube al guardar la rutina.`;
    } else {
      prev.removeAttribute('src'); prev.style.display = 'none'; msg.textContent = '';
    }
    return;
  }
  if (e.target.matches('[data-type]')) {
    const item = e.target.closest('[data-item]');
    const gifRow = item.querySelector('.gif-row');
    const badge = item.querySelector('.badge');
    const isRest = e.target.value === 'rest';
    item.classList.toggle('rest', isRest);
    item.classList.toggle('exercise', !isRest);
    badge.textContent = isRest ? 'DESCANSO' : 'EJERCICIO';
    badge.className = 'badge ' + (isRest ? 'rest' : 'exercise');
    gifRow.style.display = isRest ? 'none' : '';
    if (isRest && !item.querySelector('[data-name]').value.trim()) item.querySelector('[data-name]').value = 'Descanso';
  }
});

document.getElementById('btn-save').addEventListener('click', async () => {
  const r = currentRoutine();
  if (!r) return;
  const name = nameEl.value.trim();
  if (!name) { statusEl.textContent = 'El nombre de la rutina es obligatorio.'; return; }
  // Subir primero los GIF elegidos desde la PC
  const pending = [...sectionsEl.querySelectorAll('[data-item]')]
    .filter(it => it._gifFile && it.querySelector('[data-type]').value === 'exercise');
  if (pending.length) statusEl.textContent = `Subiendo ${pending.length} GIF(s)…`;
  for (const it of pending) {
    const fd = new FormData();
    fd.append('gif', it._gifFile);
    try {
      const res = await fetch('/uploads/gif', {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: fd,
      });
      const data = await res.json().catch(() => null);
      if (!res.ok || !data || !data.path) throw new Error((data && data.message) || ('No se pudo subir ' + it._gifFile.name));
      it.querySelector('[data-gifpath]').value = data.path;
      it.querySelector('[data-gif]').value = '';
      it._gifFile = null;
    } catch (err) {
      statusEl.textContent = '❌ ' + err.message;
      return;
    }
  }
  const sections = [...sectionsEl.querySelectorAll('[data-section]')].map(sec => ({
    id: sec.dataset.sid ? +sec.dataset.sid : null,
    title: sec.querySelector('[data-title]').value.trim() || 'Sección',
    items: [...sec.querySelectorAll('[data-item]')].map(it => {
      const type = it.querySelector('[data-type]').value;
      let nm = it.querySelector('[data-name]').value.trim();
      if (!nm) nm = type === 'rest' ? 'Descanso' : 'Ejercicio';
      return {
        id: it.dataset.iid ? +it.dataset.iid : null,
        type,
        name: nm,
        duration_seconds: Math.max(1, parseDuration(it.querySelector('[data-dur]').value) || 30),
        gif_url: type === 'exercise' ? (it.querySelector('[data-gif]').value.trim() || null) : null,
        gif_path: type === 'exercise' ? (it.querySelector('[data-gifpath]').value.trim() || null) : null,
      };
    }),
  }));
  statusEl.textContent = 'Guardando…';
  try {
    const res = await fetch(`/routines/${r.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ name, sections }),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error(err.message || ('Error ' + res.status));
    }
    const saved = await res.json();
    // Normalizar estructura guardada
    window.ROUTINES[saved.id] = saved;
    selectedId = saved.id;
    renderEditor();
    statusEl.textContent = '✅ Rutina guardada en SQLite.';
    refreshTimerOptions();
  } catch (err) {
    statusEl.textContent = '❌ ' + err.message;
  }
});

function refreshTimerOptions() {
  const sel = document.getElementById('timer-select');
  const cur = sel.value;
  sel.innerHTML = '<option value="">— Elige una rutina —</option>' +
    Object.values(window.ROUTINES).map(r => `<option value="${r.id}">${esc(r.name)}</option>`).join('');
  if (cur && window.ROUTINES[cur]) sel.value = cur;
}

renderEditor();

// ---------- TIMER ----------
const timerSelect = document.getElementById('timer-select');
const timerEmpty = document.getElementById('timer-empty');
const timerRun = document.getElementById('timer-run');
const timerDone = document.getElementById('timer-done');
const tTime = document.getElementById('t-time'), tName = document.getElementById('t-name'),
      tGif = document.getElementById('t-gif'), tNext = document.getElementById('t-next'),
      tSection = document.getElementById('t-section'), tCount = document.getElementById('t-count'),
      tProgress = document.getElementById('t-progress');

let queue = [], idx = 0, remaining = 0, totalRoutine = 0, elapsedBefore = 0;
let tickId = null, paused = false, lastWhole = -1, lastBeepSecond = -1, endAt = 0;
let audioCtx = null;

function beep(freq = 880, dur = 0.15) {
  try {
    audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === 'suspended') audioCtx.resume();
    const o = audioCtx.createOscillator(), g = audioCtx.createGain();
    o.connect(g); g.connect(audioCtx.destination);
    o.frequency.value = freq;
    g.gain.setValueAtTime(0.001, audioCtx.currentTime);
    g.gain.exponentialRampToValueAtTime(0.5, audioCtx.currentTime + 0.02);
    g.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + dur);
    o.start(); o.stop(audioCtx.currentTime + dur + 0.02);
  } catch(e) {}
}

async function loadRoutine(id) {
  const res = await fetch(`/routines/${id}/json`, { headers: { 'Accept': 'application/json' } });
  if (!res.ok) throw new Error('No se pudo cargar la rutina');
  return res.json();
}

function showIdle() {
  timerRun.style.display = 'none'; timerDone.style.display = 'none'; timerEmpty.style.display = 'block';
}
function showRun() {
  timerEmpty.style.display = 'none'; timerDone.style.display = 'none'; timerRun.style.display = 'block';
}
function showDone() {
  timerRun.style.display = 'none'; timerDone.style.display = 'block';
  stopTick();
  beep(660, .2); setTimeout(() => beep(880, .25), 220); setTimeout(() => beep(1100, .35), 480);
}

function stopTick() { if (tickId) clearInterval(tickId); tickId = null; }

function renderCurrent() {
  const cur = queue[idx];
  if (!cur) { showDone(); return; }
  const isRest = cur.type === 'rest';
  timerRun.classList.toggle('timer-rest', isRest);
  timerRun.classList.toggle('timer-exercise', !isRest);
  tSection.textContent = cur.section || '';
  tName.textContent = cur.name;
  tTime.textContent = fmtClock(remaining);
  const nxt = queue[idx + 1];
  tNext.textContent = nxt ? `Próximo: ${nxt.name} (${fmt(nxt.duration_seconds)})` : 'Último elemento de la rutina';
  tCount.textContent = `Elemento ${idx + 1} de ${queue.length}`;
  if (!isRest && cur.gif_url) { tGif.src = cur.gif_url; tGif.style.display = 'block'; tGif.onerror = () => tGif.style.display = 'none'; }
  else { tGif.removeAttribute('src'); tGif.style.display = 'none'; }
  const done = queue.slice(0, idx).reduce((a, b) => a + b.duration_seconds, 0) + (cur.duration_seconds - remaining);
  tProgress.style.width = totalRoutine ? Math.min(100, (done / totalRoutine) * 100) + '%' : '0%';
}

function tick() {
  if (paused) return;
  remaining = Math.max(0, (endAt - Date.now()) / 1000);
  const whole = Math.ceil(remaining);
  if (whole !== lastWhole) {
    lastWhole = whole;
    renderCurrent();
    // Bip solo en los últimos 5 segundos (5,4,3,2,1), una vez por segundo
    if (whole <= 5 && whole >= 1 && whole !== lastBeepSecond) {
      lastBeepSecond = whole;
      beep(880, 0.12);
    }
  } else {
    renderCurrent();
  }
  if (remaining <= 0) {
    beep(1320, 0.3); // tono de cambio de elemento
    idx++;
    if (idx >= queue.length) { showDone(); return; }
    startElement(idx);
  }
}

function startElement(i) {
  idx = i;
  remaining = queue[idx].duration_seconds;
  lastWhole = -1; lastBeepSecond = -1;
  endAt = Date.now() + remaining * 1000;
  stopTick();
  renderCurrent();
  tickId = setInterval(tick, 200);
}

document.getElementById('timer-start').addEventListener('click', async () => {
  const id = timerSelect.value;
  if (!id) { alert('Selecciona una rutina primero.'); return; }
  try {
    const data = await loadRoutine(id);
    queue = data.flat || [];
    if (!queue.length) { alert('Esta rutina no tiene elementos. Agrega ejercicios en la pestaña Crear rutinas.'); return; }
    totalRoutine = data.total_seconds || queue.reduce((a, b) => a + b.duration_seconds, 0);
    paused = false;
    document.getElementById('timer-pause').textContent = '⏸ Pausar';
    showRun();
    startElement(0);
  } catch (e) { alert(e.message); }
});

document.getElementById('timer-pause').addEventListener('click', (e) => {
  if (!queue.length) return;
  paused = !paused;
  e.target.textContent = paused ? '▶ Reanudar' : '⏸ Pausar';
  if (!paused) endAt = Date.now() + remaining * 1000;
});

document.getElementById('timer-stop').addEventListener('click', () => { stopTick(); queue = []; showIdle(); });
document.getElementById('timer-restart').addEventListener('click', () => {
  if (!queue.length) return;
  paused = false; document.getElementById('timer-pause').textContent = '⏸ Pausar';
  showRun(); startElement(0);
});
document.getElementById('timer-again').addEventListener('click', () => {
  if (!queue.length) { showIdle(); return; }
  paused = false; document.getElementById('timer-pause').textContent = '⏸ Pausar';
  showRun(); startElement(0);
});
document.getElementById('timer-back').addEventListener('click', () => { stopTick(); queue = []; showIdle(); });
document.getElementById('timer-nextbtn').addEventListener('click', () => {
  if (!queue.length) return;
  if (idx + 1 >= queue.length) { showDone(); return; }
  startElement(idx + 1);
});
document.getElementById('timer-prev').addEventListener('click', () => {
  if (!queue.length) return;
  startElement(Math.max(0, idx - 1));
});

// ---------- Pantalla completa del timer (solo se ve el timer) ----------
const timerFs = document.getElementById('timer-fs');
const timerFsBtn = document.getElementById('timer-fs-btn');
function isFs() { return !!(document.fullscreenElement || document.webkitFullscreenElement); }
async function enterTimerFs() {
  if (!queue.length) {
    alert('Inicia una rutina primero para usar la pantalla completa.');
    return;
  }
  try {
    if (timerFs.requestFullscreen) await timerFs.requestFullscreen();
    else if (timerFs.webkitRequestFullscreen) timerFs.webkitRequestFullscreen();
  } catch(e) {}
}
function exitTimerFs() {
  try {
    if (isFs()) {
      if (document.exitFullscreen) document.exitFullscreen();
      else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
    }
  } catch(e) {}
}
if (timerFsBtn) timerFsBtn.addEventListener('click', () => { isFs() ? exitTimerFs() : enterTimerFs(); });
['timer-fs-exit', 'timer-fs-exit2'].forEach(id => {
  const b = document.getElementById(id);
  if (b) b.addEventListener('click', exitTimerFs);
});
document.addEventListener('fullscreenchange', () => {
  if (timerFsBtn) timerFsBtn.textContent = isFs() ? '⛶ Salir de pantalla completa' : '⛶ Pantalla completa';
});

// ---------- Pausa automática: el timer no sigue corriendo fuera del Timer ----------
function pauseTimerAuto() {
  if (queue.length && tickId && !paused) {
    paused = true;
    const pb = document.getElementById('timer-pause');
    if (pb) pb.textContent = '▶ Reanudar';
  }
}
// Al cambiar a otra pestaña del dashboard
document.addEventListener('dashboard-tab', (e) => {
  if (e.detail !== 'timer') pauseTimerAuto();
});
// Al ocultar la pestaña del navegador
document.addEventListener('visibilitychange', () => {
  if (document.hidden) pauseTimerAuto();
});
</script>
@endsection
