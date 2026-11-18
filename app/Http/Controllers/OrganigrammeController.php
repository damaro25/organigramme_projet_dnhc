<?php

namespace App\Http\Controllers;

use App\Models\Structure;
use App\Services\OrganigrammeBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OrganigrammeController extends Controller
{
    public function index(): View
    {
        $divisions = Structure::query()->where('type', 'division')->ordonne()->get(['id', 'nom', 'couleur']);

        return view('public.organigramme', compact('divisions'));
    }

    public function donnees(OrganigrammeBuilder $builder): JsonResponse
    {
        return response()->json(['noeuds' => $builder->construire()]);
    }
}
