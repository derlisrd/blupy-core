<?php

namespace App\Traits;

trait Helpers
{
    public function distancia($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return ($miles * 1.609344);
    }


    public function separarNombres(String $cadena) : Array{
        $nombresArray = explode(' ', $cadena);
        if (count($nombresArray) >= 2) {
            $nombre1 = $nombresArray[0];
            $nombre2 = implode(' ', array_slice($nombresArray, 1));
        } else {
            $nombre1 = $cadena;
            $nombre2 = '';
        }
        return [$nombre1,$nombre2];
    }

    function ocultarParcialmenteTelefono($phoneNumber) {
        if (strlen($phoneNumber) < 7) {
            return $phoneNumber;
        }
        $obfuscatedPhoneNumber = substr($phoneNumber, 0, 3) . str_repeat('*', strlen($phoneNumber) - 6) . substr($phoneNumber, -2);
        return $obfuscatedPhoneNumber;
    }

    public function ocultarParcialmenteEmail(string $email){
            $emailParts = explode('@', $email);
            $name = $emailParts[0];
            $domain = $emailParts[1];

            $obfuscatedName = substr($name, 0, 3) . str_repeat('*', strlen($name) - 3);

            $domainParts = explode('.', $domain);
            $domainName = substr($domainParts[0], 0, 3) . str_repeat('*', strlen($domainParts[0]) - 3);
            $domainExtension = $domainParts[1];

            return $obfuscatedName . '@' . $domainName . '.' . $domainExtension;

    }
}
