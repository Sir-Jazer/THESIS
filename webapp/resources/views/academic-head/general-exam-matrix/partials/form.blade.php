@php
    $matrixDayLookup = [];
    $settingSemester = match ($setting->semester ?? null) {
        '1st Semester' => 1,
        '2nd Semester' => 2,
        default => null,
    };
    $lockedToFirstSemester = $settingSemester === 1;
    $currentAcademicYear = $setting->academic_year ?? old('academic_year', $matrix->academic_year ?? '');
    $normalizeSubjectIds = function ($values): array {
        return collect(is_array($values) ? $values : [$values])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();
    };

    if (isset($matrix)) {
        $sortedDays = $matrix->slots
            ->groupBy(fn ($slot) => optional($slot->slot_date)->format('Y-m-d'))
            ->sortKeys();

        foreach (array_values($sortedDays->all()) as $dayIndex => $slots) {
            $date = $slots->first()?->slot_date?->format('Y-m-d');

            if ($date === null || $dayIndex >= $examDayCount) {
                continue;
            }

            $matrixDayLookup[$dayIndex] = [
                'date' => $date,
                'periods' => [],
            ];

            foreach ($standardPeriods as $periodIndex => $period) {
                $slot = $slots->first(function ($slot) use ($period) {
                    return substr((string) $slot->start_time, 0, 5) === $period['start_time']
                        && substr((string) $slot->end_time, 0, 5) === $period['end_time'];
                });

                $matrixDayLookup[$dayIndex]['periods'][$periodIndex] = [
                    'subject_ids' => $slot
                        ? $normalizeSubjectIds($slot->slotSubjects->pluck('subject_id')->all())
                        : [],
                ];
            }
        }
    }
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div>
        <x-input-label for="name" value="Matrix Name (optional)" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $matrix->name ?? '')" />
    </div>
    <div>
        <x-input-label for="academic_year" value="Academic Year" />
        <div class="mt-1 rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
            {{ $currentAcademicYear !== '' ? $currentAcademicYear : 'Academic year not configured' }}
        </div>
        <input id="academic_year" name="academic_year" type="hidden" value="{{ $currentAcademicYear }}" />
    </div>
    <div>
        <x-input-label for="semester" value="Semester" />
        <select id="semester" name="semester" class="mt-1 block w-full rounded-md border-gray-300" required>
            <option value="">Select semester</option>
            <option value="1" @selected((int) old('semester', $matrix->semester ?? 0) === 1)>1st Semester</option>
            <option value="2" @selected((int) old('semester', $matrix->semester ?? 0) === 2) @disabled($lockedToFirstSemester)>2nd Semester</option>
        </select>
        @if ($lockedToFirstSemester)
            <p class="mt-1 text-xs text-amber-700">2nd Semester is locked until the academic timeline is set to 2nd Semester.</p>
        @endif
    </div>
    <div>
        <x-input-label for="exam_period" value="Exam Period" />
        <select id="exam_period" name="exam_period" class="mt-1 block w-full rounded-md border-gray-300" required>
            <option value="">Select period</option>
            @foreach (['Prelim', 'Midterm', 'Prefinals', 'Finals'] as $period)
                <option value="{{ $period }}" @selected(old('exam_period', $matrix->exam_period ?? '') === $period)>{{ $period }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
        <div>
            <h3 class="font-semibold text-slate-900">General Exam Matrix Layout</h3>
            <p class="text-sm text-slate-600 mt-1">
                Set the four exam dates, then assign subjects into the fixed standard time periods. Leave a cell blank to keep that slot open for schedule assignment later.
            </p>
        </div>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800 font-medium">
            Selected subject = fixed slot
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white mt-4">
        <table class="min-w-[1320px] text-sm">
            <thead class="bg-slate-100 text-slate-700">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold w-32 min-w-32">Session</th>
                    <th class="px-4 py-3 text-left font-semibold w-56 min-w-56">Time</th>
                    @for ($dayIndex = 0; $dayIndex < $examDayCount; $dayIndex++)
                        <th class="px-4 py-3 text-left font-semibold min-w-64">
                            <div>Day {{ $dayIndex + 1 }}</div>
                            <input
                                type="date"
                                name="exam_days[{{ $dayIndex }}][date]"
                                value="{{ old('exam_days.' . $dayIndex . '.date', $matrixDayLookup[$dayIndex]['date'] ?? '') }}"
                                class="mt-2 block w-full rounded-md border-gray-300 text-sm"
                                required
                            >
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach ($standardPeriods as $periodIndex => $period)
                    <tr class="{{ $periodIndex === 3 ? 'border-t-2 border-slate-300' : '' }}">
                        <td class="px-4 py-3 align-top text-slate-600 font-medium">{{ $period['session'] }}</td>
                        <td class="px-4 py-3 align-top text-slate-700 font-medium whitespace-nowrap">{{ $period['label'] }}</td>
                        @for ($dayIndex = 0; $dayIndex < $examDayCount; $dayIndex++)
                            @php
                                $selectedSubjectIds = $normalizeSubjectIds(old(
                                    'exam_days.' . $dayIndex . '.periods.' . $periodIndex . '.subject_ids',
                                    $matrixDayLookup[$dayIndex]['periods'][$periodIndex]['subject_ids'] ?? []
                                ));
                                $selectedSubjectIds = $selectedSubjectIds === [] ? [''] : $selectedSubjectIds;
                            @endphp
                            <td class="px-4 py-3 align-top">
                                <div class="space-y-2 js-slot-subjects">
                                    @foreach ($selectedSubjectIds as $selectedSubjectId)
                                        <div class="flex items-center gap-2 js-subject-row">
                                            <select
                                                name="exam_days[{{ $dayIndex }}][periods][{{ $periodIndex }}][subject_ids][]"
                                                class="block w-full rounded-md border-gray-300 text-sm"
                                            >
                                                <option value="">Open slot</option>
                                                @foreach ($subjects as $subject)
                                                    <option
                                                        value="{{ $subject->id }}"
                                                        @selected((string) $selectedSubjectId !== '' && (int) $selectedSubjectId === $subject->id)
                                                    >
                                                        {{ $subject->code }} | {{ $subject->course_serial_number ?: 'No Serial' }} - {{ $subject->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button
                                                type="button"
                                                class="js-remove-subject-row hidden rounded-md border border-slate-300 px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    @endforeach

                                    <button
                                        type="button"
                                        class="js-add-subject-row rounded-md border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                    >
                                        + Add subject
                                    </button>
                                </div>
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="flex gap-3">
    <button type="submit" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700 font-semibold">Save Matrix</button>
    <a href="{{ route('academic-head.general-exam-matrix.index') }}" class="px-4 py-2 rounded bg-slate-600 text-white hover:bg-slate-700 font-semibold">Cancel</a>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const containers = document.querySelectorAll('.js-slot-subjects');

            const updateRemoveButtons = (container) => {
                const rows = container.querySelectorAll('.js-subject-row');
                rows.forEach((row) => {
                    const removeButton = row.querySelector('.js-remove-subject-row');
                    if (!removeButton) {
                        return;
                    }

                    removeButton.classList.toggle('hidden', rows.length === 1);
                });
            };

            const buildRow = (container) => {
                const firstRow = container.querySelector('.js-subject-row');
                if (!firstRow) {
                    return null;
                }

                const row = firstRow.cloneNode(true);
                const select = row.querySelector('select');

                if (select) {
                    select.value = '';
                }

                return row;
            };

            containers.forEach((container) => {
                updateRemoveButtons(container);

                container.addEventListener('click', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }

                    if (target.classList.contains('js-add-subject-row')) {
                        const newRow = buildRow(container);
                        if (newRow) {
                            container.insertBefore(newRow, target);
                            updateRemoveButtons(container);
                        }
                    }

                    if (target.classList.contains('js-remove-subject-row')) {
                        const row = target.closest('.js-subject-row');
                        if (row) {
                            row.remove();

                            if (container.querySelectorAll('.js-subject-row').length === 0) {
                                const fallbackRow = buildRow(container);
                                const addButton = container.querySelector('.js-add-subject-row');

                                if (fallbackRow && addButton) {
                                    container.insertBefore(fallbackRow, addButton);
                                }
                            }

                            updateRemoveButtons(container);
                        }
                    }
                });
            });
        });
    </script>
@endonce
