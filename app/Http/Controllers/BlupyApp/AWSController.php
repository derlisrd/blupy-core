<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AWSController extends Controller
{
    public function scanearDocumento(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verify.scan'),trans('validation.verify.scan.messages'));

        if($validator->fails()) return response()->json(['success' => false, 'message' => $validator->errors()->first()],400);

        try {
            $amazon = new RekognitionClient([
                'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'version' => 'latest',
            ]);
            $base64Image = explode(";base64,", $req->fotofrontal64);
            $explodeImage = explode("image/", $base64Image[0]);
            $imageType = $explodeImage[1];
            $image_base64 = base64_decode($base64Image[1]);
            $imageName = $req->cedula . '_front.'.$imageType;
            $imagePath = public_path('clientes/' .$imageName);
            file_put_contents($imagePath, $image_base64);
            $image = fopen($imagePath, "r");
            $bytes = fread($image, filesize($imagePath));
            fclose($image);
            $analysis = $amazon->detectText(['Image'=> ['Bytes' => $bytes],'MaxLabels' => 10,'MinConfidence' => 77]);
            $results = $analysis['TextDetections'];
            if(!$results){
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo subir la imagen (1)'
                ], 400);
            }
            $string = '';
            foreach($results as $item){
                if($item['Type'] === 'WORD' || $item['Type'] === 'LINE'){
                    $string .= $item['DetectedText'] . ' ';
                }
            }
            $scaned = $this->CleanScan($string);
            $name = $this->CleanText($req->nombres);
            $lastname = $this->CleanText($req->apellidos);
            $nacimiento = str_replace('/', '-', $req->nacimiento);
            $nombres = Str::contains($scaned, [$name]);
            $apellidos = Str::contains($scaned, [$lastname]);
            $fechaNacimiento = Str::contains($scaned, [$nacimiento]);
            $nroCedula = Str::contains($scaned, [$req->cedula]);
            $extraidoCedula = (int)strstr($scaned, $req->cedula);
            $cedula = (int) $req->cedula;

            SupabaseService::LOG('scaneo',$scaned);
            $message = '';
            $success = true;
            $status = 200;
            if($cedula != $extraidoCedula){
                $nroCedula = false;
                $success = false;
                $message = 'Número de cédula no concuerda con la foto. Verifique los datos.';
                $status = 400;
            }

            if(!$fechaNacimiento ){
                $success = false;
                $message = 'Fecha de nacimiento no concuerda con la foto. Verifique los datos.';
                $status = 400;
            }
            if(!$nombres ){
                $success = false;
                $message = 'Nombre no concuerda con la foto. Verifique los datos.';
                $status = 400;
            }
            if(!$apellidos ){
                $success = false;
                $message = 'Apellido no concuerda con la foto. Verifique los datos.';
                $status = 400;
            }
            unlink($imagePath);
            return response()->json([
                'success' => $success,
                'results' => [
                    'apellidos' => $apellidos,
                    'nombres' => $nombres,
                    'nacimiento' => $fechaNacimiento,
                    'cedula' => $nroCedula
                ],
                'message'=>$message
            ],$status);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['success' =>  false, 'message'=>'Error. Trate de tomar una foto bien nitida y sin brillos.'],500);
        }
    }

    private function CleanText($name){
        setlocale(LC_ALL, 'en_US');
        $name = iconv('utf-8', 'ASCII//TRANSLIT', $name);
        $name = mb_strtoupper($name, 'utf-8');
        $name = str_replace(' ', '-', $name);
        $name = preg_replace('/[^A-Za-z0-9\-]/', '', $name);
        $name = preg_replace('/-+/', ' ', $name);
        return $name;
    }

    private function CleanScan($name){
        setlocale(LC_ALL, 'en_US');
        $name = iconv('utf-8', 'ASCII//TRANSLIT', $name);
        $name = mb_strtoupper($name, 'utf-8');
        $name = preg_replace('/[^A-Za-z0-9\-\_\ ]+/', '', $name);
        return $name;
    }
}
