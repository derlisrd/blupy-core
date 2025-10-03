<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TigoSmsService
{
    private $url;
    private $key;
    public function __construct()
    {
        $this->url = env('TIGO_API_URL');
        $this->key = env('TIGO_API_KEY');
    }


    public function enviarSms(string $numero, string $texto){
        try {
           return Http::get($this->url . 'key='.$this->key.'&message='.$texto.'&msisdn='.$numero);
        } catch (\Throwable $th) {
            throw $th;
            //return [];
        }
    }
}
