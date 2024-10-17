<?php

use App\Jobs\ProcesarVentasDelDiaFarmaJobs;
use App\Jobs\UpdateSolicitudesJobs;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new UpdateSolicitudesJobs());
Schedule::job(ProcesarVentasDelDiaFarmaJobs::dispatch(Carbon::yesterday()->format('Y-m-d')));

//->timezone('America/Asuncion')
//->dailyAt('03:00');
