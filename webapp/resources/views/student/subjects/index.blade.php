<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Subjects</h2>
    </x-slot>

    @php
        $periodIndex = array_search($selectedPeriod, $periods, true);
        $previousPeriod = $periodIndex !== false && $periodIndex > 0 ? $periods[$periodIndex - 1] : null;
        $nextPeriod = $periodIndex !== false && $periodIndex < count($periods) - 1 ? $periods[$periodIndex + 1] : null;
        $semesterLabel = $setting?->semester ?? 'Not set';
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-600"><span class="font-semibold text-slate-900">Academic Year:</span> {{ $setting?->academic_year ?? 'Not set' }}</p>
                <p class="mt-2 text-sm text-slate-600"><span class="font-semibold text-slate-900">Term:</span> {{ $semesterLabel }}</p>
                <p class="mt-2 text-sm text-slate-600"><span class="font-semibold text-slate-900">Exam Period:</span> {{ $selectedPeriod }}</p>
                <p class="mt-2 text-sm text-slate-600"><span class="font-semibold text-slate-900">Adviser:</span> {{ $adviserName }}</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th class="rounded-l-xl px-4 py-3 text-left font-semibold">Subject Name</th>
                                <th class="px-4 py-3 text-left font-semibold">Exam Date</th>
                                <th class="px-4 py-3 text-left font-semibold">Time</th>
                                <th class="px-4 py-3 text-left font-semibold">Room</th>
                                <th class="px-4 py-3 text-left font-semibold">Proctor</th>
                                <th class="rounded-r-xl px-4 py-3 text-left font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($subjectRows as $row)
                                <tr>
                                    <td class="px-4 py-4 text-slate-800">
                                        <div class="font-medium">{{ $row['subject_name'] }}</div>
                                        @if ($row['subject_code'])
                                            <div class="text-xs text-slate-500">{{ $row['subject_code'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">{{ optional($row['date'])->format('Y-m-d') }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ $row['time_label'] }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ $row['room_name'] }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ $row['proctor_name'] }}</td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $row['status'] === 'Cleared' ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No published subjects found for {{ $selectedPeriod }} in the current academic timeline.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex items-center justify-between gap-4">
                    <div>
                        @if ($previousPeriod)
                            <a href="{{ route('student.subjects.index', ['period' => $previousPeriod]) }}" class="inline-flex items-center rounded-lg bg-slate-200 px-4 py-2 font-semibold text-slate-700 transition hover:bg-slate-300">
                                ← Previous
                            </a>
                        @else
                            <span class="inline-flex cursor-not-allowed items-center rounded-lg bg-slate-200 px-4 py-2 font-semibold text-slate-400">← Previous</span>
                        @endif
                    </div>
                    <div class="text-sm font-semibold text-slate-800">{{ $selectedPeriod }}</div>
                    <div>
                        @if ($nextPeriod)
                            <a href="{{ route('student.subjects.index', ['period' => $nextPeriod]) }}" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 font-semibold text-white transition hover:bg-slate-800">
                                Next →
                            </a>
                        @else
                            <span class="inline-flex cursor-not-allowed items-center rounded-lg bg-slate-900 px-4 py-2 font-semibold text-slate-400">Next →</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
