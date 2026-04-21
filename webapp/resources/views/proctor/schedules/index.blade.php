<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Exam Schedules</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Period navigation --}}
            <div class="flex flex-wrap gap-2">
                @foreach ($periods as $period)
                    <a href="{{ route('proctor.schedules.index', ['period' => $period]) }}"
                        class="px-4 py-1.5 rounded-full text-sm font-medium transition
                            {{ $period === $selectedPeriod
                                ? 'bg-blue-600 text-white'
                                : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
                        {{ $period }}
                    </a>
                @endforeach
            </div>

            @if ($slotsBySchedule->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-slate-500">No published assignments for {{ $selectedPeriod }}.</p>
                </div>
            @else
                @foreach ($slotsBySchedule as $scheduleId => $slots)
                    @php($schedule = $schedules->get($scheduleId))
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="font-semibold text-slate-800">{{ $schedule?->section?->section_code ?? 'Section' }}</span>
                                @if ($schedule?->section?->program)
                                    <span class="ml-2 text-xs text-slate-500">{{ $schedule->section->program->code }}</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-500">{{ $selectedPeriod }}</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-slate-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold">Date</th>
                                        <th class="px-4 py-2 text-left font-semibold">Time</th>
                                        <th class="px-4 py-2 text-left font-semibold">Subject</th>
                                        <th class="px-4 py-2 text-left font-semibold">Room</th>
                                        <th class="px-4 py-2 text-left font-semibold">Attendance Count</th>
                                        <th class="px-4 py-2 text-left font-semibold">Section Attendance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($slots->sortBy(['slot_date', 'start_time']) as $slot)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-3 whitespace-nowrap">{{ optional($slot->slot_date)->format('Y-m-d') }}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">{{ substr((string) $slot->start_time, 0, 5) }}–{{ substr((string) $slot->end_time, 0, 5) }}</td>
                                            <td class="px-4 py-3">
                                                @if ($slot->subject)
                                                    <div class="font-medium text-slate-800">{{ $slot->subject->code }}</div>
                                                    <div class="text-xs text-slate-500">{{ $slot->subject->name }}</div>
                                                @else
                                                    <span class="text-slate-400">TBA</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">{{ $slot->room?->name ?? 'TBA' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                                    {{ $slot->attendances_count ?? $slot->attendances()->count() }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <a href="{{ route('proctor.schedules.attendance', $slot) }}"
                                                    class="inline-flex items-center px-3 py-1.5 rounded-md bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition">
                                                    View Table
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif

        </div>
    </div>
</x-app-layout>
