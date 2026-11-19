<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Fiche projets — {{ $agent->nom_complet }}</title>
@include('pdf._styles')
<style>
    body { font-size: 9pt; }
    .identite { width: 100%; background: #ebf7f1; border-radius: 4px; margin-bottom: 5mm; }
    .identite td { padding: 3mm; vertical-align: middle; }
    .identite img { width: 28mm; height: 28mm; border-radius: 14mm; }
    .identite .nom { font-size: 14pt; font-weight: bold; }
    .identite .poste { font-size: 10pt; color: #006a45; font-weight: bold; }
    .synthese { text-align: center; }
    .synthese .chiffre { font-size: 20pt; font-weight: bold; }
    h2 { font-size: 11pt; color: #006a45; border-bottom: 1px solid #cbd5e1; padding-bottom: 1mm; margin: 5mm 0 3mm; }
    .projet { border: 1px solid #e2e8f0; border-radius: 4px; padding: 3mm; margin-bottom: 3mm; page-break-inside: avoid; }
    .projet.retard { border-color: #fca5a5; background: #fef2f2; }
    .projet .titre-projet { font-size: 10pt; font-weight: bold; }
    .statut { display: inline-block; font-size: 7pt; padding: 0.4mm 1.5mm; border-radius: 2px; background: #e2e8f0; }
    .retard-badge { display: inline-block; font-size: 7pt; padding: 0.4mm 1.5mm; border-radius: 2px; background: #dc2626; color: #fff; font-weight: bold; }
    .etapes { width: 100%; margin-top: 2mm; }
    .etapes td { padding: 0.8mm 1mm; font-size: 8pt; border-bottom: 0.5px solid #f1f5f9; vertical-align: top; }
    .coche { width: 5mm; font-weight: bold; }
</style>
</head>
<body>
@php $hex = fn ($t) => \App\Services\ExportPdfService::couleurHex($t); @endphp

@include('pdf._republique')
<table class="entete">
    <tr>
        <td style="width: 16mm"><img src="{{ \App\Services\ExportPdfService::armoiries() }}" style="height: 12mm" alt=""></td>
        <td>
            <div class="titre">Fiche projets de l'agent</div>
            <div class="sous-titre">Direction Nationale des Hydrocarbures</div>
        </td>
        <td class="date">Édité le {{ now()->translatedFormat('d F Y à H\hi') }}</td>
    </tr>
</table>

<table class="identite">
    <tr>
        <td style="width: 32mm"><img src="{{ $photo }}" alt=""></td>
        <td>
            <div class="nom">{{ $agent->nom_complet }}</div>
            <div class="poste">{{ $agent->poste }}</div>
            <div>{{ $agent->structure?->nom }}@if ($agent->structure?->parent) — {{ $agent->structure->parent->nom }}@endif</div>
            <div class="muet">{{ collect([$agent->email, $agent->telephone])->filter()->join(' · ') }}</div>
        </td>
        <td class="synthese" style="width: 35mm">
            <div class="chiffre" style="color: {{ $hex($taux) }}">{{ $taux === null ? '—' : $taux.' %' }}</div>
            <div class="muet">Taux moyen · {{ $projets->count() }} projet(s)</div>
        </td>
    </tr>
</table>

@php
    $listes = [['Projets affectés', $projets, true]];
    if ($structure) {
        $listes[] = ['Projets de la structure : '.$structure['nom'].' (taux consolidé : '.($structure['taux'] ?? '—').' %)', $structure['projets'], false];
    }
@endphp

@foreach ($listes as [$titre, $liste, $avecRole])
    <h2>{{ $titre }}</h2>
    @forelse ($liste as $p)
        @php $t = $p->taux_execution; @endphp
        <div class="projet{{ $p->est_en_retard ? ' retard' : '' }}">
            <table style="width: 100%">
                <tr>
                    <td>
                        <div class="titre-projet">{{ $p->titre }}</div>
                        <span class="statut">{{ $p->statut->libelle() }}</span>
                        @if ($p->est_en_retard)<span class="retard-badge">En retard</span>@endif
                        <span class="muet">
                            {{ $p->structure?->libelle_court }}
                            @if ($avecRole && $p->pivot?->role) · {{ \App\Enums\RoleProjet::tryFrom($p->pivot->role)?->libelle() ?? $p->pivot->role }}@endif
                            · du {{ $p->date_debut?->format('d/m/Y') ?? '—' }} au {{ $p->date_fin_prevue?->format('d/m/Y') ?? '—' }}
                        </span>
                        @if ($p->responsables->isNotEmpty())
                            <div class="muet">Responsable : {{ $p->responsables->pluck('nom_complet')->join(', ') }}</div>
                        @endif
                    </td>
                    <td style="width: 45mm; vertical-align: top">
                        <div style="text-align: right; font-size: 12pt; font-weight: bold; color: {{ $hex($t) }}">{{ $t }} %</div>
                        <div class="barre"><div style="width: {{ $t }}%; background: {{ $hex($t) }}"></div></div>
                    </td>
                </tr>
            </table>
            @if ($p->etapes->isNotEmpty())
                <table class="etapes">
                    @foreach ($p->etapes as $e)
                        <tr>
                            <td class="coche" style="color: {{ $e->est_terminee ? '#16a34a' : '#94a3b8' }}">{{ $e->est_terminee ? '✔' : '○' }}</td>
                            <td>{{ $e->libelle }}@if ($e->commentaire)<br><span class="muet">{{ $e->commentaire }}</span>@endif</td>
                            <td class="muet" style="width: 22mm; text-align: right">{{ $e->date_realisation?->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    @empty
        <p class="muet">Aucun projet en cours.</p>
    @endforelse
@endforeach

<div class="pied">Direction Nationale des Hydrocarbures — Fiche générée depuis l'application de suivi des projets</div>
</body>
</html>
