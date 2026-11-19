<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-md bg-red-700 px-4 py-2 font-titre text-[15px] font-semibold uppercase tracking-wider text-white shadow-sm transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
