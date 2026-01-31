<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DailyRecap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-recap';
    protected $description = 'Generate daily groundcheck recap';

    public function handle()
    {
        $date = now()->format('Y-m-d');
        $logs = \App\Models\GroundcheckLog::whereDate('created_at', $date)->get();

        if ($logs->isEmpty()) {
            $this->info("No activity today ($date).");
            return;
        }

        $byUser = $logs->groupBy('user_id');
        $msg = "Laporan Groundcheck Tgl $date (16:30):\n";

        foreach ($byUser as $userId => $userLogs) {
            $count = $userLogs->count();
            // Assuming user_id 0 is System/Guest, or real users
            // $user = \App\Models\User::find($userId);
            // $name = $user ? $user->name : "Guest ($userId)";
            $name = "User $userId";
            $msg .= "- $name: $count titik updated.\n";
        }

        $this->info($msg);
        // Here you would send $msg to WhatsApp/Telegram Bot
    }
}
