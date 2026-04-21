<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cashier Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-semibold text-slate-900">Welcome, {{ auth()->user()->full_name }}.</h3>
                <p class="mt-2 text-sm text-slate-600">Current context: {{ $setting?->academic_year ?? 'Not set' }} | {{ $setting?->semester ?? 'Not set' }} | {{ $setting?->exam_period ?? 'Not set' }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-medium text-slate-500">Enrolled Students</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $studentCount }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-medium text-slate-500">Generated Permits</p>
                    <p class="mt-3 text-3xl font-semibold text-emerald-600">{{ $issuedPermitCount }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-medium text-slate-500">Pending Permits</p>
                    <p class="mt-3 text-3xl font-semibold text-amber-500">{{ $pendingPermitCount }}</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Permit Processing</h3>
                        <p class="mt-1 text-sm text-slate-600">Open the Student Payments page to generate or revoke QR permits for the active exam period.</p>
                    </div>
                    <a href="{{ route('cashier.student-payments.index') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Open Student Payments</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
