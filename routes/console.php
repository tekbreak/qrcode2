<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('analytics:aggregate')->everyFifteenMinutes();
