<?php

namespace App\Console\Commands;

use App\Services\Sync\SyncBootstrapPullService;
use Illuminate\Console\Command;

class SyncBootstrapPullCommand extends Command
{
    protected $signature = 'sync:bootstrap-pull {--cabang_id= : Cabang ID untuk filter master branch-scoped} {--mode= : incremental|bootstrap}';
    protected $description = 'Pull master data from cloud to local branch node.';

    public function handle(SyncBootstrapPullService $bootstrapPullService): int
    {
        $cabangId = (int) ($this->option('cabang_id') ?: 0);
        $mode = trim((string) ($this->option('mode') ?: config('sync.pull_master_mode', 'incremental')));
        $result = $bootstrapPullService->pullMasterFromCloud($cabangId > 0 ? $cabangId : null, 'cli', $mode);
        if (!($result['ok'] ?? false)) {
            $this->error((string) ($result['message'] ?? 'Pull master gagal.'));
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Pull master sukses. mode=%s Applied rows=%d. Datasets=%s',
            strtolower($mode),
            (int) ($result['applied_rows'] ?? 0),
            implode(',', (array) ($result['datasets'] ?? []))
        ));
        return self::SUCCESS;
    }
}
