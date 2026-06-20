<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class TeamActivityMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $actorName;
    public string $action;
    public string $subjectLabel;
    public string $itemName;
    public string $headline;

    /** Dados ricos quando o registro é uma movimentação; null caso contrário. */
    public ?array $details;

    /** Model que disparou (usado só para gerar o PDF, quando aplicável). */
    protected $model;

    public function __construct(
        string $actorName,
        string $action,
        string $subjectLabel,
        string $itemName,
        ?array $details = null,
        $model = null
    ) {
        $this->actorName    = $actorName;
        $this->action       = $action;
        $this->subjectLabel = $subjectLabel;
        $this->itemName     = $itemName;
        $this->details      = $details;
        $this->model        = $model;
        $this->headline     = trim("{$actorName} {$action} {$subjectLabel}" . ($itemName !== '' ? ": {$itemName}" : ''));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nexo Estoque — atividade da equipe',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.team-activity',
        );
    }

    public function attachments(): array
    {
        // Anexa o PDF apenas para movimentações de transferência/devolução,
        // reaproveitando a mesma view do método document() do MovementController.
        if (
            $this->model
            && class_basename($this->model) === 'Movement'
            && in_array($this->model->type, ['transferencia', 'devolucao'], true)
        ) {
            $this->model->loadMissing('product', 'user');

            $pdf    = Pdf::loadView('movements.document', ['m' => $this->model]);
            $prefix = $this->model->type === 'transferencia' ? 'transferencia' : 'devolucao';

            return [
                Attachment::fromData(fn () => $pdf->output(), $prefix . '-' . $this->model->id . '.pdf')
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
