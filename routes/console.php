<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('analytics:aggregate')->everyFifteenMinutes();
Schedule::command('subscriptions:sync')->dailyAt('03:00');
