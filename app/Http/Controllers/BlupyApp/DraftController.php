<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\SolicitudDraft;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    public function gettingDraftSolicitud(Request $req)
    {
        $user = $req->user();
        $cliente = $user->cliente;

        $solicitudDraft = SolicitudDraft::where('cliente_id', $cliente->id)->where('estado', 'draft')->latest()->first();

        if ($solicitudDraft) {
            return response()->json([
                'success' => true,
                'results' => $solicitudDraft,
                'message' => 'Solicitud draft encontrada.'
            ]);
        }
        return response()->json([
            'success' => false,
            'results' => null,
            'message' => 'No hay solicitud draft.'
        ],404);
    }

    public function postingDraftSolicitud(Request $req)
    {
        $user = $req->user();
        $cliente = $user->cliente;

        $solicitudDraft = SolicitudDraft::updateOrCreate(
            [
                'cliente_id' => $cliente->id,
                'estado'     => 'draft',
            ],
            [
                'step'      => $req->step,
                'json_data' => $req->datos,
            ]
        );

        return response()->json([
            'success' => true,
            'results' => $solicitudDraft,
            'message' => 'Solicitud draft actualizada.'
        ]);
    }
    public function deletingDraftSolicitud(Request $req)
    {
        $user = $req->user();
        $cliente = $user->cliente;

        $solicitudDraft = SolicitudDraft::where('cliente_id', $cliente->id)->where('estado', 'draft')->latest()->first();

        if ($solicitudDraft) {
            $solicitudDraft->delete();
            return response()->json([
                'success' => true,
                'results' => null,
                'message' => 'Solicitud draft eliminada.'
            ]);
        }
        return response()->json([
            'success' => false,
            'results' => null,
            'message' => 'No hay solicitud draft.'
        ], 404);
    }
}