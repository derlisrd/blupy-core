<?php

namespace App\Http\Controllers\BlupyApp;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(String $sku)
    {
        $page = Page::where('sku', $sku)->firstOrFail();

        return response()->json([
            'success'=>true,
            'results'=> $page
        ]);
    }
}
