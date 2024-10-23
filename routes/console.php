<?php

use App\Jobs\ProcesarVentasDelDiaFarmaJobs;
use App\Jobs\UpdateSolicitudesJobs;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schedule;

Schedule::job(UpdateSolicitudesJobs::dispatch());
Schedule::job(ProcesarVentasDelDiaFarmaJobs::dispatch(Carbon::yesterday()->format('Y-m-d')));
//->dailyAt('00:40');

//->timezone('America/Asuncion')
