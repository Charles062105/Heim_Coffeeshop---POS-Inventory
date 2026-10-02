<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-rose-600 border border-transparent rounded-xl font-bold text-sm text-white shadow-sm hover:bg-rose-700 active:scale-[0.99] focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:opacity-50 transition-all']) }}>
    {{ $slot }}
</button>
