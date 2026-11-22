{{--
    Mention institutionnelle discrète, en complément du logo de la DNH :
    « République de Guinée », la devise et le ministère de tutelle.
      variante : sombre (texte clair sur marine, par défaut) ou clair (texte marine).
--}}
@props(['variante' => 'sombre'])

<div {{ $attributes->merge(['class' => 'leading-tight']) }}>
    <p @class(['font-display text-xs font-semibold uppercase tracking-[0.12em]', 'text-white' => $variante === 'sombre', 'text-primary-950' => $variante !== 'sombre'])>
        République de Guinée
    </p>
    <p @class(['text-[11px] italic', 'text-primary-200' => $variante === 'sombre', 'text-slate-600' => $variante !== 'sombre'])>Travail – Justice – Solidarité</p>
    <p @class(['mt-0.5 text-[11px]', 'text-primary-200' => $variante === 'sombre', 'text-slate-600' => $variante !== 'sombre'])>Ministère de l'Assainissement, de l'Hydraulique et des Hydrocarbures</p>
</div>
