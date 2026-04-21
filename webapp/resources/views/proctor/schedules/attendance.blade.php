<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Slot Attendance</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Slot info card --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap gap-6 text-sm">
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Section</p>
                        <p class="mt-0.5 font-semibold text-slate-800">{{ $slot->schedule?->section?->section_code ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Subject</p>
                        <p class="mt-0.5 font-semibold text-slate-800">{{ $slot->subject?->code ?? 'TBA' }}</p>
                        @if ($slot->subject?->name)
                            <p class="text-xs text-slate-500">{{ $slot->subject->name }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Date</p>
                        <p class="mt-0.5 text-slate-700">{{ optional($slot->slot_date)->format('F j, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Time</p>
                        <p class="mt-0.5 text-slate-700">{{ substr((string) $slot->start_time, 0, 5) }}–{{ substr((string) $slot->end_time, 0, 5) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Room</p>
                        <p class="mt-0.5 text-slate-700">{{ $slot->room?->name ?? 'TBA' }}</p>
                    </div>
                </div>
            </div>

            {{-- Attendance table --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Attendance Log</h3>
                    <span class="text-sm text-slate-500">{{ $attendances->count() }} {{ Str::plural('student', $attendances->count()) }}</span>
                </div>

                @if ($attendances->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-slate-500">
                        No attendance has been recorded for this slot yet.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-slate-700">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold">#</th>
                                    <th class="px-4 py-2 text-left font-semibold">Student Name</th>
                                    <th class="px-4 py-2 text-left font-semibold">Student ID</th>
                                    <th class="px-4 py-2 text-left font-semibold">Logged At</th>
                                    <th class="px-4 py-2 text-left font-semibold">Logged By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($attendances as $i => $attendance)
                                    @php
                                        $student = $attendance->studentProfile?->user;
                                        $studentProfile = $attendance->studentProfile;
                                    @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 text-slate-500">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-800">
                                            {{ trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? '')) ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $studentProfile?->student_id ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                            {{ optional($attendance->logged_at)->format('Y-m-d H:i') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            {{ trim(($attendance->logger?->first_name ?? '') . ' ' . ($attendance->logger?->last_name ?? '')) ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div>
                <a href="{{ route('proctor.schedules.index') }}"
                    class="inline-flex items-center text-sm text-blue-600 hover:underline">
                    &larr; Back to Exam Schedules
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
