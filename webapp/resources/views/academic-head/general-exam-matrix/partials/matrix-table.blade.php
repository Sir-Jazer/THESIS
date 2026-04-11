@php
    $dayGroups = $matrix->slots
        ->groupBy(fn ($slot) => optional($slot->slot_date)->format('Y-m-d'))
        ->sortKeys();

    $days = [];

    for ($dayIndex = 0; $dayIndex < $examDayCount; $dayIndex++) {
        $date = array_keys($dayGroups->all())[$dayIndex] ?? null;
        $slotLookup = collect();

        if ($date !== null) {
            $slotLookup = $dayGroups->get($date, collect())
                ->keyBy(fn ($slot) => substr((string) $slot->start_time, 0, 5) . '|' . substr((string) $slot->end_time, 0, 5));
        }

        $days[] = [
            'label' => 'Day ' . ($dayIndex + 1),
            'date' => $date,
            'slots' => $slotLookup,
        ];
    }
@endphp

<div class="overflow-x-auto rounded-xl border border-slate-200">
    <table class="min-w-[1320px] text-sm">
        <thead class="bg-slate-100 text-slate-700">
            <tr>
                <th class="px-4 py-3 text-left font-semibold w-32 min-w-32">Session</th>
                <th class="px-4 py-3 text-left font-semibold w-56 min-w-56">Time</th>
                @foreach ($days as $day)
                    <th class="px-4 py-3 text-left font-semibold min-w-64">
                        <div>{{ $day['label'] }}</div>
                        <div class="text-xs font-medium text-slate-500 mt-1">{{ $day['date'] ?: 'Date not set' }}</div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white">
            @foreach ($standardPeriods as $periodIndex => $period)
                <tr class="{{ $periodIndex === 3 ? 'border-t-2 border-slate-300' : '' }}">
                    <td class="px-4 py-3 align-top text-slate-600 font-medium">{{ $period['session'] }}</td>
                    <td class="px-4 py-3 align-top text-slate-700 font-medium whitespace-nowrap">{{ $period['label'] }}</td>
                    @foreach ($days as $day)
                        @php
                            $slotKey = $period['start_time'] . '|' . $period['end_time'];
                            $slot = $day['slots']->get($slotKey);
                            $slotSubjects = $slot?->slotSubjects ?? collect();
                        @endphp
                        <td class="px-4 py-3 align-top">
                            @if ($slotSubjects->isNotEmpty())
                                <div class="space-y-2">
                                    @foreach ($slotSubjects as $slotSubject)
                                        @php
                                            $subject = $slotSubject->subject;
                                        @endphp
                                        @if ($subject)
                                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2">
                                                <div class="font-semibold text-emerald-800">{{ $subject->code }}</div>
                                                <div class="text-xs text-emerald-700">{{ $subject->course_serial_number ?: 'No Serial' }}</div>
                                                <div class="text-xs text-emerald-700 mt-1">{{ $subject->name }}</div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-500">
                                    Open slot
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
