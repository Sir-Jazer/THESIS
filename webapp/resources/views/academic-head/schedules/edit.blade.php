<x-app-layout>
    @php
        /** @var \App\Models\SectionExamSchedule $schedule */
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Schedule</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8 space-y-5">
            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <h3 class="font-semibold text-slate-800">Context</h3>
                <p class="mt-1 text-sm text-slate-600">
                    Academic Year: <span class="font-semibold">{{ $setting?->academic_year ?? $schedule->academic_year }}</span>
                    | Semester: <span class="font-semibold">{{ $schedule->semester === 1 ? '1st Semester' : '2nd Semester' }}</span>
                    | Exam Period: <span class="font-semibold">{{ $schedule->exam_period }}</span>
                </p>
                <p class="text-sm text-slate-600">
                    Program: <span class="font-semibold">{{ $schedule->section?->program?->code }}</span>
                    | Year Level: <span class="font-semibold">{{ $schedule->section?->year_level }}</span>
                    | Section: <span class="font-semibold">{{ $schedule->section?->section_code }}</span>
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="border-b px-4 py-3 flex flex-wrap gap-2 items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-gray-800">{{ $schedule->section?->program?->code }} - {{ $schedule->section?->section_code }}</h4>
                        <p class="text-xs text-slate-500">Status: {{ ucfirst($schedule->status) }}</p>
                    </div>
                    <a href="{{ route('academic-head.schedules.index', $filters) }}" class="px-3 py-2 text-xs rounded bg-slate-600 text-white hover:bg-slate-700 font-semibold">Back to Schedules</a>
                </div>

                <div class="px-4 py-2 border-b bg-amber-50">
                    <p class="text-xs text-amber-800">
                        <span class="inline-block w-3 h-3 rounded-sm border border-amber-400 bg-amber-100 align-middle mr-1"></span>
                        Yellow subject dropdown means the selected subject is strictly from the General Exam Matrix.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th class="px-3 py-2 text-left">Time</th>
                                <th class="px-3 py-2 text-left">Subject</th>
                                <th class="px-3 py-2 text-left">Room</th>
                                <th class="px-3 py-2 text-left">Proctor</th>
                                <th class="px-3 py-2 text-left">Action</th>
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
                                    <td class="px-3 py-2 font-semibold text-slate-800" colspan="5">Day {{ $dayCounter }} - {{ $slotDate }}</td>
                                </tr>

                                @foreach ($slots as $slot)
                                    @php
                                        $roomAvailability = $roomAvailabilityBySlot[$slot->id] ?? ['conflict' => [], 'capacity' => []];
                                        $proctorUnavailable = $proctorAvailabilityBySlot[$slot->id] ?? [];
                                        $matrixSubjectIds = (array) ($matrixAssignedSubjectBySlot[$slot->id] ?? []);
                                        $isMatrixAssignedSelection = count($matrixSubjectIds) > 0 && in_array((int) $slot->subject_id, $matrixSubjectIds, true);
                                    @endphp
                                    <tr class="border-t align-top js-slot-row" data-slot-id="{{ $slot->id }}">
                                        <td class="px-3 py-2 whitespace-nowrap">{{ substr((string) $slot->start_time, 0, 5) }}-{{ substr((string) $slot->end_time, 0, 5) }}</td>
                                        <td class="px-3 py-2">
                                            <form method="POST" action="{{ route('academic-head.schedules.slots.update', $slot) }}" class="js-slot-form space-y-2" data-slot-id="{{ $slot->id }}">
                                                @csrf
                                                @method('PATCH')
                                                <select
                                                    name="subject_id"
                                                    class="w-72 rounded-md border-gray-300 text-sm transition-colors"
                                                    data-slot-subject-select="true"
                                                    data-matrix-subject-ids='@json($matrixSubjectIds)'
                                                    style="{{ $isMatrixAssignedSelection ? 'background-color:#fef9c3;border-color:#f59e0b;color:#78350f;font-weight:600;' : '' }}"
                                                    @disabled($schedule->status === 'published')
                                                >
                                                    <option value="">Unassigned</option>
                                                    @foreach ($subjectOptions as $subjectOption)
                                                        @php
                                                            $isMatrixOption = in_array((int) $subjectOption['id'], $matrixSubjectIds, true);
                                                        @endphp
                                                        <option value="{{ $subjectOption['id'] }}"
                                                            @selected((int) $slot->subject_id === (int) $subjectOption['id'])
                                                            style="{{ $isMatrixOption ? 'background-color:#fef9c3;font-weight:600;' : '' }}">
                                                            {{ $subjectOption['code'] }} | {{ $subjectOption['course_serial_number'] ?: 'No Serial' }} - {{ $subjectOption['name'] }}
                                                            {{ $isMatrixOption ? '(General Matrix Assigned)' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                        </td>
                                        <td class="px-3 py-2">
                                                <select name="room_id" class="w-56 rounded-md border-gray-300 text-sm" @disabled($schedule->status === 'published')>
                                                    <option value="">Unassigned</option>
                                                    @foreach ($rooms as $room)
                                                        @php
                                                            $roomId = (int) $room->id;
                                                            $isConflictRoom = in_array($roomId, $roomAvailability['conflict'] ?? [], true);
                                                            $isCapacityRoom = in_array($roomId, $roomAvailability['capacity'] ?? [], true);
                                                            $isUnavailableRoom = $isConflictRoom || $isCapacityRoom;
                                                            $reasonLabel = $isConflictRoom
                                                                ? 'Unavailable: conflict'
                                                                : ($isCapacityRoom ? 'Unavailable: capacity' : null);
                                                        @endphp
                                                        <option value="{{ $room->id }}"
                                                            @selected((int) $slot->room_id === $roomId)
                                                            @disabled($isUnavailableRoom)
                                                        >
                                                            {{ $room->name }} ({{ $room->capacity }}){{ $reasonLabel ? ' - ' . $reasonLabel : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                        </td>
                                        <td class="px-3 py-2">
                                                <select name="proctor_ids[]" multiple class="w-72 rounded-md border-gray-300 text-sm" @disabled($schedule->status === 'published')>
                                                    @foreach ($proctors as $proctor)
                                                        @php($proctorId = (int) $proctor->id)
                                                        <option value="{{ $proctor->id }}"
                                                            @selected($slot->proctors->contains('id', $proctor->id))
                                                            @disabled(in_array($proctorId, $proctorUnavailable, true))>
                                                            {{ $proctor->full_name }}{{ in_array($proctorId, $proctorUnavailable, true) ? ' - Unavailable' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                        </td>
                                        <td class="px-3 py-2">
                                                <button type="submit" class="px-3 py-1.5 text-xs rounded bg-blue-600 text-white hover:bg-blue-700" @disabled($schedule->status === 'published')>
                                                    Save Slot
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach

                                @php($dayCounter++)
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <form id="save-draft-form" method="POST" action="{{ route('academic-head.schedules.save-draft', $schedule) }}" class="bg-white shadow-sm sm:rounded-lg p-4 flex items-center gap-3">
                @csrf
                <div id="save-draft-payload"></div>
                <button type="submit" class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700 font-semibold" @disabled($schedule->status === 'published')>Save Draft</button>
                <span class="text-xs text-slate-500">This collects all visible row values and saves them as draft in one request.</span>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const draftForm = document.getElementById('save-draft-form');
            const payloadContainer = document.getElementById('save-draft-payload');
            const slotForms = document.querySelectorAll('.js-slot-form');
            if (!draftForm || !payloadContainer) {
                return;
            }

            const syncMatrixSubjectIndicator = (subjectSelect) => {
                if (!(subjectSelect instanceof HTMLSelectElement)) {
                    return;
                }

                const matrixSubjectIds = JSON.parse(subjectSelect.dataset.matrixSubjectIds || '[]');
                const selectedSubjectId = Number(subjectSelect.value || 0);
                const isMatrixSelection = matrixSubjectIds.length > 0 && matrixSubjectIds.includes(selectedSubjectId);

                if (isMatrixSelection) {
                    subjectSelect.style.backgroundColor = '#fef9c3';
                    subjectSelect.style.borderColor = '#f59e0b';
                    subjectSelect.style.color = '#78350f';
                    subjectSelect.style.fontWeight = '600';
                    return;
                }

                subjectSelect.style.backgroundColor = '';
                subjectSelect.style.borderColor = '';
                subjectSelect.style.color = '';
                subjectSelect.style.fontWeight = '';
            };

            const subjectSelects = document.querySelectorAll('[data-slot-subject-select="true"]');
            subjectSelects.forEach((selectElement) => {
                syncMatrixSubjectIndicator(selectElement);

                selectElement.addEventListener('change', function () {
                    syncMatrixSubjectIndicator(this);
                });
            });

            draftForm.addEventListener('submit', function () {
                payloadContainer.innerHTML = '';

                slotForms.forEach((slotForm) => {
                    const slotId = slotForm.getAttribute('data-slot-id');
                    if (!slotId) {
                        return;
                    }

                    const subjectSelect = slotForm.querySelector('select[name="subject_id"]');
                    const roomSelect = slotForm.querySelector('select[name="room_id"]');
                    const proctorSelect = slotForm.querySelector('select[name="proctor_ids[]"]');

                    const subjectInput = document.createElement('input');
                    subjectInput.type = 'hidden';
                    subjectInput.name = `slots[${slotId}][subject_id]`;
                    subjectInput.value = subjectSelect ? subjectSelect.value : '';
                    payloadContainer.appendChild(subjectInput);

                    const roomInput = document.createElement('input');
                    roomInput.type = 'hidden';
                    roomInput.name = `slots[${slotId}][room_id]`;
                    roomInput.value = roomSelect ? roomSelect.value : '';
                    payloadContainer.appendChild(roomInput);

                    if (proctorSelect instanceof HTMLSelectElement) {
                        Array.from(proctorSelect.selectedOptions).forEach((option) => {
                            const proctorInput = document.createElement('input');
                            proctorInput.type = 'hidden';
                            proctorInput.name = `slots[${slotId}][proctor_ids][]`;
                            proctorInput.value = option.value;
                            payloadContainer.appendChild(proctorInput);
                        });
                    }
                });
            });
        });
    </script>
</x-app-layout>
