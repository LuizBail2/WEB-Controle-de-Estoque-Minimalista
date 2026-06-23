# Regras de negócio — Nexo Estoque

Este arquivo registra as regras que **não são óbvias pelo código** e o **porquê**
de cada decisão. Serve para quem (você ou outro dev) for mexer no sistema depois e
não lembrar por que ele se comporta de determinado jeito.

> Para instalação e uso, veja o `README.md`. Aqui é só a lógica.

---

## 1. Lotes e estoque (controle de validade)

### A relação entre quantidade e lotes
Cada produto tem uma **quantidade em estoque** (campo `quantity`). Opcionalmente,
parte ou todo esse estoque pode estar registrado em **lotes** (`batches`), cada um
com validade. O modelo adotado é o **flexível** (nem todo produto precisa de lote —
um Arduino não vence; um item perecível sim).

### Regra: a soma dos lotes nunca passa do estoque
Ao cadastrar ou editar um produto com lote:
- A soma das quantidades dos lotes **não pode ultrapassar** a quantidade em estoque.
- Ex.: estoque 50 → os lotes podem somar no máximo 50. Tentar registrar um lote de
  80 num produto de estoque 50 é **bloqueado**.
- Pode haver "estoque sem lote" (ex.: estoque 50, lote 30 → 20 unidades sem
  rastreamento de validade). Isso é **proposital e válido**.

**Por quê:** rastrear validade só faz sentido onde importa. Obrigar lote em tudo
geraria dados falsos (lote inventado para produto que não vence). A regra garante
coerência *quando* o lote existe, sem engessar o que não precisa.

### Regra: não reduzir o estoque abaixo do que está em lotes
Ao **editar** a quantidade de um produto para um valor menor que a soma dos lotes
já existentes, o sistema **bloqueia** o save.
- Ex.: produto com 2 lotes somando 20; tentar baixar a quantidade para 5 é
  bloqueado. É preciso ajustar os lotes (em Validades) antes.

**Por quê:** se você rastreia 20 unidades em lotes, o estoque não pode dizer 5 —
seria uma contradição. O bloqueio força os números a baterem.

### Onde isso está
`app/Http/Controllers/ProductController.php` — métodos `store()` e `update()`,
auxiliar `ensureBatchFitsStock()`. A validação é de **aplicação** (controller),
não trava de banco: criação de lote por fora (seed/import) não é validada.

---

## 2. Saída de estoque — FEFO (vence primeiro, sai primeiro)

Quando há saída (saída, transferência, ou devolução ao fornecedor) de um produto
que tem lotes:
- Se o usuário **não informar** lote: o sistema baixa automaticamente do lote que
  **vence primeiro** (`expiry_date` mais antigo). Se um lote não cobre a
  quantidade, ele "rola" para o próximo mais antigo, e assim por diante.
- Se o usuário **informar** um lote: baixa daquele lote primeiro; o que faltar é
  completado pelos demais por ordem de validade.

**Por quê:** FEFO (First Expired, First Out) reduz perda por vencimento — escoa o
que está mais perto de vencer antes do resto.

### Onde isso está
`app/Http/Controllers/MovementController.php` — método `consumeBatchesFefo()`.

---

## 3. Fluxo de aprovação (funcionário × admin)

- **Transferências** e **devoluções** criadas por um **funcionário** entram como
  `pending_approval` e **não mexem no estoque** até o admin aprovar.
- **Pedidos de compra** de funcionário também aguardam aprovação para liberar
  entrada de estoque.
- O admin aprova/rejeita em **Perfil**. Ao aprovar, a movimentação é aplicada ao
  estoque **naquele momento** (o estoque é revalidado — pode ter mudado entre a
  solicitação e a aprovação).

**Por quê:** o admin mantém controle sobre o que altera o estoque, sem travar o
trabalho do funcionário (ele registra a solicitação na hora).

### Onde isso está
`MovementController` (`store`, `approve`, `reject`, `applyApprovedMovement`).

---

## 4. Isolamento entre empresas (multi-tenant)

Cada empresa só enxerga os próprios dados. Um usuário de uma empresa **não acessa**
produtos, movimentações, lotes, financeiro nem relatórios de outra — nem pela tela,
nem digitando a URL/ID de um registro de outra empresa (retorna 404).

**Por quê:** o sistema é multiempresa; vazar dado de uma para outra é falha grave.

### Onde isso está
Trait `app/Models/Concerns/BelongsToUser.php`, aplicada nos models (Product,
Movement, Category, Supplier, PurchaseOrder, Batch, Payable, Receivable). Ela filtra
automaticamente por empresa (via `owner_id` do usuário) em toda consulta.

---

## 5. E-mail do funcionário mascarado (perfil do admin)

No perfil do admin, o e-mail de cada funcionário aparece **mascarado**
(ex.: `lu********@gmail.com`) com um botão "Editar" que revela o campo para alterar.

**Importante — é máscara VISUAL, não proteção de dados:**
- O e-mail completo está no HTML (no input oculto). Quem abrir o "inspecionar
  elemento" do navegador consegue vê-lo.
- Protege contra **olhar casual na tela** (alguém por cima do ombro), não contra
  acesso técnico. Não é criptografia nem anonimização.
- Ao salvar, vai o e-mail real (não o mascarado) — não corrompe o dado.

### Onde isso está
`resources/views/profile/edit.blade.php` (campo do funcionário) e o helper
`app/Support/Mask.php` (`Mask::email()`).

---

## 6. Tema claro/escuro

O sistema tem dois temas, controlados por `[data-theme="dark"|"light"]` e variáveis
CSS `--t-*` definidas em `public/css/dashboard-pro.css`.

**Regra de manutenção:** ao criar telas ou componentes, **use as variáveis `--t-*`**
(ex.: `var(--t-panel)`, `var(--t-text)`) em vez de cores fixas ou classes Bootstrap
de cor fixa (`bg-dark`, `table-dark`, `text-light`). Cores fixas funcionam num tema
e quebram no outro, exigindo remendo depois.

> O Financeiro foi migrado para esse padrão (classes `fin-*` em
> `resources/views/finance/_styles.blade.php`).

---

## Histórico
Documento criado para registrar regras definidas durante o desenvolvimento.
Atualize-o sempre que uma nova regra de negócio não-óbvia for adicionada.
