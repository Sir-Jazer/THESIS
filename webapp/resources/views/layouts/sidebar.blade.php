@php
    $role = Auth::user()->role;

    $dashboardRoute = match ($role) {
        'student' => route('student.dashboard'),
        'proctor' => route('proctor.dashboard'),
        'cashier' => route('cashier.dashboard'),
        'academic_head' => route('academic-head.dashboard'),
        'admin' => route('admin.dashboard'),
        default => route('dashboard'),
    };

    $navItems = match ($role) {
        'admin' => [
            [
                'label' => 'Dashboard',
                'href' => route('admin.dashboard'),
                'active' => request()->routeIs('admin.dashboard'),
                'icon' => 'home',
            ],
            [
                'label' => 'Manage Users',
                'href' => route('admin.users.index'),
                'active' => request()->routeIs('admin.users.*'),
                'icon' => 'users',
            ],
            [
                'label' => 'Rooms',
                'href' => route('admin.rooms.index'),
                'active' => request()->routeIs('admin.rooms.*'),
                'icon' => 'rooms',
            ],
            [
                'label' => 'Programs',
                'href' => route('admin.programs.index'),
                'active' => request()->routeIs('admin.programs.*') || request()->routeIs('admin.programs.sections.*'),
                'icon' => 'programs',
            ],
            [
                'label' => 'Subjects',
                'href' => route('admin.subjects.index'),
                'active' => request()->routeIs('admin.subjects.*'),
                'icon' => 'subjects',
            ],
            [
                'label' => 'Academic Timeline',
                'href' => route('admin.settings.edit'),
                'active' => request()->routeIs('admin.settings.*'),
                'icon' => 'settings',
            ],
        ],
        'academic_head' => [
            [
                'label' => 'Dashboard',
                'href' => route('academic-head.dashboard'),
                'active' => request()->routeIs('academic-head.dashboard'),
                'icon' => 'home',
            ],
            [
                'label' => 'Schedules',
                'href' => route('academic-head.schedules.index'),
                'active' => request()->routeIs('academic-head.schedules*'),
                'icon' => 'schedules',
            ],
            [
                'label' => 'General Exam Matrix',
                'href' => route('academic-head.general-exam-matrix.index'),
                'active' => request()->routeIs('academic-head.general-exam-matrix*'),
                'icon' => 'matrix',
            ],
            [
                'label' => 'Subject Exam References',
                'href' => route('academic-head.subject-exam-references.index'),
                'active' => request()->routeIs('academic-head.subject-exam-references*'),
                'icon' => 'subjects',
            ],
            [
                'label' => 'Reports',
                'href' => route('academic-head.reports'),
                'active' => request()->routeIs('academic-head.reports*'),
                'icon' => 'reports',
            ],
        ],
        'student' => [
            [
                'label' => 'Dashboard',
                'href' => route('student.dashboard'),
                'active' => request()->routeIs('student.dashboard'),
                'icon' => 'home',
            ],
            [
                'label' => 'My Subjects',
                'href' => route('student.subjects.index'),
                'active' => request()->routeIs('student.subjects.*'),
                'icon' => 'subjects',
            ],
            [
                'label' => 'My Permit',
                'href' => route('student.permit.show'),
                'active' => request()->routeIs('student.permit.*'),
                'icon' => 'reports',
            ],
        ],
        'cashier' => [
            [
                'label' => 'Dashboard',
                'href' => route('cashier.dashboard'),
                'active' => request()->routeIs('cashier.dashboard'),
                'icon' => 'home',
            ],
            [
                'label' => 'Student Payments',
                'href' => route('cashier.student-payments.index'),
                'active' => request()->routeIs('cashier.student-payments.*'),
                'icon' => 'subjects',
            ],
        ],
        'proctor' => [
            [
                'label' => 'Dashboard',
                'href' => route('proctor.dashboard'),
                'active' => request()->routeIs('proctor.dashboard'),
                'icon' => 'home',
            ],
            [
                'label' => 'QR Scanner',
                'href' => route('proctor.scanner.show'),
                'active' => request()->routeIs('proctor.scanner.*'),
                'icon' => 'qr',
            ],
            [
                'label' => 'Exam Schedules',
                'href' => route('proctor.schedules.index'),
                'active' => request()->routeIs('proctor.schedules.*'),
                'icon' => 'schedules',
            ],
            [
                'label' => 'My Advisees',
                'href' => route('proctor.advisees.index'),
                'active' => request()->routeIs('proctor.advisees.*'),
                'icon' => 'users',
            ],
            [
                'label' => 'Pending Registrations',
                'href' => route('proctor.pending-registrations.index'),
                'active' => request()->routeIs('proctor.pending-registrations.*'),
                'icon' => 'pending',
            ],
        ],
        default => [
            [
                'label' => 'Dashboard',
                'href' => $dashboardRoute,
                'active' => request()->routeIs($role . '.dashboard') || ($role === 'academic_head' && request()->routeIs('academic-head.dashboard')),
                'icon' => 'home',
            ],
            [
                'label' => 'Profile Information',
                'href' => route('profile.edit'),
                'active' => request()->routeIs('profile.*'),
                'icon' => 'profile',
            ],
        ],
    };

    $roleLabel = match ($role) {
        'student' => 'Student Portal',
        'proctor' => 'Proctor Portal',
        'cashier' => 'Cashier Portal',
        'academic_head' => 'Academic Head Portal',
        'admin' => 'System Admin Portal',
        default => 'Portal',
    };
