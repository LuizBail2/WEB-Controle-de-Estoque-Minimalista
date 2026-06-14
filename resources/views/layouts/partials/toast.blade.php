@php
    $flashType = session('error') ? 'error' : (session('warning') ? 'warning' : ((session('success') || session('status')) ? 'success' : ($errors->any() ? 'error' : null)));
    $flashMsg  = session('error') ?? session('warning') ?? session('success') ?? session('status') ?? ($errors->any() ? $errors->first() : null);
@endphp

@if($flashMsg)
<div id="flashToast" class="flash-toast flash-{{ $flashType }}" role="status" aria-live="polite">
    <span class="flash-ico">{{ $flashType === 'error' ? '⚠️' : ($flashType === 'warning' ? '🔔' : '✅') }}</span>
    <span class="flash-msg">{{ $flashMsg }}</span>
    <button type="button" class="flash-close" aria-label="Fechar" onclick="this.closest('.flash-toast').remove()">×</button>
</div>

<style>
  .flash-toast{
    position: fixed; top: 18px; right: 18px; z-index: 4000;
    display: flex; align-items: center; gap: 10px;
    max-width: 360px; padding: 12px 14px;
    background: var(--panel, #111827);
    color: var(--text, #e5e7eb);
    border: 1px solid var(--border, rgba(255,255,255,.12));
    border-left: 4px solid var(--green, #22c55e);
    border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.45);
    font-size: .92rem; animation: flashIn .25s ease;
  }
  .flash-toast.flash-error{ border-left-color: var(--red, #ef4444); }
  .flash-toast.flash-warning{ border-left-color: var(--yellow, #ffb700); }
  .flash-ico{ font-size: 1.05rem; }
  .flash-msg{ flex: 1; }
  .flash-close{
    background: transparent; border: 0; color: var(--muted, #94a3b8);
    font-size: 1.2rem; line-height: 1; cursor: pointer; padding: 0 2px;
  }
  .flash-close:hover{ color: var(--text, #e5e7eb); }
  .flash-toast.hide{ animation: flashOut .3s ease forwards; }
  @keyframes flashIn{ from{ opacity:0; transform: translateX(20px); } to{ opacity:1; transform:none; } }
  @keyframes flashOut{ to{ opacity:0; transform: translateX(20px); } }
</style>

<script>
  (function(){
    var t = document.getElementById('flashToast');
    if(!t) return;
    setTimeout(function(){
      t.classList.add('hide');
      setTimeout(function(){ t.remove(); }, 300);
    }, 4000);
  })();
</script>
@endif
