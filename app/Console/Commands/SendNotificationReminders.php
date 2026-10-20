<?php

namespace App\Console\Commands;

use App\Services\NotificationReminderService;
use Illuminate\Console\Command;

class SendNotificationReminders extends Command
{
    protected $signature = 'notifications:send-reminders';

    protected $description = 'Registra los recordatorios diarios: facturas por vencer o vencidas, mantenimientos y cuentas por pagar vencidas';

    public function handle(NotificationReminderService $reminders): int
    {
        $result = $reminders->run();

        $this->info("Facturas: {$result['invoices']} aviso(s).");
        $this->info("Mantenimientos: {$result['maintenances']} aviso(s).");
        $this->info("Cuentas por pagar: {$result['accounts_payable']} aviso(s) interno(s).");

        return self::SUCCESS;
    }
}
