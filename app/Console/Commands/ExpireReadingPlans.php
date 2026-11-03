<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReadingPlan;

class ExpireReadingPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-reading-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '期日を過ぎた進行中の読書計画を期限切れに更新する';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = ReadingPlan::where('status', 'in_progress')
            ->whereDate('target_date', '<', today('Asia/Tokyo'))
            ->update([
                'status' => 'expired',
            ]);

        $this->info("期限切れに更新した読書計画：{$count}件");

        return self::SUCCESS;
    }
}

