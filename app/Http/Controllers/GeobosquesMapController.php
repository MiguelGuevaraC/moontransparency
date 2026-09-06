<?php

namespace App\Http\Controllers;

use App\Services\GeobosquesMapService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeobosquesMapController extends Controller
{
    public function show(Request $request, GeobosquesMapService $mapService): View
    {
        return view('mapa', [
            'map' => $mapService->build(
                $request->query('latitude'),
                $request->query('longitude')
            ),
        ]);
    }
}
