<nav aria-label="Section tabs" class="mb-6 border-b border-slate-200 print:hidden">
    <div class="flex gap-1 overflow-x-auto">
        @foreach($tabs as $tab)
            @php
                $isActiveTab = request()->routeIs(...$tab['active']);
            @endphp
            <a href="{{ route($tab['route']) }}"
               @if($isActiveTab) aria-current="page" @endif
               class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ $isActiveTab ? 'border-heim-600 text-heim-800' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
