<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use App\Models\User;
use App\Mail\TeamActivityMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

// Notifica o admin por e-mail quando um funcionário cria/edita/exclui um registro.
trait LogsTeamActivity
{
    protected static function bootLogsTeamActivity(): void
    {
        static::created(function ($model) { $model->logTeamActivity('criou'); });
        static::updated(function ($model) {
            // Alteração só do estoque, vinda de uma movimentação, não gera log.
            $changed = array_keys($model->getChanges());
            $ignore  = ['quantity', 'updated_at'];
            if (count(array_diff($changed, $ignore)) === 0) {
                return;
            }
            $model->logTeamActivity('editou');
        });
        static::deleted(function ($model) { $model->logTeamActivity('excluiu'); });
    }

    public function logTeamActivity(string $action): void
    {
        if (!Auth::check()) {
            return;
        }
        $user = Auth::user();
        if ($user->isAdmin()) {
            return; // Só registramos ações de funcionários.
        }

        $subject = $this->activitySubject();
        // Se o model define um nome de exibição customizado (ex.: Movement usa o
        // tipo "Saída" em vez do id), usa ele; senão cai no padrão genérico.
        $name = method_exists($this, 'activityDisplayName')
            ? $this->activityDisplayName()
            : $this->activityName();
        $desc = trim("{$user->name} {$action} {$subject}" . ($name !== '' ? ": {$name}" : ''));

        try {
            ActivityLog::create([
                'owner_id'    => $user->ownerId(),
                'actor_id'    => $user->id,
                'action'      => $action,
                'subject'     => $subject,
                'description' => mb_substr($desc, 0, 255),
            ]);

            $owner = User::find($user->ownerId());
            if ($owner && !empty($owner->email)) {
                // Pergunta ao próprio model se ele tem dados ricos para o e-mail.
                // Movement responde com os campos detalhados; os demais retornam null.
                $details = method_exists($this, 'activityMailDetails')
                    ? $this->activityMailDetails()
                    : null;

                Mail::to($owner->email)->send(new TeamActivityMail(
                    actorName:    $user->name,
                    action:       $action,
                    subjectLabel: $subject,
                    itemName:     $name,
                    details:      $details,
                    model:        $this
                ));
            }
        } catch (\Throwable $e) {
            // Log/e-mail nunca pode quebrar a ação do usuário.
            report($e);
        }
    }

    protected function activitySubject(): string
    {
        return match (class_basename($this)) {
            'Product'       => 'um produto',
            'Movement'      => 'uma movimentação',
            'Batch'         => 'um lote',
            'Payable'       => 'uma conta a pagar',
            'Receivable'    => 'uma conta a receber',
            'PurchaseOrder' => 'um pedido de compra',
            'Supplier'      => 'um fornecedor',
            'Category'      => 'uma categoria',
            default         => 'um registro',
        };
    }

    protected function activityName(): string
    {
        foreach (['name', 'description', 'lote', 'code', 'title'] as $attr) {
            if (!empty($this->{$attr})) {
                return (string) $this->{$attr};
            }
        }
        return '#' . $this->getKey();
    }
}
