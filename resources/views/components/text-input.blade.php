@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-dnh-500 focus:ring-dnh-500 rounded-md shadow-sm']) }}>
