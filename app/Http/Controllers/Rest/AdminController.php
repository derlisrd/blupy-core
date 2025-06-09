<?php

namespace App\Http\Controllers\Rest;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function store(Request $req){
        $validator = Validator::make($req->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email|max:255',
            //'password' => 'required|string|min:6',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,moderator',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ],400);
        }

        $adminNuevo = Admin::create([
            'name' => $req->name,
            'email' => $req->email,
            'password' => bcrypt($req->password),
            'role' => $req->role
        ]);
        return response()->json([
            'success' => true,
            'results' => $adminNuevo,
            'message' => 'Se ha creado el administrador correctamente'
        ]);
    }

    public function resetPassword(Request $req){

        $validator = Validator::make($req->all(), [
            'id' => 'required|exists:admins,id',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        Admin::where('id', $req->id)->update([
            'password' => bcrypt($req->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cambio de contraseña realizado correctamente'
        ]);
    }
}
