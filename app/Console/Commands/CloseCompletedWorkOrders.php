<?php

namespace App\Console\Commands;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Services\WorkOrderClosingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cierra económicamente las OT completadas que no tienen cierre (por ejemplo,
 * las completadas antes de la FASE 14). Es idempotente.
 */
class CloseCompletedWorkOrders extends Command
{
    protected $signature = 'work-orders:close-completed';

    protected $description = 'Genera el cierre económico de las OT completadas que todavía no lo tienen';

    public function handle(WorkOrderClosingService $closingService): int
    {
        $count = 0;

        WorkOrder::where('status', WorkOrderStatus::COMPLETED)
            ->whereDoesntHave('closing')
            ->orderBy('id')
            ->each(function (WorkOrder $workOrder) use ($closingService, &$count) {
                DB::transaction(fn () => $closingService->close($workOrder, null));
                $count++;
            });

        $this->info("OT cerradas: {$count}.");

        return self::SUCCESS;
    }
}
