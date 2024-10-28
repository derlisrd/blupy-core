<?php

use App\Jobs\CumpleaniosJobs;
use App\Jobs\ProcesarVentasDelDiaFarmaJobs;
use App\Jobs\UpdateSolicitudesJobs;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schedule;


Schedule::job(new UpdateSolicitudesJobs());
Schedule::job(new ProcesarVentasDelDiaFarmaJobs(Carbon::yesterday()->format('Y-m-d')));
Schedule::job(new CumpleaniosJobs());
//->dailyAt('00:40');
//->timezone('America/Asuncion')
