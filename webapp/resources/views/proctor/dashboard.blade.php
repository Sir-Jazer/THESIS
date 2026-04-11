<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proctor Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">Welcome, {{ auth()->user()->full_name }}.</div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-3">
                    <h3 class="font-semibold text-gray-800">Published Invigilation Assignments</h3>

                    @if ($publishedAssignments->isEmpty())
                        <p class="text-sm text-gray-500">No published assignments yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-100 text-slate-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Date</th>
                                        <th class="px-3 py-2 text-left">Time</th>
                                        <th class="px-3 py-2 text-left">Subject</th>
                                        <th class="px-3 py-2 text-left">Room</th>
                                        <th class="px-3 py-2 text-left">Section</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($publishedAssignments as $slot)
                                        <tr class="border-t">
                                            <td class="px-3 py-2">{{ optional($slot->slot_date)->format('Y-m-d') }}</td>
                                            <td class="px-3 py-2">{{ substr((string) $slot->start_time, 0, 5) }} - {{ substr((string) $slot->end_time, 0, 5) }}</td>
                                            <td class="px-3 py-2">
                                                @if ($slot->subject)
                                                    <div class="font-semibold text-slate-800">{{ $slot->subject->code }}</div>
                                                    <div class="text-xs text-slate-600">Course Serial: {{ $slot->subject->course_serial_number ?: 'Not set' }}</div>
                                                    <div class="text-xs text-slate-600">Exam Ref: {{ $slot->exam_reference_number ?: 'Not set' }}</div>
                                                @else
                                                    TBA
                                                @endif
                                            </td>
                                            <td class="px-3 py-2">{{ $slot->room?->name ?? 'TBA' }}</td>
                                            <td class="px-3 py-2">{{ $slot->schedule?->section?->section_code }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
