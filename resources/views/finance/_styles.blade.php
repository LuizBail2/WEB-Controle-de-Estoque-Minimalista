{{-- ============================================================
     Estilo do Financeiro — usa as variáveis de tema (--t-*),
     funciona no claro e no escuro. Substitui as classes fixas
     do Bootstrap (bg-dark, table-dark, text-light...).
     Incluído em cada tela financeira via @include.
     ============================================================ --}}
<style>
  /* Card / painel */
  .fin-card{
    background: var(--t-panel);
    border: 1px solid var(--t-border);
    border-radius: 16px;
    color: var(--t-text);
  }
  .fin-card-head{
    padding: 14px 18px;
    border-bottom: 1px solid var(--t-border);
    background: var(--t-panel-2);
    border-radius: 16px 16px 0 0;
  }
  .fin-card-head h3{ margin:0; color: var(--t-text); font-weight:700; }
  .fin-card-body{ padding: 18px; }
  .fin-card-body.p0{ padding: 0; }

  /* KPIs do topo */
  .fin-kpi{
    background: var(--t-panel);
    border: 1px solid var(--t-border);
    border-radius: 14px;
    padding: 14px 16px;
    height: 100%;
  }
  .fin-kpi__label{
    color: var(--t-muted);
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-weight: 700;
    margin-bottom: 4px;
  }
  .fin-kpi__value{ font-size: 1.3rem; font-weight: 800; }

  /* Tabela */
  .fin-tbl{ width:100%; border-collapse: collapse; color: var(--t-text); }
  .fin-tbl thead th{
    background: var(--t-panel-2);
    color: var(--t-text-soft);
    text-align: left;
    padding: 12px 16px;
    font-size: .8rem;
    font-weight: 700;
    border-bottom: 1px solid var(--t-border);
  }
  .fin-tbl tbody td{
    padding: 12px 16px;
    border-bottom: 1px solid var(--t-border-soft);
    color: var(--t-text);
  }
  .fin-tbl tbody tr:hover td{ background: var(--t-hover); }
  .fin-tbl .t-end{ text-align: right; }
  .fin-muted{ color: var(--t-muted); }

  /* Inputs */
  .fin-input{
    background: var(--t-input-bg);
    color: var(--t-text);
    border: 1px solid var(--t-input-border);
    border-radius: 10px;
    height: 42px;
    padding: 0 12px;
    width: 100%;
    outline: none;
  }
  .fin-input::placeholder{ color: var(--t-muted); }
  .fin-input:focus{ border-color: var(--accent, #3b82f6); }
  .fin-label{ font-size:.78rem; color: var(--t-muted); font-weight:600; margin-bottom:4px; display:block; }

  /* Botões de navegação / filtro */
  .fin-btn-nav{
    display:inline-flex; align-items:center; gap:6px;
    padding: 6px 14px; border-radius: 10px;
    border: 1px solid var(--t-input-border);
    background: var(--t-panel);
    color: var(--t-text);
    text-decoration:none; font-size:.85rem; font-weight:600;
  }
  .fin-btn-nav:hover{ background: var(--t-hover); color: var(--t-text); }
  .fin-btn-nav.is-active{ background:#2563eb; border-color:#1d4ed8; color:#fff; }

  /* Cores de valor (mantêm semântica nos 2 temas) */
  .fin-pos{ color:#16a34a; }
  .fin-neg{ color:#dc2626; }
  .fin-info{ color:#2563eb; }
  .fin-warn{ color:#b45309; }
  [data-theme="dark"] .fin-pos{ color:#22c55e; }
  [data-theme="dark"] .fin-neg{ color:#ef4444; }
  [data-theme="dark"] .fin-info{ color:#38bdf8; }
  [data-theme="dark"] .fin-warn{ color:#fbbf24; }

  /* Coluna do olho (mobile) */
  .fin-eye{ display:none; }
  .fin-eye-btn{ width:34px; height:34px; border-radius:9px; background: var(--t-panel-2); border:1px solid var(--t-input-border); color: var(--t-text); cursor:pointer; font-size:14px; }

  @media (max-width: 768px){
    .fin-tbl thead{ display:none; }
    .fin-tbl, .fin-tbl tbody{ display:block; width:100%; }
    .fin-tbl tr{
      display:flex; flex-wrap:wrap; align-items:center; gap:6px 10px;
      background: var(--t-panel-2);
      border:1px solid var(--t-border);
      border-radius:12px; padding:12px 14px; margin-bottom:10px;
    }
    .fin-tbl td{ border:0 !important; padding:0 !important; background:transparent !important; }
    .fin-tbl td.fin-c-prod{ order:1; flex:1 1 auto; font-weight:600; text-align:left !important; }
    .fin-tbl td.fin-c-stock{ order:2; text-align:right !important; font-weight:600; }
    .fin-tbl td.fin-c-stock::before{ content:'Estoque: '; color:var(--t-muted); font-weight:400; font-size:.76rem; }
    .fin-tbl td.fin-eye{ order:3; display:flex; }
    .fin-tbl td.fin-c-extra{
      display:none; order:4; flex:1 1 100%;
      justify-content:space-between; align-items:center; text-align:right !important;
      padding-top:8px !important; border-top:1px solid var(--t-border-soft) !important;
    }
    .fin-tbl td.fin-c-extra::before{
      content:attr(data-label); color:var(--t-muted);
      font-size:.74rem; text-transform:uppercase; letter-spacing:.03em; font-weight:600;
    }
    .fin-tbl tr.is-open td.fin-c-extra{ display:flex; }
    .fin-tbl tr.is-open .fin-eye-btn{ background: rgba(59,130,246,.18); border-color: var(--accent, #3b82f6); }

    /* responsividade das tabelas simples (cashflow / contas) */
    .fin-tbl.simple td{ display:flex; justify-content:space-between; align-items:center; gap:14px; padding:9px 0 !important; text-align:right !important; }
    .fin-tbl.simple td::before{ content:attr(data-label); color:var(--t-muted); font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; font-weight:600; text-align:left; }
  }
</style>
