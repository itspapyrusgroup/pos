<?php

namespace App\Console\Commands;

use App\Services\Sync\SyncPushService;
use Illuminate\Console\Command;

class SyncPushCloudCommand extends Command
{
    protected $signature = 'sync:push-cloud';
    protected $description = 'Push incremental transactional changes from branch node to cloud node.';

    public function handle(SyncPushService $syncPushService): int
    {
        $result = $syncPushService->pushToCloud('scheduler');
        if (!($result['ok'] ?? false)) {
            $this->warn((string) ($result['message'] ?? 'Sinkronisasi gagal'));
            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Sync sukses. Rows=%d, datasets=%s',
            (int) ($result['sent_rows'] ?? 0),
            implode(',', (array) ($result['datasets'] ?? []))
        ));

        return self::SUCCESS;
    }
}
