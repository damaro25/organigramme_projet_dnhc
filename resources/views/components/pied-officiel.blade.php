{{-- Pied de page : la charte demande un lien vers le portail officiel du Gouvernement. --}}
<footer {{ $attributes->merge(['class' => 'border-t border-fond-bord bg-white px-4 py-2.5 text-xs text-slate-600 lg:px-6']) }}>
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
        <span class="flex min-w-0 items-center gap-2">
            <x-logo-dnh variante="embleme" class="h-4 w-auto shrink-0 text-dnh-950" aria-hidden="true" />
            <span>© {{ date('Y') }} <span class="font-semibold text-dnh-950">Direction Nationale des Hydrocarbures</span> · Ministère de l'Assainissement, de l'Hydraulique et des Hydrocarbures · République de Guinée</span>
        </span>
        <a href="https://gouvernement.gov.gn" target="_blank" rel="noopener" class="font-medium text-dnh-700 hover:text-dnh-950 hover:underline">Portail officiel du Gouvernement ↗<span class="sr-only"> (nouvel onglet)</span></a>
    </div>
</footer>
