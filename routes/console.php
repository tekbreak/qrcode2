<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('credits:reset')->hourly();
Schedule::command('analytics:aggregate')->everyFifteenMinutes();
