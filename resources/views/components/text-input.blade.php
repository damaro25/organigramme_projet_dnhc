@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-md border-slate-300 shadow-sm transition placeholder:text-slate-400 focus:border-dnh-500 focus:ring-dnh-500 disabled:bg-slate-50 disabled:text-slate-500']) }}>
