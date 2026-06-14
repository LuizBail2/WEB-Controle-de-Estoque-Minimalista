<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

//notifica o admin por e-mail quando um funcionario cria/edita/exclui um registro. Ações do próprio admin não vão geram log.

trait LogsTeamActivity
{
    protected static function bootLogsTeamActivity(): void
    {
        static::created(function ($model) { $model->logTeamActivity('criou'); });
        static::updated(function ($model) {
            //alteração só do estoque, vinda de uma movimentação,
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
            return; //só registram ações de funcionários
        }

        $subject = $this->activitySubject();
        $name    = $this->activityName();
        $desc    = trim("{$user->name} {$action} {$subject}" . ($name !== '' ? ": {$name}" : ''));

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
                Mail::raw($desc, function ($msg) use ($owner) {
                    $msg->to($owner->email)->subject('StockPro — atividade da equipe');
                });
            }
        } catch (\Throwable $e) {
            //Log/e-mail nunca pode quebra a ação do usuário
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
