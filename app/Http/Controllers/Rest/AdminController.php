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
            'name' => 'required',
            'email' => 'required|email|unique:admins',
            'password' => 'required|min:6',
            'role' => 'required,in:admin,moderator',
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
}
