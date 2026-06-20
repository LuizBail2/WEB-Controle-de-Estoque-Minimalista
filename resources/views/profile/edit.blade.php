@extends('layouts.app')

@section('title', 'Meu Perfil')

@section('content')
<div class="reports-dark pf">

    <div class="pf-header">
        <div>
            <h3 class="pf-title">Meu Perfil</h3>
            <p class="pf-sub">Edite seus dados de conta e sua senha</p>
        </div>
    </div>

    @if(session('success'))
        <div class="pf-alert pf-alert--ok">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="pf-alert pf-alert--err">⚠ {{ session('error') }}</div>
    @endif

    <div class="pf-grid">
        {{-- Dados da conta --}}
        <div class="pf-card">
            <div class="pf-card__title">Dados da conta</div>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')
                <div class="pf-field">
                    <label>Nome</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name') <span class="pf-err">{{ $message }}</span> @enderror
                </div>
                <div class="pf-field">
                    <label>E-mail</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email') <span class="pf-err">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn-primary">Salvar dados</button>
            </form>
        </div>

        {{-- Alterar senha --}}
        <div class="pf-card">
            <div class="pf-card__title">Alterar senha</div>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')
                <div class="pf-field">
                    <label>Senha atual</label>
                    <input type="password" name="current_password" autocomplete="current-password">
                    @error('current_password') <span class="pf-err">{{ $message }}</span> @enderror
                </div>
                <div class="pf-field">
                    <label>Nova senha</label>
                    <input type="password" name="password" autocomplete="new-password">
                    @error('password') <span class="pf-err">{{ $message }}</span> @enderror
                </div>
                <div class="pf-field">
                    <label>Confirmar nova senha</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary">Alterar senha</button>
            </form>
        </div>
    </div>

    @if($user->isAdmin() && $pendingSignups->count())
    <div class="pf-card pf-pending">
        <div class="pf-card__title">🙋 Novos cadastros aguardando aprovação <span class="pf-act-badge">{{ $pendingSignups->count() }}</span></div>
        <p class="pf-team__hint">Pessoas que se cadastraram e querem entrar na equipe. Aprove para liberar o acesso (depois você define as abas na seção Equipe). Ao aprovar ou recusar, a pessoa é avisada por e-mail.</p>
        @foreach($pendingSignups as $su)
            <div class="pf-po">
                <div class="pf-po__info">
                    <div class="pf-po__code">{{ $su->name }}</div>
                    <div class="pf-act__meta">{{ $su->email }} · cadastrou-se {{ $su->created_at->diffForHumans() }}</div>
                </div>
                <div class="pf-po__actions">
                    <form method="POST" action="{{ route('profile.signups.approve', $su) }}" style="margin:0;"
                          data-confirm data-confirm-kind="approve"
                          data-confirm-title="Aprovar cadastro?"
                          data-confirm-text="Liberar o acesso de {{ $su->name }}? A pessoa será avisada por e-mail."
                          data-confirm-btn="Aprovar">@csrf
                        <button type="submit" class="btn btn-success pf-act-readbtn">✓ Aprovar</button>
                    </form>
                    <form method="POST" action="{{ route('profile.signups.reject', $su) }}" style="margin:0;"
                          data-confirm data-confirm-kind="danger"
                          data-confirm-title="Recusar cadastro?"
                          data-confirm-text="Recusar o cadastro de {{ $su->name }}? O cadastro será removido."
                          data-confirm-btn="Recusar">@csrf
                        <button type="submit" class="btn pf-btn-del pf-act-readbtn">✕ Recusar</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    @if($user->isAdmin())
    <div class="pf-card pf-team">
        <div class="pf-card__title">👥 Equipe / Funcionários</div>
        <p class="pf-team__hint">Crie logins para funcionários e libere as abas que cada um pode acessar. Funcionários novos começam <strong>sem nenhuma aba liberada</strong> — marque abaixo o que cada um pode usar.</p>

        {{-- Criar funcionário --}}
        <form method="POST" action="{{ route('profile.employees.store') }}" class="pf-emp-form">
            @csrf
            <div class="pf-emp-grid">
                <div class="pf-field"><label>Nome</label><input type="text" name="name" value="{{ old('name') }}" required></div>
                <div class="pf-field"><label>E-mail</label><input type="email" name="email" value="{{ old('email') }}" required></div>
                <div class="pf-field"><label>Senha</label><input type="password" name="password" required></div>
            </div>
            @error('email') <span class="pf-err">{{ $message }}</span> @enderror
            @error('password') <span class="pf-err">{{ $message }}</span> @enderror
            <div class="pf-perms">
                <span class="pf-perms__lbl">Abas liberadas:</span>
                @foreach($abas as $key => $label)
                    <label class="pf-chk"><input type="checkbox" name="permissions[]" value="{{ $key }}"> {{ $label }}</label>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary">+ Criar funcionário</button>
        </form>

        {{-- Funcionários existentes --}}
        @forelse($employees as $emp)
            <div class="pf-emp">
                <form method="POST" action="{{ route('profile.employees.update', $emp) }}">
                    @csrf @method('PUT')
                    <div class="pf-emp-grid">
                        <div class="pf-field"><label>Nome</label><input type="text" name="name" value="{{ $emp->name }}" required></div>
                        <div class="pf-field"><label>E-mail</label><input type="email" name="email" value="{{ $emp->email }}" required></div>
                        <div class="pf-field"><label>Nova senha (opcional)</label><input type="password" name="password" placeholder="deixe em branco p/ manter"></div>
                    </div>
                    <div class="pf-perms">
                        <span class="pf-perms__lbl">Abas liberadas:</span>
                        @foreach($abas as $key => $label)
                            <label class="pf-chk"><input type="checkbox" name="permissions[]" value="{{ $key }}" {{ in_array($key, $emp->permissions ?? [], true) ? 'checked' : '' }}> {{ $label }}</label>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </form>
                <form method="POST" action="{{ route('profile.employees.destroy', $emp) }}" class="pf-emp-del"
                      data-confirm data-confirm-kind="danger"
                      data-confirm-title="Remover funcionário?"
                      data-confirm-text="Remover o funcionário {{ $emp->name }}? Esta ação não pode ser desfeita."
                      data-confirm-btn="Remover">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn pf-btn-del">🗑 Remover funcionário</button>
                </form>
            </div>
        @empty
            <p class="pf-team__empty">Nenhum funcionário cadastrado ainda.</p>
        @endforelse
    </div>
    @endif

    @if($user->isAdmin() && $pendingOrders->count())
    <div class="pf-card pf-pending">
        <div class="pf-card__title">📥 Pedidos aguardando sua aprovação <span class="pf-act-badge">{{ $pendingOrders->count() }}</span></div>
        <p class="pf-team__hint">Veja o PDF da solicitação e decida. Enquanto não aprovar, o funcionário não consegue dar entrada no estoque.</p>
        @foreach($pendingOrders as $po)
            <div class="pf-po">
                <div class="pf-po__info">
                    <div class="pf-po__code">{{ $po->code }} · {{ $po->supplier->name ?? '—' }}</div>
                    <div class="pf-act__meta">Solicitado por {{ optional($po->creator)->name ?? '—' }} · {{ $po->created_at->diffForHumans() }} · Total R$ {{ number_format($po->total, 2, ',', '.') }}</div>
                </div>
                <div class="pf-po__actions">
                    <a href="{{ route('purchase-orders.report', $po) }}" target="_blank" class="btn btn-secondary pf-act-readbtn">📄 PDF</a>
                    <form method="POST" action="{{ route('purchase-orders.approve', $po) }}" style="margin:0;"
                          data-confirm data-confirm-kind="approve"
                          data-confirm-title="Aprovar pedido?"
                          data-confirm-text="Aprovar o pedido {{ $po->code }}? O funcionário poderá dar entrada no estoque."
                          data-confirm-btn="Aprovar">@csrf
                        <button type="submit" class="btn btn-success pf-act-readbtn">✓ Aprovar</button>
                    </form>
                    <form method="POST" action="{{ route('purchase-orders.reject', $po) }}" style="margin:0;"
                          data-confirm data-confirm-kind="danger"
                          data-confirm-title="Rejeitar pedido?"
                          data-confirm-text="Rejeitar o pedido {{ $po->code }}?"
                          data-confirm-btn="Rejeitar">@csrf
                        <button type="submit" class="btn pf-btn-del pf-act-readbtn">✕ Rejeitar</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    @if($user->isAdmin() && $pendingMovements->count())
    <div class="pf-card pf-pending">
        <div class="pf-card__title">🔄 Movimentações aguardando sua aprovação <span class="pf-act-badge">{{ $pendingMovements->count() }}</span></div>
        <p class="pf-team__hint">Transferências e devoluções solicitadas por funcionários. O estoque <strong>só muda quando você aprovar</strong>.</p>
        @foreach($pendingMovements as $mv)
            <div class="pf-po">
                <div class="pf-po__info">
                    <div class="pf-po__code">
                        {{ $mv->type === 'transferencia' ? 'Transferência' : 'Devolução' }}
                        @if($mv->type === 'devolucao') ({{ $mv->direction === 'fornecedor' ? 'ao fornecedor' : 'de cliente' }}) @endif
                        · {{ optional($mv->product)->name ?? '—' }} · {{ $mv->quantity }} un.
                    </div>
                    <div class="pf-act__meta">
                        Solicitado por {{ optional($mv->creator)->name ?? '—' }} · {{ $mv->created_at->diffForHumans() }}
                        @if($mv->type === 'transferencia' && $mv->destination) · Destino: {{ $mv->destination }} @endif
                        @if($mv->type === 'devolucao' && $mv->reason) · Motivo: {{ $mv->reason }} @endif
                    </div>
                </div>
                <div class="pf-po__actions">
                    <a href="{{ route('movements.document', $mv) }}" target="_blank" class="btn btn-secondary pf-act-readbtn">📄 PDF</a>
                    <form method="POST" action="{{ route('movements.approve', $mv) }}" style="margin:0;"
                          data-confirm data-confirm-kind="approve"
                          data-confirm-title="Aprovar movimentação?"
                          data-confirm-text="Aprovar esta movimentação? O estoque será atualizado agora."
                          data-confirm-btn="Aprovar">@csrf
                        <button type="submit" class="btn btn-success pf-act-readbtn">✓ Aprovar</button>
                    </form>
                    <form method="POST" action="{{ route('movements.reject', $mv) }}" style="margin:0;"
                          data-confirm data-confirm-kind="danger"
                          data-confirm-title="Rejeitar movimentação?"
                          data-confirm-text="Rejeitar esta movimentação?"
                          data-confirm-btn="Rejeitar">@csrf
                        <button type="submit" class="btn pf-btn-del pf-act-readbtn">✕ Rejeitar</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <div class="pf-card pf-activity">
        <div class="pf-activity__head">
            <div class="pf-card__title" style="margin:0;">🔔 {{ $user->isAdmin() ? 'Atividade da equipe' : 'Minhas notificações' }} @if($unreadCount > 0)<span class="pf-act-badge">{{ $unreadCount }}</span>@endif</div>
            @if($unreadCount > 0)
            <form method="POST" action="{{ route('profile.notifications.read') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-secondary pf-act-readbtn">Marcar todas como lidas</button>
            </form>
            @endif
        </div>
        <p class="pf-team__hint">{{ $user->isAdmin() ? 'Tudo que seus funcionários criam, editam ou excluem aparece aqui (e é enviado pro seu e-mail).' : 'Avisos do administrador (aprovações e rejeições) aparecem aqui.' }}</p>

        @forelse($activities as $a)
            <div class="pf-act {{ is_null($a->read_at) ? 'pf-act--new' : '' }}">
                <div class="pf-act__dot"></div>
                <div class="pf-act__body">
                    <div class="pf-act__desc">{{ $a->description }}</div>
                    <div class="pf-act__meta">{{ optional($a->actor)->name ?? '—' }} · {{ $a->created_at->diffForHumans() }}</div>
                </div>
            </div>
        @empty
            <p class="pf-team__empty">Nenhuma notificação ainda.</p>
        @endforelse
    </div>
