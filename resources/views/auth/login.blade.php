<x-guest-layout>
    <div class="space-y-6">
        {{-- Top Header / Welcome --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-heim-200 bg-heim-50 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-[0.18em] text-heim-800">
                    <span class="h-1.5 w-1.5 rounded-full bg-heim-600 animate-pulse"></span>
                    Secure Portal
                </span>
                <span class="text-[11px] font-semibold text-gray-400">POS & Backoffice</span>
            </div>

            <div class="space-y-1.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">Welcome back</h1>
                <p class="text-sm leading-relaxed text-gray-500">Sign in to manage orders, inventory, and daily operations from one clean workspace.</p>
            </div>
        </div>

        {{-- Session Status Banner --}}
        @if(session('status'))
            <div class="flex items-center gap-2.5 rounded-2xl border border-emerald-200 bg-emerald-50/90 p-3.5 text-xs font-semibold text-emerald-800 shadow-xs">
                <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        {{-- Flash Error Banner --}}
        @if(session('error'))
            <div class="flex items-center gap-2.5 rounded-2xl border border-rose-200 bg-rose-50/90 p-3.5 text-xs font-semibold text-rose-800 shadow-xs">
                <svg class="h-4 w-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form id="login-form" method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            {{-- Email Address --}}
            <div>
                <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-gray-700">
                    Email Address <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"/>
                        </svg>
                    </div>
                    <input id="email"
                           type="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           autofocus
                           autocomplete="username"
                           placeholder="name@coffee.com"
                           class="block w-full rounded-xl border {{ $errors->has('email') ? 'border-rose-300 bg-rose-50/30 ring-1 ring-rose-300' : 'border-gray-200 bg-gray-50/60' }} py-2.5 pl-10 pr-3.5 text-sm text-gray-900 placeholder-gray-400 transition duration-150 focus:border-heim-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500/20">
                </div>
                @if($errors->has('email'))
                    <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ $errors->first('email') }}</span>
                    </p>
                @endif
            </div>

            {{-- Password --}}
            <div>
                <div class="mb-1.5 flex items-center justify-between gap-3">
                    <label for="password" class="block text-xs font-bold uppercase tracking-[0.14em] text-gray-700">
                        Password <span class="text-rose-500">*</span>
                    </label>
                    <span class="text-right text-xs font-medium text-gray-500">
                        Forgot password? Contact the Store Owner.
                    </span>
                </div>

                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input id="password"
                           type="password"
                           name="password"
                           required
                           autocomplete="current-password"
                           placeholder="••••••••"
                           class="block w-full rounded-xl border {{ $errors->has('password') ? 'border-rose-300 bg-rose-50/30 ring-1 ring-rose-300' : 'border-gray-200 bg-gray-50/60' }} py-2.5 pl-10 pr-11 text-sm text-gray-900 placeholder-gray-400 transition duration-150 focus:border-heim-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500/20">
                    <button type="button"
                            id="toggle-password-btn"
                            onclick="togglePasswordVisibility()"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition-colors hover:text-gray-700 focus:text-heim-700 focus:outline-none"
                            aria-label="Toggle password visibility">
                        <svg id="eye-icon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg id="eye-slash-icon" class="hidden h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                        </svg>
                    </button>
                </div>
                @if($errors->has('password'))
                    <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ $errors->first('password') }}</span>
                    </p>
                @endif
            </div>

            {{-- Remember Me --}}
            <div class="flex items-center justify-between gap-3">
                <label for="remember_me" class="inline-flex cursor-pointer select-none items-center">
                    <input id="remember_me"
                           type="checkbox"
                           name="remember"
                           class="h-4 w-4 rounded border-gray-300 text-heim-700 shadow-sm transition focus:ring-2 focus:ring-heim-500 focus:ring-offset-1">
                    <span class="ms-2.5 text-sm font-medium text-gray-600">Remember this device</span>
                </label>
            </div>

            {{-- Submit Button --}}
            <div>
                <button type="submit"
                        id="login-submit-btn"
                        class="group flex w-full items-center justify-center gap-2 rounded-xl bg-heim-700 px-4 py-3 text-sm font-bold text-white shadow-md shadow-heim-900/10 transition duration-150 hover:bg-heim-800 hover:shadow-lg hover:shadow-heim-900/20 focus:outline-none focus:ring-2 focus:ring-heim-500 focus:ring-offset-2 active:bg-heim-900">
                    <span>Sign In</span>
                    <svg class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </div>
        </form>

    </div>

    <script>
        function togglePasswordVisibility() {
            const pwd = document.getElementById('password');
            const eye = document.getElementById('eye-icon');
            const eyeSlash = document.getElementById('eye-slash-icon');
            if (!pwd) return;

            if (pwd.type === 'password') {
                pwd.type = 'text';
                if (eye) eye.classList.add('hidden');
                if (eyeSlash) eyeSlash.classList.remove('hidden');
            } else {
                pwd.type = 'password';
                if (eye) eye.classList.remove('hidden');
                if (eyeSlash) eyeSlash.classList.add('hidden');
            }
        }

        document.getElementById('login-form')?.addEventListener('submit', function () {
            const btn = document.getElementById('login-submit-btn');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                btn.classList.add('opacity-80', 'cursor-not-allowed');
                btn.innerHTML = `
                    <svg class="-ml-1 mr-2 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Signing in...</span>
                `;
            }
        });
    </script>
</x-guest-layout>
