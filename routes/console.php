<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('recurring:generate')->dailyAt('05:00')->withoutOverlapping();
