<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Student Dashboard</h2>
    </x-slot>

    @php
        $clearedCount = $subjectRows->where('status', 'Cleared')->count();
        $pendingCount = $subjectRows->where('status', 'Pending')->count();
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-semibold text-slate-900">Welcome, {{ auth()->user()->full_name }}.</h3>
                <p class="mt-2 text-sm text-slate-600">Current context: {{ $setting?->academic_year ?? 'Not set' }} | {{ $setting?->semester ?? 'Not set' }} | {{ $selectedPeriod }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-medium text-slate-500">Scheduled Subjects</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $subjectRows->count() }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-medium text-slate-500">Cleared Exams</p>
                    <p class="mt-3 text-3xl font-semibold text-emerald-600">{{ $clearedCount }}</p>
                </div>
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <p class="text-sm font-medium text-slate-500">Permit Status</p>
                    <p class="mt-3 text-3xl font-semibold {{ $currentPermit ? 'text-emerald-600' : 'text-amber-500' }}">{{ $currentPermit ? 'Ready' : 'Pending' }}</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Current Period Schedule</h3>
                        <p class="mt-1 text-sm text-slate-600">A quick view of your {{ $selectedPeriod }} exam schedule and attendance status.</p>
                    </div>
                    <a href="{{ route('student.subjects.index', ['period' => $selectedPeriod]) }}" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Open My Subjects</a>
                </div>

                @if ($subjectRows->isEmpty())
                    <p class="mt-6 text-sm text-slate-500">No published exam schedule yet for the current period.</p>
                @else
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-100 text-slate-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold">Subject</th>
                                    <th class="px-4 py-3 text-left font-semibold">Date</th>
                                    <th class="px-4 py-3 text-left font-semibold">Time</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($subjectRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800">{{ $row['subject_name'] }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ optional($row['date'])->format('Y-m-d') }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $row['time_label'] }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $row['status'] === 'Cleared' ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }}">{{ $row['status'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
