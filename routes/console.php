<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('recurring:generate')->dailyAt('05:00')->withoutOverlapping();
Schedule::command('jobs:remind')->dailyAt('07:00')->withoutOverlapping();
