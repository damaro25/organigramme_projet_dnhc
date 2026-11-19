<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Organigramme de la DNH</title>
@include('pdf._styles')
<style>
    body { font-size: 7pt; }
    .carte { border: 1px solid #cbd5e1; border-top-width: 3px; border-radius: 4px; background: #fff; padding: 2mm; }
    .carte td { vertical-align: middle; }
    .carte img { width: 13mm; height: 13mm; border-radius: 6.5mm; }
    .carte.grande img { width: 17mm; height: 17mm; border-radius: 8.5mm; }
    .nom { font-weight: bold; font-size: 7.5pt; }
    .grande .nom { font-size: 9pt; }
    .poste { color: #334155; font-size: 6.5pt; }
    .sigle { display: inline-block; color: #fff; font-size: 6pt; font-weight: bold; padding: 0.3mm 1.2mm; border-radius: 2px; }
    .centre { margin: 0 auto; }
    .titre-groupe { font-size: 7pt; font-weight: bold; text-transform: uppercase; color: #475569; margin: 0 0 1.5mm; }
    .liste-agents { margin: 1mm 0 0; padding: 0; }
    .liste-agents td { padding: 0.4mm 1mm; font-size: 6.3pt; border-bottom: 0.5px solid #eef2f7; }
    .taux { text-align: right; white-space: nowrap; font-weight: bold; }
    .colonne { vertical-align: top; padding: 0 1.5mm; }
    .trait { width: 0; height: 4mm; border-left: 1px solid #94a3b8; margin: 0 auto; }
    .trait.tirets { border-left-style: dashed; }
    .section { margin-top: 2mm; }
</style>
</head>
<body>
@php
    $hex = fn ($t) => \App\Services\ExportPdfService::couleurHex($t);
@endphp

@include('pdf._republique')
<table class="entete">
    <tr>
        <td style="width: 18mm"><img src="{{ \App\Services\ExportPdfService::armoiries() }}" style="height: 14mm" alt=""></td>
        <td>
            <div class="titre">Organigramme de la Direction Nationale des Hydrocarbures</div>
            <div class="sous-titre">Personnel, structures et taux moyen d'exécution des projets</div>
        </td>
        <td class="date">Édité le {{ now()->translatedFormat('d F Y à H\hi') }}</td>
    </tr>
</table>

@if (! $racine)
    <p>Aucune structure enregistrée.</p>
@else

{{-- Carte d'un titulaire --}}
@php
    $carte = function (?\App\Models\Agent $agent, $structure, bool $grande = false) use ($export, $hex) {
        $c = $structure->couleur_effective;
        $claire = \App\Services\ExportPdfService::estClaire($c);
        $bord = $claire ? '#6b7280' : $c;
        $html = '<table class="carte'.($grande ? ' grande' : '').'" style="border-top-color: '.$bord.'; width: 100%"><tr>';
        $html .= '<td style="width: '.($grande ? 19 : 15).'mm"><img src="'.$export->photo($agent).'" alt=""></td><td>';
        $html .= '<span class="sigle" style="background: '.$c.'; color: '.($claire ? '#1f2937' : '#fff').'">'.e($structure->libelle_court).'</span><br>';
        if ($agent) {
            $html .= '<span class="nom">'.e($agent->nom_complet).'</span><br><span class="poste">'.e($agent->poste).'</span>';
            $t = $agent->taux_moyen;
            if ($t !== null) {
                $html .= '<div class="barre" style="margin-top: 1mm"><div style="width: '.$t.'%; background: '.$hex($t).'"></div></div>';
                $html .= '<span class="muet">Taux moyen : <b>'.$t.' %</b> · '.$agent->projets->count().' projet(s)</span>';
            }
        } else {
            $html .= '<span class="nom">'.e($structure->nom).'</span>';
        }

        return $html.'</td></tr></table>';
    };
@endphp

{{-- Niveau 1 : DNH, entouré des services rattachés --}}
<table style="width: 100%">
    <tr>
        <td class="colonne" style="width: 33%">
            <p class="titre-groupe">Appui technique</p>
            @foreach ($appuiTechnique as $s)
                <div style="margin-bottom: 1.5mm">{!! $carte($s['responsable'] ?? $s['agents']->first(), $s['structure']) !!}</div>
            @endforeach
        </td>
        <td class="colonne" style="width: 34%; vertical-align: middle">
            {!! $carte($racine['responsable'], $racine['structure'], true) !!}
            <div class="trait"></div>
            @if ($adjointe)
                {!! $carte($adjointe['responsable'], $adjointe['structure'], true) !!}
            @endif
        </td>
        <td class="colonne" style="width: 33%">
            <p class="titre-groupe">Personnel d'appui</p>
            @foreach ($personnelAppui as $s)
                <div style="margin-bottom: 1.5mm">{!! $carte($s['responsable'] ?? $s['agents']->first(), $s['structure']) !!}</div>
            @endforeach
        </td>
    </tr>
</table>

<div class="trait"></div>

{{-- Niveau 2 : les divisions et leurs sections, puis les services déconcentrés --}}
<table style="width: 100%; table-layout: fixed">
    <tr>
        @foreach ($divisions as $division)
            <td class="colonne">
                {!! $carte($division['responsable'], $division['structure']) !!}
                @foreach ($division['enfants'] as $section)
                    <div class="section">
                        {!! $carte($section['responsable'], $section['structure']) !!}
                        @if ($section['agents']->isNotEmpty())
                            <table class="liste-agents" style="width: 100%">
                                @foreach ($section['agents'] as $a)
                                    <tr>
                                        <td>{{ $a->nom_complet }} <span class="muet">— {{ $a->poste }}</span></td>
                                        <td class="taux" style="color: {{ $hex($a->taux_moyen) }}">{{ $a->taux_moyen === null ? '—' : $a->taux_moyen.' %' }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </div>
                @endforeach
            </td>
        @endforeach

        @if ($deconcentres)
            <td class="colonne" style="width: 16%">
                @foreach ($deconcentres as $i => $s)
                    @if ($i > 0)<div class="trait{{ $s['structure']->type_lien->value === 'fonctionnel' ? ' tirets' : '' }}"></div>@endif
                    {!! $carte($s['responsable'], $s['structure']) !!}
                @endforeach
                <p class="muet" style="margin-top: 2mm">Le trait en tirets indique un lien fonctionnel.</p>
            </td>
        @endif
    </tr>
</table>
@endif

<div class="pied">Direction Nationale des Hydrocarbures — Organigramme et suivi des projets</div>
</body>
</html>
