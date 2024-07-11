<?php

namespace App\Traits;

use App\Services\FarmaService;
use App\Services\InfinitaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

trait RegisterTraits
{
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

    public function clienteFarma(String $cedula){
        $farma = new FarmaService();
        $res = (object)$farma->cliente($cedula);
        $data = (object)$res->data;

        if(property_exists($data,'result')){
            $result = $data->result;
            if(count($result)>0){
                $dato = (object)$result[0];
                return (object)[
                    'funcionario'=> $dato->esFuncionario == "N" ? 0 : 1,
                    'lineaFarma'=> ($dato->limiteCreditoTotal > 0) ? 1 : 0,
                    'credito'=> $dato->limiteCreditoTotal,
                    'asofarma'=> ($dato->esFuncionario == "N" && ((int)$dato->limiteCreditoTotal) > 0 ) ? 1 : 0,
                    'completado'=>1
                ];
            }
        }
        return (object)[
            'funcionario'=>0,
            'lineaFarma'=>0,
            'credito'=> 0,
            'asofarma'=>0,
            'completado'=>0
        ];
    }



    public function registrarInfinita(Object $cliente){
        $infinitaService = new InfinitaService();
        $response = (object)$infinitaService->TraerPorDocumento($cliente->cedula);
        $datosDeInfinita = (object) $response->data;

        $response = [ 'cliId'=>0, 'register'=>false, 'solicitudId'=>0 ];
        try {
            if(property_exists($datosDeInfinita,'CliId') && $datosDeInfinita->CliId !== '0'){
                return [ 'cliId'=>$datosDeInfinita->CliId, 'register'=>true, 'solicitudId'=>0 ];
            }

            $registrarEnInfinita = (object)$infinitaService->registrar((object)$cliente);

            $dataInfinita = (object) $registrarEnInfinita->data;
                if(property_exists($dataInfinita,'CliId')){
                    if( $dataInfinita->CliId !== '0'){
                        $response = [
                            'cliId'=>$dataInfinita->CliId,
                            'estadoSolicitud'=> trim($dataInfinita->SolEstado),
                            'solicitudId'=>$dataInfinita->SolId,
                            'register'=>true
                        ];
                    }
                }
            return  $response;

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function enviarFotoCedulaInfinita(String $cedula, String $fotoFrontal, String $fotoDorsal){
        $infinitaService = new InfinitaService();
        $foto1 = preg_replace('#data:image/[^;]+;base64,#', '', $fotoFrontal);
        $foto2 = preg_replace('#data:image/[^;]+;base64,#', '', $fotoDorsal);
        $res = $infinitaService->enviarFotoCedula($cedula,$foto1,$foto2);
        Log::info($res);
    }

    public function enviarEmailRegistro(String $email, String $nombre){
        try {
            Mail::send('email.registro', ['name'=>$nombre], function ($message) use($email) {
                $message->subject('Blupy: Registro exitoso');
                $message->to($email);
            });
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'success'=>false,
                'message'=>'Error al enviar el email mas tarde'
            ],500);
        }
    }

    public function enviarEmailDeLogueoInusual(Array $datos){
        try {
            $datas = [
                'email'=>$datos['email'],
                'ip'=>$datos['ip'],
                'nombre'=>$datos['nombre'],
                'device'=>$datos['device']
            ];
            Mail::send('email.intentoIngreso', $datas, function ($message) use($datas) {
                $message->subject('Blupy: intento de ingreso inusual');
                $message->to($datas['email']);
            });
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'success'=>false,
                'message'=>'Error al enviar el email mas tarde'
            ],500);
        }
    }

    public function userInfo($cliente,string $token, array $tarjetas){
        return [
            'cliid'=>$cliente->cliid,
            'name'=>$cliente->user->name,
            'nombres'=>trim($cliente->nombre_primero . ' ' . $cliente->nombre_segundo),
            'apellidos'=>trim($cliente->apellido_primero . ' ' . $cliente->apellido_segundo),
            'cedula'=>$cliente->cedula,
            'fechaNacimiento'=>$cliente->fecha_nacimiento,
            'email'=>$cliente->user->email,
            'telefono'=>$cliente->celular,
            'celular'=>$cliente->celular,
            'solicitudCredito'=>$cliente->solicitud_credito,
            'direccionCompletado'=>$cliente->direccion_completado,
            'funcionario'=>$cliente->funcionario,
            'aso'=>$cliente->asofarma,
            'vendedorId'=>$cliente->user->vendedor_id,
            'tokenType'=>'Bearer',
            'token'=>'Bearer '.$token,
            'tarjetas'=>$tarjetas ?? []
        ];
    }



    public function guardarCedulaImagenBase64(String $imagenBase64, String $cedula){
        $path = null;
        if (preg_match('/^data:image\/(\w+);base64,/', $imagenBase64, $matches)) {
            $extension = $matches[1]; // La extensión de la imagen (e.g., jpeg, png)
            $imagenBase64 = substr($imagenBase64, strpos($imagenBase64, ',') + 1); // Eliminar el prefijo

            // Decodificar la imagen base64
            $imagen = base64_decode($imagenBase64);
            $imager = new ImageManager(Driver::class);
            $imageName = 'cedula_' . $cedula . '.' . $extension;
            $publicPath = public_path('clientes/' . $imageName);
            $imager->read($imagen)->scale(800)->save($publicPath);

            return $imageName; // Opcional: retornar el path de la imagen guardada
        }
        return $path;
    }


}
