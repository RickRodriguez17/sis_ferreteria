<?php

namespace App\Notifications;

use App\Models\Credit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CreditDueNotification extends Notification
{
    use Queueable;

    public function __construct(public Credit $credit) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        // La versión actual solo prepara notificaciones internas persistidas.
        // Los canales mail o WhatsApp podrán agregarse aquí cuando exista una decisión de integración.
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'credit_id' => $this->credit->id,
            'message' => 'Un crédito requiere atención por vencimiento.',
            'days_overdue' => $this->credit->days_overdue,
        ];
    }
}
