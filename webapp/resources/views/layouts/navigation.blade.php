<nav class="portal-header fixed inset-x-0 top-0 z-50 border-b border-portal-header-border">
    @php
        $portalRoute = match (Auth::user()->role) {
            'student' => route('student.dashboard'),
            'proctor' => route('proctor.dashboard'),
            'cashier' => route('cashier.dashboard'),
            'academic_head' => route('academic-head.dashboard'),
            'admin' => route('admin.dashboard'),
            default => '/',
        };

        $roleLabel = match (Auth::user()->role) {
            'student' => 'Student',
            'proctor' => 'Proctor',
            'cashier' => 'Cashier',
            'academic_head' => 'Academic Head',
            'admin' => 'Administrator',
            default => 'User',
        };

        $logoCandidates = [
            'sti_logo_full.png',
            'sti_logo.png',
        ];

        $logoAsset = null;

        foreach ($logoCandidates as $candidate) {
            if (file_exists(public_path($candidate))) {
                $logoAsset = asset($candidate);
                break;
            }
        }

        $initials = strtoupper(substr((string) Auth::user()->first_name, 0, 1) . substr((string) Auth::user()->last_name, 0, 1));
    @endphp

    <div class="h-16 w-full px-3 sm:px-4 lg:px-6">
        <div class="flex h-full items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <button
                    type="button"
                    @click="if (window.innerWidth >= 1024) { sidebarCollapsed = !sidebarCollapsed } else { sidebarOpen = !sidebarOpen }"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/35 bg-white/10 text-white transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/50"
                    aria-label="Toggle navigation menu"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18" />
                        <path d="M3 12h18" />
                        <path d="M3 18h18" />
                    </svg>
                </button>

                <a href="{{ $portalRoute }}" class="flex min-w-0 items-center gap-3">
                    @if ($logoAsset)
                        <img src="{{ $logoAsset }}" alt="STI Logo" class="h-9 w-auto object-contain" />
                    @else
                        <span class="inline-flex h-9 items-center rounded-md bg-blue-700 px-3 text-sm font-semibold tracking-wide text-white">STI</span>
                    @endif
                    <div class="hidden min-w-0 sm:block">
                        <p class="truncate text-xl font-semibold leading-tight text-white">E-Permit System</p>

                    </div>
                </a>
            </div>

            <div class="relative" @keydown.escape.window="profileOpen = false">
                <button
                    type="button"
                    @click="profileOpen = !profileOpen"
                    class="inline-flex items-center gap-3 rounded-xl border border-white/35 bg-white/10 px-3 py-2 text-left shadow-sm backdrop-blur transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/50"
                    aria-haspopup="menu"
                    :aria-expanded="profileOpen.toString()"
                >
                    <span class="hidden text-right sm:block">
                        <span class="block text-sm font-semibold leading-tight text-white">{{ Auth::user()->full_name }}</span>
                        <span class="block text-xs text-white/85">{{ $roleLabel }}</span>
                    </span>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-sm font-semibold text-blue-700">{{ $initials }}</span>
                    <svg class="hidden h-4 w-4 text-white/80 sm:block" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.177l3.71-3.946a.75.75 0 011.08 1.04l-4.25 4.52a.75.75 0 01-1.08 0l-4.25-4.52a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div
                    x-show="profileOpen"
                    x-transition.origin.top.right
                    @click.outside="profileOpen = false"
                    class="portal-profile-menu absolute right-0 z-20 mt-2 w-56 overflow-hidden rounded-xl"
                    x-cloak
                >
                    <div class="portal-profile-menu-head px-4 py-3">
                        <p class="text-sm font-semibold">{{ Auth::user()->full_name }}</p>
                        <p class="text-xs">{{ Auth::user()->email }}</p>
                    </div>
                    <div class="py-1.5">
                        <button
                            type="button"
                            @click="toggleTheme()"
                            class="portal-profile-menu-item flex w-full items-center justify-between px-4 py-2 text-sm"
                            :aria-pressed="(theme === 'dark').toString()"
                        >
                            <span>Dark Mode</span>
                            <span class="portal-theme-pill" :class="theme === 'dark' ? 'portal-theme-pill-on' : 'portal-theme-pill-off'" x-text="theme === 'dark' ? 'On' : 'Off'"></span>
                        </button>

                        <a href="{{ route('profile.edit') }}" class="portal-profile-menu-item block px-4 py-2 text-sm">
                            Profile Information
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="portal-profile-menu-item portal-profile-menu-item-danger block w-full px-4 py-2 text-left text-sm">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
