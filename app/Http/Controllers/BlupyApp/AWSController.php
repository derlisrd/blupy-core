<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Aws\Rekognition\RekognitionClient;
use Aws\Credentials\Credentials;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class AWSController extends Controller
{
    // activo
    public function escanearSelfieConCedula(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'selfie64' => 'required'
        ]);

        if ($validator->fails())
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);

        $ip = $req->ip();
        $rateKey = "scanCedula:$ip";

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 2 minutos.'], 429);
        }
        RateLimiter::hit($rateKey, 120);

        try {

            $credentials = new Credentials(
                env('AWS_ACCESS_KEY_ID'),
                env('AWS_SECRET_ACCESS_KEY')
            );
            $amazon = new RekognitionClient([
                'credentials' => $credentials,
                'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'version' => 'latest',
            ]);
            
            // Procesa la imagen
            $base64Image = explode(";base64,", $req->selfie64);
            $image_base64 = base64_decode($base64Image[1]);

            // Verificamos si la decodificación fue exitosa
            if (!$image_base64) {
                return response()->json(['success' => false, 'message' => 'Error en formato de la imagen. Trate de subir desde galeria. E64'], 400);
            }

            // 1. Detectar rostros con atributos detallados
            $faceDetect = $amazon->detectFaces([
                'Image' => ['Bytes' => $image_base64],
                'Attributes' => ['ALL']
            ]);

            // 2. Detectar objetos/etiquetas en la imagen
            $labelsDetect = $amazon->detectLabels([
                'Image' => ['Bytes' => $image_base64],
                'MaxLabels' => 20,
                'MinConfidence' => 60
            ]);

            $message = '';
            $success = true;
            $status = 200;
            $validations = [];

            // Validar que hay rostros detectados
            if (empty($faceDetect['FaceDetails'])) {
                $success = false;
                $message = 'No se detectó ningún rostro en la imagen.';
                $status = 400;
            } else {
                $face = $faceDetect['FaceDetails'][0]; // Tomamos el primer rostro detectado

                // Validar calidad del rostro
                $faceQuality = $face['Quality'];
                if ($faceQuality['Brightness'] < 30) {
                    $success = false;
                    $message = 'La imagen está muy oscura. Tome la foto con mejor iluminación.';
                    $status = 400;
                }

                if ($faceQuality['Sharpness'] < 30) {
                    $success = false;
                    $message = 'La imagen está borrosa. Tome una foto más nítida.';
                    $status = 400;
                }

                // Validar que no tenga lentes/gafas
                if (isset($face['Eyeglasses']) && $face['Eyeglasses']['Value'] === true && $face['Eyeglasses']['Confidence'] > 80) {
                    $success = false;
                    $message = 'Por favor retire los lentes/gafas para la verificación.';
                    $status = 400;
                }

                // Validar que no tenga gafas de sol
                if (isset($face['Sunglasses']) && $face['Sunglasses']['Value'] === true && $face['Sunglasses']['Confidence'] > 80) {
                    $success = false;
                    $message = 'Por favor retire las gafas de sol para la verificación.';
                    $status = 400;
                }

                // Validar que los ojos estén abiertos
                if (isset($face['EyesOpen']) && $face['EyesOpen']['Value'] === false && $face['EyesOpen']['Confidence'] > 80) {
                    $success = false;
                    $message = 'Por favor mantenga los ojos abiertos durante la foto.';
                    $status = 400;
                }

                // Validar que la boca esté cerrada (opcional, para mejor calidad)
                if (isset($face['MouthOpen']) && $face['MouthOpen']['Value'] === true && $face['MouthOpen']['Confidence'] > 80) {
                    $success = false;
                    $message = 'Por favor mantenga la boca cerrada durante la foto.';
                    $status = 400;
                }

                if (isset($face['FaceOccluded']) && $face['FaceOccluded']['Value'] === true && $face['FaceOccluded']['Confidence'] > 80) {
                    $success = false;
                    $message = 'Por favor no se tape el rostro.';
                    $status = 400;
                }

                // Información de las validaciones del rostro
                $validations['face'] = [
                    'detected' => true,
                    'brightness' => $faceQuality['Brightness'],
                    'sharpness' => $faceQuality['Sharpness'],
                    'has_eyeglasses' => isset($face['Eyeglasses']) ? $face['Eyeglasses']['Value'] : false,
                    'has_sunglasses' => isset($face['Sunglasses']) ? $face['Sunglasses']['Value'] : false,
                    'eyes_open' => isset($face['EyesOpen']) ? $face['EyesOpen']['Value'] : true,
                    'mouth_open' => isset($face['MouthOpen']) ? $face['MouthOpen']['Value'] : false,
                ];
            }

            // Validar presencia de documento de identidad
            $labels = $labelsDetect['Labels'];
            $documentDetected = false;
            $documentConfidence = 0;

            // Buscar etiquetas relacionadas con documentos
            $documentKeywords = ['Document', 'Id Cards', 'Text', 'Paper', 'License', 'Card', 'Identity'];

            foreach ($labels as $label) {
                if (in_array($label['Name'], $documentKeywords)) {
                    if ($label['Confidence'] > $documentConfidence) {
                        $documentConfidence = $label['Confidence'];
                    }
                    if ($label['Confidence'] > 70) {
                        $documentDetected = true;
                    }
                }
            }

            // Validar documento específicamente
            $idCardFound = collect($labels)->firstWhere('Name', 'Id Cards');
            $documentFound = collect($labels)->firstWhere('Name', 'Document');

            if (!$documentDetected && (!$idCardFound || $idCardFound['Confidence'] < 70) && (!$documentFound || $documentFound['Confidence'] < 70)) {
                $success = false;
                $message = 'No se detectó un documento de identidad en la imagen. Asegúrese de tener la cédula visible en tu mano.';
                $status = 400;
            }

            $validations['document'] = [
                'detected' => $documentDetected,
                'confidence' => $documentConfidence,
                'id_card_found' => $idCardFound ? $idCardFound['Confidence'] : 0,
                'document_found' => $documentFound ? $documentFound['Confidence'] : 0,
            ];

            // Si todo está bien
            if ($success) {
                $message = 'Verificación exitosa. Rostro y documento detectados correctamente.';
            }

            return response()->json([
                'success' => $success,
                'message' => $message
            ], $status);
        } catch (\Throwable $th) {
            Log::error('Error en escanearSelfieConCedula: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error. Trate de tomar una foto bien nítida y sin brillos.'
            ], 500);
        }
    }


    public function escanearCedula(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fotofrontal64' => 'required',
            'cedula' => 'required',
            'nombres' => 'required',
            'apellidos' => 'required',
            'nacimiento' => 'required',
        ]);

        if ($validator->fails())
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);

        $ip = $req->ip();
        $rateKey = "scanCedula:$ip";

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 2 minutos.'], 429);
        }
        RateLimiter::hit($rateKey, 120);

        try {
            $amazon = new RekognitionClient([
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'), // Get from .env file
                    'secret' => env('AWS_SECRET_ACCESS_KEY'), // Get from .env file
                ],
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

            $results1 = $analysis1['TextDetections'];



            if (!$results1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo subir la imagen o no se detecta el documento.'
                ], 400);
            }
            $string = '';
            foreach ($results1 as $item) {
                if ($item['Type'] === 'WORD' || $item['Type'] === 'LINE') {
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


            if ($cedula != $extraidoCedula) {
                $nroCedula = false;
                $success = false;
                $message = 'El número de cédula no es legible en la foto, trate de tomar una imagen sin reflejos.';
                $status = 400;
            }

            if (!$fechaNacimiento) {
                $success = false;
                $message = 'La fecha de nacimiento no es legible en la foto, tome una imagen más nitida y legible.';
                $status = 400;
            }
            /* if (!$nombres) {
                $success = false;
                $message = 'El nombre no es legible en la foto, tome una imagen sin reflejos.';
                $status = 400;
            }
            if (!$apellidos) {
                $success = false;
                $message = 'Apellido no concuerda con la foto. Verifique los datos o tome una foto más nitida.';
                $status = 400;
            } */

            return response()->json([
                'success' => $success,
                'results' => [
                    'apellidos' => true, //$apellidos,
                    'nombres' => true, // $nombres,
                    'nacimiento' => $fechaNacimiento,
                    'cedula' => $nroCedula,
                ],
                'message' => $message
            ], $status);
        } catch (\Aws\Rekognition\Exception\RekognitionException $e) {
            // Capturamos el error específico de Rekognition
            if ($e->getAwsErrorCode() === 'ValidationException' && str_contains($e->getAwsErrorMessage(), 'Member must have length less than or equal to')) {
                // Si el error es por el tamaño de la imagen

                return response()->json([
                    'success' => false,
                    'message' => 'El tamaño de la imagen es demasiado grande. El tamaño máximo permitido es de 5 MB.'
                ], 400);
            } else {
                // Para otros errores de Rekognition

                return response()->json(['success' => false, 'message' => 'Error al procesar la imagen con Rekognition. Por favor, intente de nuevo.'], 500);
            }
        } catch (\Throwable $th) {
            // Este catch general es para cualquier otro tipo de error
            Log::error('Error en escanearCedula: ' . $th->getMessage());
            return response()->json(['success' => false, 'message' => 'Error. Trate de tomar una foto bien nítida y sin brillos.'], 500);
        }
    }


    public function scanearDocumento(Request $req)
    {
        $validator = Validator::make($req->all(), trans('validation.verify.scan'), trans('validation.verify.scan.messages'));

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $ip = $req->ip();
        $rateKey = "scanCedula:$ip";

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 2 minutos.'], 429);
        }
        RateLimiter::hit($rateKey, 120);

        try {
            $amazon = new RekognitionClient([
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'), // Get from .env file
                    'secret' => env('AWS_SECRET_ACCESS_KEY'), // Get from .env file
                ],
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



            if (!$results1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo subir la imagen o no se detecta el documento.'
                ], 400);
            }
            $string = '';
            foreach ($results1 as $item) {
                if ($item['Type'] === 'WORD' || $item['Type'] === 'LINE') {
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

            if ($cedula != $extraidoCedula) {
                $nroCedula = false;
                $success = false;
                $message = 'Número de cédula no concuerda con la foto. Verifique los datos.';
                $status = 400;
            }

            if (!$fechaNacimiento) {
                $success = false;
                $message = 'Fecha de nacimiento no concuerda con la foto. Verifique los datos.';
                $status = 400;
            }
            if (!$nombres) {
                $success = false;
                $message = 'Nombre no concuerda con la foto. Verifique los datos.';
                $status = 400;
            }
            if (!$apellidos) {
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
                'message' => $message
            ], $status);
        } catch (\Throwable $th) {

            return response()->json(['success' =>  false, 'message' => 'Error. Trate de tomar una foto bien nitida y sin brillos.'], 500);
        }
    }





    public function scanSelfieCedula(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'selfie' => 'required|string',
            'cedula' => 'required|numeric'
        ]);

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $ip = $req->ip();
        $rateKey = "scanSelfie:$ip";

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return response()->json(['success' => false, 'message' => 'Demasiadas peticiones. Espere 2 minutos.'], 429);
        }
        RateLimiter::hit($rateKey, 120);

        try {
            $amazon = new RekognitionClient([
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'), // Get from .env file
                    'secret' => env('AWS_SECRET_ACCESS_KEY'), // Get from .env file
                ],
                'region' => env('AWS_DEFAULT_REGION', 'us-east-2'),
                'version' => 'latest',
            ]);

            $base64Image = explode(";base64,", $req->selfie);
            $explodeImage = explode("image/", $base64Image[0]);
            $imageType = $explodeImage[1];
            $image_base64 = base64_decode($base64Image[1]);
            $imageName = $req->cedula . '_selfie_ci.' . $imageType;
            $imagePath = public_path('clientes/tmp/' . $imageName);
            file_put_contents($imagePath, $image_base64);
            $image = fopen($imagePath, "r");
            $bytes = fread($image, filesize($imagePath));
            fclose($image);

            $faceDetect = $amazon->detectFaces(['Image' => ['Bytes' => $bytes], 'Attributes' => ['ALL']]);
            $faceDetectArray = ($faceDetect['FaceDetails']);

            $etiquetas = $amazon->detectLabels(['Image' => ['Bytes' => $bytes], 'MaxLabels' => 10]);
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
                SupabaseService::uploadImageSelfies($imageName, $imagePath, $imageType);
                $status = 400;
                $success = false;
            }

            unlink($imagePath);
            return response()->json([
                'success' => $success,
                'message' => $message,
            ], $status);
        } catch (\Throwable $th) {

            return response()->json(['success' =>  false, 'message' => 'Error. Trate de tomar una foto bien nitida y sin brillos.'], 500);
        }
    }

    private function getImageBytes(string $imageBase64, string $keyImage)
    {

        $base64Image = explode(";base64,", $imageBase64);
        $explodeImage = explode("image/", $base64Image[0]);
        $imageType = $explodeImage[1];
        $image_base64 = base64_decode($base64Image[1]);
        $imageName = $keyImage . '.' . $imageType;
        $imagePath = public_path('clientes/' . $imageName);
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


    private function CleanText($name)
    {
        setlocale(LC_ALL, 'en_US');
        $name = iconv('utf-8', 'ASCII//TRANSLIT', $name);
        $name = mb_strtoupper($name, 'utf-8');
        $name = str_replace(' ', '-', $name);
        $name = preg_replace('/[^A-Za-z0-9\-]/', '', $name);
        $name = preg_replace('/-+/', ' ', $name);
        return $name;
    }

    private function CleanScan($name)
    {
        setlocale(LC_ALL, 'en_US');
        $name = iconv('utf-8', 'ASCII//TRANSLIT', $name);
        $name = mb_strtoupper($name, 'utf-8');
        $name = preg_replace('/[^A-Za-z0-9\-\_\ ]+/', '', $name);
        return $name;
    }
}
