{{-- Barre de progression du taux d'exécution : rouge < 30 %, orange 30–70 %, vert > 70 %. --}}
@props(['taux'])
@php($c = \App\Models\Projet::couleurPourTaux($taux))
<div class="flex items-center gap-2">
    <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-200">
        <div @class(['h-full rounded-full', 'bg-red-600' => $c === 'rouge', 'bg-orange-500' => $c === 'orange', 'bg-green-600' => $c === 'vert', 'bg-slate-400' => $c === 'gris']) style="width: {{ $taux ?? 0 }}%"></div>
    </div>
    <span class="w-10 text-right text-xs font-bold">{{ $taux === null ? '—' : $taux.'%' }}</span>
</div>
