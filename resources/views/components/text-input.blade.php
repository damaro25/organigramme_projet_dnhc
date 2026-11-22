@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'champ mt-0']) }}>
