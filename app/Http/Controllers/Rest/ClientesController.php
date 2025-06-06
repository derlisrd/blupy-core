<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Adjunto;
use App\Models\Cliente;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\FarmaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Validator;

class ClientesController extends Controller
{
    protected $campos;

    public function __construct()
    {
        $this->campos = [
            'clientes.id',
            'u.name',
            'u.id as user_id',
            'cedula',
            'celular',
            'u.email',
            'cliid as id_micredito',
            'asofarma',
            'funcionario',
            'solicitud_credito',
            'foto_ci_frente',
            'clientes.created_at',
            'u.active',
            'u.vendedor_id',
            'clientes.extranjero',
            'clientes.selfie',
            'clientes.salario',
            'clientes.empresa',
            'clientes.latitud_direccion',
            'clientes.longitud_direccion',
            'clientes.codigo_farma'
        ];
    }

    /*
    ==============================================================================================================
    CLIENTES NUEVOS DEL MES
    ==============================================================================================================
    */

    public function index()
    {
        try {
            $desde = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
            $hasta = Carbon::now()->format('Y-m-d H:i:s');
            $results = Cliente::join('users as u', 'u.cliente_id', '=', 'clientes.id')
                ->orderBy('clientes.created_at', 'DESC')
                ->whereBetween('clientes.created_at', [$desde, $hasta])
                ->select($this->campos)
                ->get();
            /* Cliente::join('users as u','u.cliente_id','=','clientes.id')
            ->whereBetween('clientes.created_at',[$desde,$hasta])
            ->orderBy('clientes.created_at','DESC')
            ->select($this->campos)
            ->get(); */

            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Throwable $th) {
            throw $th;
            return response()->json([
                'success' => false,
                'results' => null,
                'message' => 'Error de servidor'
            ]);
        }
    }

    /*
    ==============================================================================================================
    FILTROS
    ==============================================================================================================
    */
    public function filtros(Request $request)
    {
        $asofarma = $request->asofarma ?? 0;
        $funcionario = $request->funcionario ?? 0;
        $todos = $request->todos ?? false;
        $desde = $request->desde ?? Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $hasta = $request->hasta ?? Carbon::now()->format('Y-m-d H:i:s');

        if ($todos) {
            $clientes = Cliente::join('users as u', 'u.cliente_id', '=', 'clientes.id')
                ->orderBy('created_at', 'DESC')
                ->where('asofarma', $asofarma)
                ->where('funcionario', $funcionario)
                ->select($this->campos);
        }

        if (!$todos) {
            $clientes = Cliente::join('users as u', 'u.cliente_id', '=', 'clientes.id')
                ->orderBy('created_at', 'DESC')
                ->where('asofarma', $asofarma)
                ->where('funcionario', $funcionario)
                ->whereBetween('clientes.created_at', [$desde, $hasta])
                ->select($this->campos);
        }

        return response()->json([
            'success' => true,
            'total' => $clientes->count(),
            'results' => $clientes->get()
        ]);
    }

    /*
    ==============================================================================================================
    BUSCAR
    ==============================================================================================================
    */

    public function buscar(Request $req)
    {

        $buscar = trim($req->q ?? '');


        if (empty($buscar)) {
            return response()->json([
                'success' => true,
                'total' => 0,
                'results' => []
            ]);
        }
        $clientes = Cliente::join('users as u', 'u.cliente_id', '=', 'clientes.id')
            ->select($this->campos);

        // Aplicar filtros de búsqueda dentro de un grupo para evitar comportamientos inesperados
        $clientes->where(function ($query) use ($buscar) {
            $query->where('clientes.cedula', 'like', '%' . $buscar . '%')
                ->orWhere('u.name', 'like', '%' . $buscar . '%');
        });

        $clientes->orderBy('clientes.created_at', 'DESC');

        // Obtener resultados una sola vez para mejorar rendimiento
        $resultados = $clientes->get();
        $total = count($resultados);


        return response()->json([
            'success' => true,
            'total' => $total,
            'results' => $resultados
        ]);
    }

