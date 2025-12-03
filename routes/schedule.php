<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('update:tenant-status')
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/tenant_status.log'));
