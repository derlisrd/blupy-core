<?php

namespace App\Http\Controllers;

use App\Services\FarmaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    public function test(){
        $farma = new FarmaService();

        $res = $farma->cliente('4937724');

        $resData = $res['data'];


        return response()->json([$resData]);
    }
}
