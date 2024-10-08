<?php

use App\Jobs\UpdateSolicitudesJobs;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new UpdateSolicitudesJobs())
->timezone('America/Asuncion')->dailyAt('03:00');
