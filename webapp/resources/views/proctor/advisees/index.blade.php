<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Advisees</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if ($adviseesBySection->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-slate-500">No advisory section assigned to your account.</p>
                </div>
            @else
                @foreach ($adviseesBySection as $sectionId => $advisees)
                    @php($sectionLabel = $advisees->first()?->section?->section_code ?? 'Section')
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="font-semibold text-slate-800">{{ $sectionLabel }}</h3>
                            <span class="text-xs text-slate-500">{{ $advisees->count() }} {{ Str::plural('student', $advisees->count()) }}</span>
                        </div>

                        @if ($advisees->isEmpty())
                            <div class="px-6 py-6 text-sm text-slate-500">No students in this section yet.</div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50 text-slate-700">
                                        <tr>
                                            <th class="px-4 py-2 text-left font-semibold">Name</th>
                                            <th class="px-4 py-2 text-left font-semibold">Student ID</th>
                                            <th class="px-4 py-2 text-left font-semibold">Program</th>
                                            <th class="px-4 py-2 text-left font-semibold">Year Level</th>
                                            <th class="px-4 py-2 text-left font-semibold">Account Status</th>
                                            <th class="px-4 py-2 text-left font-semibold">Permit ({{ $setting?->exam_period ?? '—' }})</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($advisees->sortBy(fn($a) => $a->user?->last_name) as $advisee)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-3 font-medium text-slate-800">
                                                    {{ trim(($advisee->user?->first_name ?? '') . ' ' . ($advisee->user?->last_name ?? '')) ?: '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-slate-600">{{ $advisee->student_id ?? '—' }}</td>
                                                <td class="px-4 py-3 text-slate-600">{{ $advisee->program?->code ?? '—' }}</td>
                                                <td class="px-4 py-3 text-slate-600">{{ $advisee->year_level ? 'Year ' . $advisee->year_level : '—' }}</td>
                                                <td class="px-4 py-3">
                                                    @php($status = $advisee->user?->status ?? 'unknown')
                                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold
                                                        {{ $status === 'active' ? 'bg-green-100 text-green-800' : ($status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-slate-100 text-slate-600') }}">
                                                        {{ ucfirst($status) }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if ($advisee->current_permit)
                                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-800">Issued</span>
                                                    @else
                                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold bg-red-100 text-red-700">Not Issued</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif

        </div>
    </div>
</x-app-layout>