@endphp

<aside
    class="portal-sidebar fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-slate-200 bg-white transition-all duration-300 lg:translate-x-0"
    :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        sidebarCollapsed ? 'lg:w-20' : 'lg:w-72'
    ]"
>
    <div class="flex h-full flex-col pt-16">
        <div class="border-b border-slate-100 px-4 py-4" :class="sidebarCollapsed ? 'lg:px-3' : 'lg:px-4'">
            <p class="portal-sidebar-role" :class="sidebarCollapsed ? 'lg:hidden' : 'lg:block'">{{ $roleLabel }}</p>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            @foreach ($navItems as $item)
                <a
                    href="{{ $item['href'] }}"
                    @click="if (window.innerWidth < 1024) sidebarOpen = false"
                    class="portal-nav-item {{ $item['active'] ? 'portal-nav-item-active' : 'portal-nav-item-idle' }}"
                    :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : 'lg:px-3'"
                    title="{{ $item['label'] }}"
                >
                    <span class="portal-nav-icon">
                        @if ($item['icon'] === 'home')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path d="M3 10.5 12 3l9 7.5" />
                                <path d="M5 9.5V21h14V9.5" />
                            </svg>
                        @elseif ($item['icon'] === 'users')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="8.5" cy="7" r="4" />
                                <path d="M20 8v6" />
                                <path d="M23 11h-6" />
                            </svg>
                        @elseif ($item['icon'] === 'rooms')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <rect x="3" y="4" width="18" height="16" rx="2" />
                                <path d="M3 10h18" />
                                <path d="M8 20v-4" />
                                <path d="M16 20v-4" />
                            </svg>
                        @elseif ($item['icon'] === 'programs')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                                <path d="M6.5 3H20v18H6.5A2.5 2.5 0 0 1 4 18.5V5.5A2.5 2.5 0 0 1 6.5 3z" />
                            </svg>
                        @elseif ($item['icon'] === 'subjects')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path d="M12 3v18" />
                                <path d="M5 7h14" />
                                <path d="M5 17h14" />
                                <path d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
                            </svg>
                        @elseif ($item['icon'] === 'settings')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <circle cx="12" cy="12" r="3" />
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33h.08a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.08a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.08a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                            </svg>
                        @elseif ($item['icon'] === 'schedules')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <rect x="3" y="4" width="18" height="18" rx="2" />
                                <path d="M16 2v4" />
                                <path d="M8 2v4" />
                                <path d="M3 10h18" />
                                <path d="M8 14h3" />
                            </svg>
                        @elseif ($item['icon'] === 'matrix')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <rect x="3" y="3" width="18" height="18" rx="2" />
                                <path d="M3 9h18" />
                                <path d="M3 15h18" />
                                <path d="M9 3v18" />
                                <path d="M15 3v18" />
                            </svg>
                        @elseif ($item['icon'] === 'reports')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <path d="M14 2v6h6" />
                                <path d="M8 13h8" />
                                <path d="M8 17h6" />
                            </svg>
                        @elseif ($item['icon'] === 'qr')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <rect x="3" y="3" width="7" height="7" rx="1" />
                                <rect x="14" y="3" width="7" height="7" rx="1" />
                                <rect x="3" y="14" width="7" height="7" rx="1" />
                                <path d="M14 14h3v3h-3z" />
                                <path d="M17 17h4" />
                                <path d="M17 21v-4" />
                            </svg>
                        @elseif ($item['icon'] === 'pending')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 7v5l3 3" />
                            </svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 8v8" />
                                <path d="M8 12h8" />
                            </svg>
                        @endif
                    </span>
                    <span class="portal-nav-label" :class="sidebarCollapsed ? 'lg:hidden' : 'lg:inline'">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="border-t border-slate-100 p-3" :class="sidebarCollapsed ? 'lg:px-2' : 'lg:px-3'">
            <a
                href="{{ route('profile.edit') }}"
                @click="if (window.innerWidth < 1024) sidebarOpen = false"
                class="portal-nav-item {{ request()->routeIs('profile.*') ? 'portal-nav-item-active' : 'portal-nav-item-idle' }}"
                :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : 'lg:px-3'"
                title="Profile"
            >
                <span class="portal-nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </span>
                <span class="portal-nav-label" :class="sidebarCollapsed ? 'lg:hidden' : 'lg:inline'">Profile</span>
            </a>
        </div>
    </div>
</aside>
