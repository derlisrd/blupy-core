<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class AWSController extends Controller
{
    
    public function scanearDocumento(Request $req){
        $validator = Validator::make($req->all(),trans('validation.verify.scan'),trans('validation.verify.scan.messages'));

        if($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()],400);

        $ip = $req->ip();
        $rateKey = "scanCedula:$ip";

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 2 minutos.'], 429);
        }
        RateLimiter::hit($rateKey, 120);

        try {
            $amazon = new RekognitionClient([
                'region' => env('AWS_DEFAULT_REGION', 'us-east-2'),
                'version' => 'latest',
            ]);

            // Procesa la imagen frontal de la cédula
            $base64Image = explode(";base64,", $req->fotofrontal64);
            $image_base64 = base64_decode($base64Image[1]);

            // Verificamos si la decodificación fue exitosa
            if (!$image_base64) {
                return response()->json(['success' => false, 'message' => 'Error en formato de la imagen. Trate de subir desde galeria. E64'], 400);
            }

            // Pasamos los bytes decodificados directamente a Rekognition.
            $analysis1 = $amazon->detectText([
                'Image' => ['Bytes' => $image_base64],
                'MaxLabels' => 10,
                'MinConfidence' => 77
            ]);
            
            // Procesa la imagen de la selfie
            $base64Selfie = explode(";base64,", $req->fotoselfie64);
            $image_base64_selfie = base64_decode($base64Selfie[1]);
            
            // Verificamos si la decodificación fue exitosa
            if (!$image_base64_selfie) {
                return response()->json(['success' => false, 'message' => 'Error en formato de selfie. Suba desde galeria. SE64'], 400);
            }

            // Pasamos los bytes decodificados directamente a Rekognition.
            $faceDetect = $amazon->detectFaces([
                'Image' => ['Bytes' => $image_base64_selfie],
                'Attributes' => ['ALL']
            ]);

            $faceDetectArray = ($faceDetect['FaceDetails']);
            $results1 = $analysis1['TextDetections'];
            if(!$results1){
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo subir la imagen o no se detecta el documento.'
                ], 400);
            }
            $string = '';
            foreach($results1 as $item){
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

            $message = '';
            $success = true;
            $status = 200;

            $selfieDetect = true;
            if (!$faceDetectArray) {   
                $selfieDetect = false;
                $success = false;
                $message = 'No se pudo detectar su rostro en la foto.';
                $status = 400;
            }

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

            return response()->json([
                'success' => $success,
                'results' => [
                    'apellidos' => $apellidos,
                    'nombres' => $nombres,
                    'nacimiento' => $fechaNacimiento,
                    'cedula' => $nroCedula,
                    'selfie' => $selfieDetect,
                ],
                'message'=>$message
            ],$status);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json(['success' =>  false, 'message'=>'Error. Trate de tomar una foto bien nitida y sin brillos.'],500);
        }
    }


    


    public function scanSelfieCedula(Request $req){
        $validator = Validator::make($req->all(),[
            'selfie' => 'required|string',
            'cedula' => 'required|numeric'
        ]);

        if($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()],400);

            $ip = $req->ip();
            $rateKey = "scanSelfie:$ip";

            if (RateLimiter::tooManyAttempts($rateKey, 5)) {
                return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 2 minutos.'], 429);
            }
            RateLimiter::hit($rateKey, 120);

        try {
            $amazon = new RekognitionClient([
                'region' => env('AWS_DEFAULT_REGION', 'us-east-2'),
                'version' => 'latest',
            ]);

            $base64Image = explode(";base64,", $req->selfie);
            $explodeImage = explode("image/", $base64Image[0]);
            $imageType = $explodeImage[1];
            $image_base64 = base64_decode($base64Image[1]);
            $imageName = $req->cedula . '_selfie_ci.'.$imageType;
            $imagePath = public_path('clientes/tmp/' .$imageName);
            file_put_contents($imagePath, $image_base64);
            $image = fopen($imagePath, "r");
            $bytes = fread($image, filesize($imagePath));
            fclose($image);

            $faceDetect = $amazon->detectFaces(['Image' => ['Bytes' => $bytes], 'Attributes' => ['ALL']]);
            $faceDetectArray = ($faceDetect['FaceDetails']);

            $etiquetas = $amazon->detectLabels(['Image'=> ['Bytes' => $bytes],'MaxLabels' => 10]);
            $labels = $etiquetas['Labels'];

            $document = collect($labels)->firstWhere('Name', 'Document');
            $idCard = collect($labels)->firstWhere('Name', 'Id Cards');
            //$face = collect($labels)->firstWhere('Name', 'Face');

            $documentValid = $document && $document['Confidence'] > 70;
            $idCardValid = $idCard && $idCard['Confidence'] > 70;
            //$faceValid = $face && $face['Confidence'] > 60;
            $message = 'Imagen subida correctamente.';
            $success = true;
            $status = 200;

            if (!$faceDetectArray) {   
                $message = 'No se dectectó rostro.';
            }
            
            if (!$documentValid) {
                $message = 'No se pudo detectar el documento.';
            }
            if (!$idCardValid) {
                $message = 'No se pudo detectar la cédula.';
            }
            

            if (!$documentValid || !$idCardValid || !$faceDetectArray) {
                SupabaseService::LOG($req->cedula, $message);
                SupabaseService::uploadImageSelfies($imageName,$imagePath,$imageType);
                $status = 400;
                $success = false;
            }

            unlink($imagePath);
            return response()->json([
                'success' => $success,
                'message' => $message,
            ], $status);
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json(['success' =>  false, 'message'=>'Error. Trate de tomar una foto bien nitida y sin brillos.'],500);
        }

    }

    private function getImageBytes(string $imageBase64,string $keyImage){

        $base64Image = explode(";base64,", $imageBase64);
        $explodeImage = explode("image/", $base64Image[0]);
        $imageType = $explodeImage[1];
        $image_base64 = base64_decode($base64Image[1]);
        $imageName = $keyImage . '.'.$imageType;
        $imagePath = public_path('clientes/' .$imageName);
        file_put_contents($imagePath, $image_base64);
        $image = fopen($imagePath, "r");
        $bytes = fread($image, filesize($imagePath));
        fclose($image);
        return [
            'bytes' => $bytes,
            'imagePath' => $imagePath,
            'imageName' => $imageName,
        ];
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
