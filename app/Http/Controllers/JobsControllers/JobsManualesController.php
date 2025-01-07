<?php

namespace App\Http\Controllers\JobsControllers;

use App\Http\Controllers\Controller;
use App\Jobs\ActualizarTarjetasJobs;
use App\Jobs\UpdatePerfilJobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class JobsManualesController extends Controller
{
    public function actualizarTarjetas(){
        // ActualizarTarjetasJobs::dispatch()->onConnection('database');
        return response()->json(['success'=>false,'message'=>'Job ya ha sido procesado.'],400);
    }
    public function actualizarPerfilFuncionario(){
        UpdatePerfilJobs::dispatch()->onConnection('database');
        return response()->json(['success'=>true,'message'=>'Actualizando perfiles en 2do plano.']);
    }
    public function concluido(){
        return response()->json(['success'=>false,'message'=>'Job ya ha sido procesado.'],400);
    }

    public function sumarDeudas(){
        // $CLIENTES = json_decode(File::get('deudas.json'), true);
        $rutaArchivo = public_path('deudas.json');
        $rutaSalida = public_path('deudas_sumadas.json');

        // Verificar si el archivo existe
        if (!File::exists($rutaArchivo)) {
            return response()->json(['success'=>false],400);
            return 1;
        }

        // Leer y decodificar el JSON
        $contenido = File::get($rutaArchivo);
        $datos = json_decode($contenido, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success'=>false],400);
        }

        // Procesar los datos para sumar los montos por cédula
        $usuariosSumados = [];

        foreach ($datos as $usuario) {
            $codigo = $usuario['codigo'];
            $importe = $usuario['importe'];
            $nombre = $usuario['nombre'];

            if (!isset($usuariosSumados[$codigo])) {
                $usuariosSumados[$codigo] = [
                    'codigo' => $codigo,
                    'importe' => 0,
                    'nombre'=>$nombre
                ];
            }

            $usuariosSumados[$codigo]['importe'] += $importe;
        }

        // Convertir a un array de valores
        $resultado = array_values($usuariosSumados);

        // Guardar en un nuevo archivo JSON
        File::put($rutaSalida, json_encode($resultado, JSON_PRETTY_PRINT));
        return response()->json(['success'=>true]);
    }

    public function extraerCedula(){

        $rutaArchivo = public_path('deudas_sumadas.json');
        $rutaSalida = public_path('deudas_con_cedulas.json');

        // Verificar si el archivo existe
        if (!File::exists($rutaArchivo)) {
            return response()->json(['success'=>false],400);
        }

        // Leer y decodificar el JSON
        $contenido = File::get($rutaArchivo);
        $datos = json_decode($contenido, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success'=>false],400);
        }

        // Procesar los datos para agregar la cédula
        foreach ($datos as &$registro) {
            if (preg_match('/C\.I\.\:\s(\d+)/', $registro['nombre'], $coincidencias)) {
                $registro['cedula'] = $coincidencias[1];
            } else {
                $registro['cedula'] = null; // Si no encuentra cédula, lo deja como null
            }
        }

        // Guardar en un nuevo archivo JSON
        File::put($rutaSalida, json_encode($datos, JSON_PRETTY_PRINT));

        return response()->json(['success'=>true]);
    }

    public function clientesConDeudas(){
        $rutaClientes = public_path('clientes_rats.json');
        $rutaDeudas = public_path('deudas_con_cedulas.json');
        $rutaSalida = public_path('deudas_con_id_func.json');

        // Verificar si los archivos existen
        if (!File::exists($rutaClientes) || !File::exists($rutaDeudas)) {
            return response()->json(['success'=>false],400);
        }

        // Leer y decodificar los JSON
        $clientesContenido = File::get($rutaClientes);
        $deudasContenido = File::get($rutaDeudas);

        $clientes = json_decode($clientesContenido, true);
        $deudas = json_decode($deudasContenido, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success'=>false],400);
        }

        // Crear un mapa de documento a id_func
        $clientesMapa = [];
        foreach ($clientes as $cliente) {
            $clientesMapa[$cliente['documento']] = $cliente['id_func'];
        }

        // Agregar id_func a las deudas
        foreach ($deudas as &$deuda) {
            $cedula = $deuda['cedula'];
            if (isset($clientesMapa[$cedula])) {
                $deuda['id_func'] = $clientesMapa[$cedula];
            } else {
                $deuda['id_func'] = null; // Si no hay coincidencia, lo dejamos como null
            }
        }

        // Guardar el resultado en un nuevo archivo JSON
        File::put($rutaSalida, json_encode($deudas, JSON_PRETTY_PRINT));

        return response()->json(['success'=>true]);
    }
}
