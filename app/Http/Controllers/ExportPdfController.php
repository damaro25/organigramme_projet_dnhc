<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\ExportPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/** Exports PDF : organigramme complet (A3 paysage) et fiche projets d'un agent (A4). */
class ExportPdfController extends Controller
{
    public function __construct(private ExportPdfService $export) {}

    public function organigramme(Request $request): Response
    {
        // La mise en page A3 représente toute la DNH : elle est réservée aux comptes qui voient
        // l'organigramme complet. Les autres exportent leur fiche (avec les projets de leur structure).
        abort_unless($request->user()->perimetre()->estGlobal(), 403, "L'export de l'organigramme complet est réservé aux comptes qui voient toute la Direction.");

        $pdf = Pdf::loadView('pdf.organigramme', $this->export->organigramme() + ['export' => $this->export])
            ->setPaper('a3', 'landscape');

        return $pdf->download('organigramme-dnh-'.now()->format('Y-m-d').'.pdf');
    }

    public function ficheAgent(Request $request, Agent $agent): Response
    {
        abort_unless($agent->actif, 404);
        abort_unless($request->user()->perimetre()->voitAgent($agent), 404);

        $pdf = Pdf::loadView('pdf.fiche-agent', $this->export->ficheAgent($agent))->setPaper('a4');

        return $pdf->download('fiche-'.Str::slug($agent->nom_complet).'-'.now()->format('Y-m-d').'.pdf');
    }
}
