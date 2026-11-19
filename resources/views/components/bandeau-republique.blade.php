{{--
    Bloc institutionnel conforme à la charte graphique du Gouvernement de la
    République de Guinée : armoiries en haut à gauche, sur fond blanc, avec une
    marge libre autour ; « République de Guinée », la devise, le Ministère et la
    Direction à côté des armoiries (jamais au-dessus ni en dessous) ; bande
    tricolore rouge, jaune, vert à bandes égales.

    Les armoiries officielles se placent dans public/images/armoiries.png (ou .svg,
    fichier fourni par la Cellule de Communication du Gouvernement). Tant qu'il est
    absent, l'emplacement public/images/logo-ministere.svg est affiché.

    Variantes : « entete » (barre horizontale en haut de page) et « centre »
    (bloc centré de la page de connexion).
--}}
@props(['variante' => 'entete'])

@php
    $armoiries = collect(['images/armoiries.svg', 'images/armoiries.png'])
        ->first(fn ($f) => file_exists(public_path($f))) ?? 'images/logo-ministere.svg';
@endphp

@if ($variante === 'centre')
    <div {{ $attributes->merge(['class' => 'flex flex-col items-center text-center']) }}>
        <img src="{{ asset($armoiries) }}" alt="Armoiries de la République de Guinée" class="h-20 w-auto object-contain">
        <x-tricolore class="mt-4 w-24" />
        <p class="mt-3 text-sm font-bold uppercase tracking-wider text-slate-900">République de Guinée</p>
        <p class="text-xs italic text-slate-600">Travail – Justice – Solidarité</p>
        <p class="mt-3 text-xs uppercase tracking-wide text-slate-500">Ministère de l'Assainissement, de l'Hydraulique et des Hydrocarbures</p>
        <p class="text-lg font-bold text-dnh-800">Direction Nationale des Hydrocarbures</p>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex min-w-0 items-center gap-3']) }}>
        <span class="shrink-0 bg-white p-1.5">
            <img src="{{ asset($armoiries) }}" alt="Armoiries de la République de Guinée" class="h-11 w-auto object-contain">
        </span>
        <span class="min-w-0 leading-tight">
            <span class="block truncate text-[11px] font-bold uppercase tracking-wider text-slate-900">
                République de Guinée <span class="hidden font-normal normal-case italic tracking-normal text-slate-500 sm:inline">· Travail – Justice – Solidarité</span>
            </span>
            <span class="block truncate text-[11px] uppercase tracking-wide text-slate-500">Ministère de l'Assainissement, de l'Hydraulique et des Hydrocarbures</span>
            <span class="block truncate text-base font-bold text-dnh-800 sm:text-lg">{{ $slot->isEmpty() ? 'Direction Nationale des Hydrocarbures' : $slot }}</span>
        </span>
    </div>
@endif
