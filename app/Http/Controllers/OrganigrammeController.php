<?php

namespace App\Http\Controllers;

use App\Models\Structure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganigrammeController extends Controller
{
    public function index(Request $request): View
    {
        // Légende : seulement les divisions que le compte peut voir.
        $divisions = $request->user()->perimetre()->restreindreStructures(Structure::query())
            ->where('type', 'division')->ordonne()->get(['id', 'nom', 'couleur']);

        return view('public.organigramme', compact('divisions'));
    }

    /** Nœuds de l'arbre limités à la fiche du compte et à la branche sous lui. */
    public function donnees(Request $request): JsonResponse
    {
        return response()->json(['noeuds' => $request->user()->perimetre()->noeuds()]);
    }
}
