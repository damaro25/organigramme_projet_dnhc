{{-- Pied de page sobre, marine : institution de tutelle, © DNH et portail officiel du Gouvernement. --}}
<footer {{ $attributes->merge(['class' => 'bg-primary-950 px-4 py-3 text-xs text-primary-200 lg:px-8']) }}>
    <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1">
        <span>© {{ now()->year }} Direction Nationale des Hydrocarbures · République de Guinée · Ministère de l'Assainissement, de l'Hydraulique et des Hydrocarbures</span>
        <a href="https://gouvernement.gov.gn" target="_blank" rel="noopener" class="font-medium text-white hover:text-accent-500 hover:underline">Portail officiel du Gouvernement ↗</a>
    </div>
</footer>
