{{-- Pied de page : la charte demande un lien vers le portail officiel du Gouvernement. --}}
<footer {{ $attributes->merge(['class' => 'border-t border-fond-bord bg-white px-4 py-2 text-xs text-slate-500 lg:px-6']) }}>
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
        <span>République de Guinée · Ministère de l'Assainissement, de l'Hydraulique et des Hydrocarbures · Direction Nationale des Hydrocarbures</span>
        <a href="https://gouvernement.gov.gn" target="_blank" rel="noopener" class="font-medium text-dnh-700 hover:underline">Portail officiel du Gouvernement ↗</a>
    </div>
</footer>
