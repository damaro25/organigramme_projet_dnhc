{{--
    Badge de statut. couleur : neutre, primaire, succes, avertissement, danger, info, accent.
    Pour un statut de projet, passer statut="en_cours" (non_demarre, en_cours, termine, suspendu).
--}}
@props(['couleur' => null, 'statut' => null])

@php
    $couleur ??= match ($statut instanceof \App\Enums\StatutProjet ? $statut->value : $statut) {
        'en_cours' => 'primaire', 'termine' => 'succes', 'suspendu' => 'avertissement', default => 'neutre',
    };
    $classes = match ($couleur) {
        'primaire' => 'bg-primary-100 text-primary-800',
        'succes' => 'bg-success-100 text-success-700',
        'avertissement' => 'bg-warning-100 text-warning-700',
        'danger' => 'bg-danger-100 text-danger-700',
        'info' => 'bg-info-100 text-info-700',
        'accent' => 'bg-accent-100 text-primary-950',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$classes]) }}>{{ $slot }}</span>
