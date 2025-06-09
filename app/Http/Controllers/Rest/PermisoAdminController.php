<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Permiso;
use App\Models\PermisosOtorgado;
use Illuminate\Http\Request;

class PermisoAdminController extends Controller
{
    public function index()
    {
        $permisos = Permiso::all();
        return response()->json([
            'success' => true,
            'results' => $permisos
        ]);        
    }

    public function permisosByAdmin(Request $req){

        $permisosByUser = PermisosOtorgado::where('admin_id', $req->id)->get();

        return response()->json([
            'success' => true,
            'results' => $permisosByUser
        ]);
    }

    public function administradores(){
        $admins = Admin::all();
        return response()->json([
            'succcess' => true,
            'results' => $admins
        ]);
    }

    // Asignar permisos a un admin
    public function asignar(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'permisos' => 'required|array',
            'permisos.*' => 'exists:permisos,id',
        ]);

        $admin = Admin::findOrFail($request->admin_id);
        $admin->permisos()->syncWithoutDetaching($request->permisos);

        return response()->json(['message' => 'Permisos asignados correctamente.']);
    }

    // Quitar permisos de un admin
    public function revocar(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'permisos' => 'required|array',
            'permisos.*' => 'exists:permisos,id',
        ]);

        $admin = Admin::findOrFail($request->admin_id);
        $admin->permisos()->detach($request->permisos);

        return response()->json(['message' => 'Permisos revocados correctamente.']);
    }

}
