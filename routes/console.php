<?php

use App\Models\ChatMessage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Console Command: Purge old chat messages and physical files to conserve server storage.
 * Usage: php artisan chat:prune --hours=24 (or --days=7, or --all)
 */
Artisan::command('chat:prune {--hours=24 : Cutoff in hours} {--days= : Cutoff in days} {--all : Purge all messages}', function () {
    $isAll = (bool) $this->option('all');
    $days = $this->option('days');
    $hours = $this->option('hours');

    if ($isAll) {
        $cutoff = now();
        $label = 'all messages';
    } elseif ($days) {
        $cutoff = now()->subDays((int) $days);
        $label = "older than {$days} days";
    } else {
        $cutoff = now()->subHours((int) $hours);
        $label = "older than {$hours} hours";
    }

    $query = ChatMessage::where('created_at', '<=', $cutoff);

    // Delete attachment files from storage
    $attachments = (clone $query)->whereNotNull('attachment_path')->pluck('attachment_path');
    $freedFiles = 0;
    foreach ($attachments as $filePath) {
        $fullPath = public_path($filePath);
        if (File::exists($fullPath)) {
            @unlink($fullPath);
            $freedFiles++;
        }
    }

    $deletedCount = $query->delete();
    $this->info("Successfully pruned {$deletedCount} chat messages and deleted {$freedFiles} attachment files ({$label}).");
})->purpose('Purge old chat messages and attachments to conserve storage');

// Daily auto-cleanup schedule: run daily at 2:00 AM to keep disk usage lean
Schedule::command('chat:prune --days=30')->daily()->at('02:00');