    public function actualizarFuncionario(Request $req)
    {

        $validator = Validator::make($req->all(), ['cedula' => 'required']);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $farma = new FarmaService();
        $res = $farma->cliente($req->cedula);
        $dataFarma = (object)$res['data'];

        $farmaResult = null;

        if (property_exists($dataFarma, 'result')) {
            $result = $dataFarma->result;
            if (count($result) > 0) {
                $farmaResult = $result[0];
                $esFuncionario = $farmaResult['esFuncionario'] === "N" ? 0 : 1;
                Cliente::where('cedula', $req->cedula)->update([
                    'funcionario' => $esFuncionario
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Actualizado'
        ]);
    }


    /*
    ==============================================================================================================
    REINICIAR CONTRA
    ==============================================================================================================
    */


    public function restablecerContrasena(Request $request)
    {
        $validator = Validator::make($request->all(), ['id' => 'required|exists:users,id', 'password' => 'required|string|min:8']);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $user = User::find($request->id);
        if (!$user)
            return response()->json(['success' => false, 'message' => 'Usuario no existe'], 404);

        $user->password = Hash::make($request->password);
        $user->changepass = true;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cambiado !'
        ]);
    }
    /*
    ==============================================================================================================
    ACTIVAR USUARIO
    ==============================================================================================================
    */
    public function cambiarEstado(Request $request)
    {
        $validator = Validator::make($request->all(), ['id' => 'required|exists:users,id']);
        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        $user = User::find($request->id);
        if (!$user)
            return response()->json(['success' => false, 'message' => 'Usuario no existe'], 404);

        $user->active = !$user->active;
        $user->save();

        return response()->json([
            'success' => true,
            'results' => $user,
            'message' => 'Estado actualizado'
        ]);
    }


    public function ficha(Request $request)
    {

        $clients = Cliente::findorFail($request->id);

        return response()->json([
            'success' => true,
            'results' => $clients
        ]);
    }
    public function adjuntos(Request $req, $id){
        $adjuntos = Adjunto::where('cliente_id', $id)->get();
        return response()->json([
            'success' => true,
            'results' => $adjuntos
        ]);
    }

    public function agregarAdjunto(Request $req,$id){
        $validator = Validator::make(
            ['id' => $id,
            'adjunto' => $req->adjunto],
            [
                'id' => 'required|exists:clientes,id',
                'adjunto' => 'required|image|mimes:jpeg,png,jpg|max:2048'
            ]
        );

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);



        try {

            $imagen = $req->file('adjunto');
            $extension = $imagen->getClientOriginalExtension();
            $time = time();
            $imageName =  $id . '_' . $time . '.' . $extension;
            $publicPath = public_path('adjuntos/' . $imageName);

            $imager = new ImageManager(new Driver());
            // Procesar y guardar la imagen
            $imager->read($imagen->getPathname())->scale(800)->save($publicPath);


            Adjunto::create([
                'cliente_id' => $id,
                'nombre' => $imageName,
                'tipo' => $req->nombre,
                'path' => 'adjuntos',
                'url' => 'adjuntos/'. $imageName
            ]);


            return response()->json([
                'message' => 'Imagen subida correctamente',
                'success' => true,
                'results' => [
                    'path' => asset('abjuntos/' . $imageName)
                ]
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    public function actualizarFotoCedula(Request $request, $id)
    {
        $validator = Validator::make(
            ['id' => $id,
            'foto_cedula' => $request->foto_cedula],
            [
                'id' => 'required|exists:clientes,id',
                'foto_cedula' => 'required|image|mimes:jpeg,png,jpg|max:2048'
            ]
        );

        if ($validator->fails())
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);

        try {
            $cliente = Cliente::find($request->id);
            $imagen = $request->file('foto_cedula');
            $extension = $imagen->getClientOriginalExtension();

            $imageName = 'cedula_' . $cliente->cedula . '.' . $extension;
            $publicPath = public_path('clientes/' . $imageName);

            $imager = new ImageManager(new Driver());
            // Procesar y guardar la imagen
            $imager->read($imagen->getPathname())->scale(800)->save($publicPath);

            Adjunto::create([
                'cliente_id' => $request->id,
                'nombre' => $imageName,
                'tipo' => 'cedula1',
                'path' => 'clientes',
                'url' => 'clientes/'. $imageName
            ]);


            return response()->json([
                'message' => 'Imagen subida correctamente',
                'success' => true,
                'results' => [
                    'path' => asset('clientes/' . $imageName)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al subir la imagen. ' . $e->getMessage(),
                'success' => false,
                'results' => null
            ], 500);
        }
    }
}
