<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Track;

class TrackController extends Controller
{
    public function index()
    {
        $tracks = Track::with(['field', 'level'])
            ->public() // Use the public scope
            ->where('level_id', '<=', 7)
            ->whereNotNull('level_id')
            ->orderBy('track')
            ->get();
        
        return response()->json($tracks);
    }
}