</div>

{{-- ===== MODAL DE CONFIRMAÇÃO (genérico, atende todos os forms com data-confirm) ===== --}}
<div class="cf-overlay" id="cfOverlay">
  <div class="cf-box">
    <div class="cf-ico" id="cfIco">?</div>
    <h5 class="cf-title" id="cfTitle">Confirmar ação?</h5>
    <p class="cf-text" id="cfText">Tem certeza que deseja continuar?</p>
    <div class="cf-actions">
      <button type="button" class="cf-btn cf-btn-ghost" id="cfCancel">Cancelar</button>
      <button type="button" class="cf-btn cf-btn-confirm" id="cfConfirm">Confirmar</button>
    </div>
  </div>
</div>

<style>
    .pf{ color: var(--t-text); }
    .pf-header{ margin-bottom:18px; }
    .pf-title{ margin:0; font-weight:800; } .pf-sub{ color: var(--t-muted); margin:4px 0 0; font-size:.9rem; }
    .pf-alert{ border-radius:10px; padding:10px 14px; margin-bottom:16px; font-size:.9rem; }
    .pf-alert--ok{ background:rgba(34,197,94,.12); border:1px solid rgba(34,197,94,.4); color:#22c55e; }
    .pf-alert--err{ background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.4); color:#ef4444; }
    .pf-grid{ display:grid; grid-template-columns:repeat(2, 1fr); gap:16px; }
    .pf-card{ background: var(--t-panel); border:1px solid var(--t-border); border-radius:16px; padding:20px; }
    .pf-card__title{ font-weight:700; margin-bottom:16px; font-size:1.02rem; }
    .pf-field{ margin-bottom:14px; display:flex; flex-direction:column; }
    .pf-field label{ font-size:.8rem; text-transform:uppercase; letter-spacing:.04em; color: var(--t-muted); margin-bottom:6px; }
    .pf-field input{ height:42px; padding:0 12px; border-radius:10px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); outline:none; }
    .pf-field input:focus{ border-color: var(--accent, #3b82f6); }
    .pf-err{ color:#ef4444; font-size:.8rem; margin-top:5px; }

    .pf-team{ margin-top:16px; }
    .pf-team__hint{ color:var(--t-muted); font-size:.88rem; margin-bottom:16px; }
    .pf-team__empty{ color:var(--t-muted); font-size:.9rem; }
    .pf-emp-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
    .pf-perms{ display:flex; flex-wrap:wrap; gap:8px 12px; align-items:center; margin:14px 0; }
    .pf-perms__lbl{ font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; color:var(--t-muted); width:100%; }
    .pf-chk{ display:inline-flex; align-items:center; gap:6px; font-size:.85rem; background:var(--t-panel-2); border:1px solid var(--t-input-border); padding:5px 10px; border-radius:8px; cursor:pointer; }
    .pf-chk input{ accent-color: var(--accent,#3b82f6); }
    .pf-emp-form{ padding-bottom:18px; border-bottom:1px solid var(--t-border); margin-bottom:18px; }
    .pf-emp{ padding:16px; border:1px solid var(--t-border); border-radius:12px; margin-bottom:14px; background:var(--t-panel-2); }
    .pf-emp-del{ margin-top:10px; }
    .pf-btn-del{ background:rgba(239,68,68,.14); color:#ef4444; border:1px solid rgba(239,68,68,.4); }
    @media (max-width:760px){ .pf-emp-grid{ grid-template-columns:1fr; } }

    .pf-activity{ margin-top:16px; }
    .pf-pending{ margin-top:16px; }
    .pf-po{ display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding:12px 0; border-bottom:1px solid var(--t-border-soft); }
    .pf-po:last-child{ border-bottom:0; }
    .pf-po__code{ font-weight:600; }
    .pf-po__actions{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .pf-activity__head{ display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .pf-act-badge{ display:inline-block; background:#ef4444; color:#fff; font-size:.72rem; font-weight:700; border-radius:999px; padding:1px 8px; margin-left:6px; vertical-align:middle; }
    .pf-act-readbtn{ font-size:.82rem; padding:6px 12px; }
    .pf-act{ display:flex; gap:10px; align-items:flex-start; padding:11px 0; border-bottom:1px solid var(--t-border-soft); }
    .pf-act:last-child{ border-bottom:0; }
    .pf-act__dot{ width:8px; height:8px; border-radius:50%; background:transparent; margin-top:6px; flex:0 0 auto; }
    .pf-act--new .pf-act__dot{ background:var(--accent,#3b82f6); }
    .pf-act--new{ background:rgba(59,130,246,.05); margin:0 -10px; padding-left:10px; padding-right:10px; border-radius:8px; }
    .pf-act__desc{ font-size:.9rem; }
    .pf-act__meta{ color:var(--t-muted); font-size:.76rem; margin-top:2px; }

    @media (max-width: 760px){ .pf-grid{ grid-template-columns:1fr; } }

    /* ===== Modal de confirmação ===== */
    .cf-overlay{ position:fixed; inset:0; background:rgba(8,12,22,.55); -webkit-backdrop-filter:blur(4px); backdrop-filter:blur(4px); display:none; align-items:center; justify-content:center; z-index:3000; }
    .cf-overlay.open{ display:flex; }
    .cf-box{ background:var(--t-panel-2, #0b1220); border:1px solid var(--t-border, rgba(255,255,255,.12)); border-radius:18px; padding:26px 24px; max-width:420px; width:92%; text-align:center; color:var(--t-text, #e5e7eb); box-shadow:0 30px 60px rgba(0,0,0,.5); }
    .cf-ico{ width:54px; height:54px; margin:0 auto 12px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:26px; font-weight:800; }
    .cf-title{ font-weight:800; margin:0 0 8px; font-size:1.05rem; }
    .cf-text{ color:var(--t-muted, #94a3b8); font-size:.9rem; margin:0 0 20px; line-height:1.5; }
    .cf-actions{ display:flex; gap:10px; justify-content:center; }
    .cf-btn{ padding:9px 18px; border-radius:12px; font-weight:700; font-size:.9rem; cursor:pointer; border:1px solid var(--t-input-border, rgba(255,255,255,.14)); transition:filter .15s ease; }
    .cf-btn:hover{ filter:brightness(1.08); }
    .cf-btn-ghost{ background:var(--t-panel, #111827); color:var(--t-text, #e5e7eb); }
    .cf-btn-confirm{ color:#fff; border:none; }
    .cf-overlay[data-kind="approve"] .cf-ico{ background:rgba(34,197,94,.18); color:#22c55e; }
    .cf-overlay[data-kind="approve"] .cf-btn-confirm{ background:#16a34a; }
    .cf-overlay[data-kind="danger"]  .cf-ico{ background:rgba(239,68,68,.18); color:#ef4444; }
    .cf-overlay[data-kind="danger"]  .cf-btn-confirm{ background:#dc2626; }
</style>

<script>
(function(){
  var overlay = document.getElementById('cfOverlay');
  var titleEl = document.getElementById('cfTitle');
  var textEl  = document.getElementById('cfText');
  var icoEl   = document.getElementById('cfIco');
  var btnOk   = document.getElementById('cfConfirm');
  var btnNo   = document.getElementById('cfCancel');
  var pendingForm = null;

  function close(){ overlay.classList.remove('open'); pendingForm = null; }

  function open(form){
    pendingForm = form;
    var kind  = form.dataset.confirmKind || 'danger';
    overlay.setAttribute('data-kind', kind);
    titleEl.textContent = form.dataset.confirmTitle || 'Confirmar ação?';
    textEl.textContent  = form.dataset.confirmText  || 'Tem certeza?';
    icoEl.textContent   = kind === 'approve' ? '✓' : '!';
    btnOk.textContent   = form.dataset.confirmBtn || 'Confirmar';
    overlay.classList.add('open');
  }

  document.querySelectorAll('form[data-confirm]').forEach(function(form){
    form.addEventListener('submit', function(e){
      if (form.dataset.confirmed === '1') return;
      e.preventDefault();
      open(form);
    });
  });

  btnOk.addEventListener('click', function(){
    if (pendingForm){ pendingForm.dataset.confirmed = '1'; pendingForm.submit(); }
  });
  btnNo.addEventListener('click', close);
  overlay.addEventListener('click', function(e){ if (e.target === overlay) close(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
})();
</script>
@endsection

