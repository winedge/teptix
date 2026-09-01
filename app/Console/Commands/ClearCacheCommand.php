<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearCacheCommand extends Command
{
    protected $signature = 'cron:clear-cache';
    protected $description = 'Clear all Laravel caches (app, route, config, view)';

    public function handle()
    {
        // Run silently (no log, no output)
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        
        // No echo, no log
    }
}