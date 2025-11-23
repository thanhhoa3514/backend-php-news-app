<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;

Schedule::call(function () {
    DB::table('ai_generations')
        ->where('status', 'draft')
        ->where('created_at', '<', now()->subDays(30))
        ->delete();
})->daily();
