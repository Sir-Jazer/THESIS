@php
    /** @var \App\Models\SectionExamSchedule $schedule */
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-slate-700">
            <tr>
                <th class="px-3 py-2 text-left">Time</th>
                <th class="px-3 py-2 text-left">Subject</th>
                <th class="px-3 py-2 text-left">Room</th>
                <th class="px-3 py-2 text-left">Proctor</th>
            </tr>
        </thead>
        <tbody>
            @php
                $slotsByDate = $schedule->slots
                    ->groupBy(fn ($slot) => optional($slot->slot_date)->format('Y-m-d'));
                $dayCounter = 1;
            @endphp

            @foreach ($slotsByDate as $slotDate => $slots)
                <tr class="bg-slate-200 border-t border-slate-300">
                    <td class="px-3 py-2 font-semibold text-slate-800" colspan="4">Day {{ $dayCounter }} - {{ $slotDate }}</td>
                </tr>

                @foreach ($slots as $slot)
                    <tr class="border-t align-top">
                        <td class="px-3 py-2 whitespace-nowrap">{{ substr((string) $slot->start_time, 0, 5) }}-{{ substr((string) $slot->end_time, 0, 5) }}</td>
                        <td class="px-3 py-2">{{ $slot->subject?->name ?? 'Unassigned' }}</td>
                        <td class="px-3 py-2">{{ $slot->room?->name ?? 'Unassigned' }}</td>
                        <td class="px-3 py-2">{{ $slot->proctors->pluck('full_name')->filter()->join(', ') ?: 'Unassigned' }}</td>
                    </tr>
                @endforeach

                @php($dayCounter++)
            @endforeach
        </tbody>
    </table>
</div>
