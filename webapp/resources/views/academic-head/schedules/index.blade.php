<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Schedules</h2>
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
                <h3 class="font-semibold text-gray-800 mb-3">Load Exam Schedule</h3>
                <form method="POST" action="{{ route('academic-head.schedules.load') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                    @csrf
                    <div>
                        <x-input-label value="Academic Year" />
                        <div class="mt-1 rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
                            {{ $filters['academic_year'] }}
                        </div>
                    </div>
                    <div>
                        <x-input-label for="semester" value="Semester" />
                        <select id="semester" name="semester" class="mt-1 block w-full rounded-md border-gray-300" required>
                            <option value="1" @selected((int) $filters['semester'] === 1)>1st Semester</option>
                            <option value="2" @selected((int) $filters['semester'] === 2) @disabled($settingSemester === 1)>2nd Semester</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="program_id" value="Program" />
                        <select id="program_id" name="program_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                            <option value="">Select program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected((int) $filters['program_id'] === $program->id)>
                                    {{ $program->code }} - {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="year_level" value="Year Level" />
                        <select id="year_level" name="year_level" class="mt-1 block w-full rounded-md border-gray-300" required>
                            <option value="">Select year level</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="section_id" value="Section" />
                        <select id="section_id" name="section_id" class="mt-1 block w-full rounded-md border-gray-300" required>
                            <option value="">Select section</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="exam_period" value="Exam Period" />
                        <select id="exam_period" name="exam_period" class="mt-1 block w-full rounded-md border-gray-300" required>
                            <option value="">Select exam period</option>
                            @foreach (['Prelim', 'Midterm', 'Prefinals', 'Finals'] as $period)
                                <option value="{{ $period }}" @selected($filters['exam_period'] === $period)>{{ $period }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2 items-end">
                        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">Load Schedule</button>
                        <button type="submit"
                            formaction="{{ route('academic-head.schedules.fetch-matrix-all') }}"
                            onclick="return confirm('Fetch latest uploaded matrix updates for ALL draft schedules in the selected semester and exam period? Fixed-slot subject assignments may be overwritten.')"
                            class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 font-semibold whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">Fetch Matrix</button>
                    </div>
                </form>

                @if ($settingSemester === 1)
                    <p class="mt-2 text-xs text-amber-700">2nd Semester is currently locked by Academic Timeline settings.</p>
                @endif

                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-600">
                    <span class="font-semibold text-slate-700">Schedule Status Legend:</span>
                    <span class="inline-flex items-center gap-1">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        <span>Green = Uploaded</span>
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                        <span>Yellow = Draft Saved</span>
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                        <span>Red = Not Yet Plotted</span>
                    </span>
                </div>
            </div>

            @php
                $filterComplete = (int) ($filters['program_id'] ?? 0) > 0
                    && (int) ($filters['year_level'] ?? 0) > 0
                    && (int) ($filters['section_id'] ?? 0) > 0
                    && $filters['exam_period'] !== '';
            @endphp

            @if (! $filterComplete)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-10 text-center text-slate-500">
                        Select Program, Year Level, Section, Semester, and Exam Period to load a schedule table.
                    </div>
                </div>
            @elseif (! $selectedSchedule)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-6">
                        <h4 class="font-semibold text-slate-800">No schedule loaded yet for this section and exam period.</h4>
                        @if ($matchingMatrix)
                            <p class="mt-1 text-sm text-slate-600">Click Load Schedule to auto-create a draft from the uploaded General Exam Matrix reference.</p>
                            <p class="mt-2 text-xs text-slate-500">Source matrix: {{ $matchingMatrix->name ?: 'General Exam Matrix' }}</p>
                        @else
                            <p class="mt-3 text-sm text-red-600">No uploaded General Exam Matrix found for this academic context. Upload it first in General Exam Matrix page.</p>
                        @endif
                    </div>
                </div>
            @else
                @php($schedule = $selectedSchedule)
                @php($readiness = $publishReadiness)
                @php($hasPublishBlockers = $schedule->status === 'draft' && ($readiness['has_blockers'] ?? false))
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="border-b px-4 py-3 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-800">
                                {{ $schedule->section?->program?->code }} - Y{{ $schedule->section?->year_level }} {{ $schedule->section?->section_code }}
                                ({{ $schedule->academic_year }} / {{ $schedule->semester === 1 ? '1st' : '2nd' }} Semester / {{ $schedule->exam_period }})
                            </h4>
                            <p class="text-sm text-gray-500">Status: <span class="font-semibold">{{ ucfirst($schedule->status) }}</span></p>
                            <p class="mt-1 text-xs text-slate-500">Upload publishes the saved draft schedule to student and proctor portals.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('academic-head.schedules.edit', array_merge(['schedule' => $schedule->id], request()->query())) }}"
                                class="px-3 py-2 text-xs rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">Edit Schedule</a>

                            <form method="POST" action="{{ route('academic-head.schedules.upload', $schedule) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="px-3 py-2 text-xs rounded bg-emerald-600 text-white hover:bg-emerald-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-emerald-600"
                                    @disabled($schedule->status === 'published' || $hasPublishBlockers)
                                    title="{{ $hasPublishBlockers ? 'Resolve upload blockers listed below before publishing.' : '' }}"
                                >Upload Schedule</button>
                            </form>

                            <form method="POST" action="{{ route('academic-head.schedules.reset', $schedule) }}">
                                @csrf
                                <button type="submit" class="px-3 py-2 text-xs rounded bg-amber-600 text-white hover:bg-amber-700 font-semibold">Reset Schedule</button>
                            </form>

                            <form method="POST" action="{{ route('academic-head.schedules.destroy', $schedule) }}" onsubmit="return confirm('Delete this schedule?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-2 text-xs rounded bg-red-600 text-white hover:bg-red-700 font-semibold">Delete</button>
                            </form>
                        </div>
                    </div>

                    @if ($schedule->status === 'draft')
                        <div class="border-b px-4 py-3">
                            @if ($hasPublishBlockers)
                                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    <p class="font-semibold">Upload blocked: {{ $readiness['total_blockers'] }} issue(s) found.</p>
                                    <p class="mt-1 text-xs">Non-fixed slots can stay unassigned, but fixed slots need subjects and assigned subjects need room and proctor.</p>

                                    @if (! empty($readiness['missing_fixed_subjects']))
                                        <p class="mt-2 font-semibold">Fixed slots missing subjects ({{ count($readiness['missing_fixed_subjects']) }}):</p>
                                        <ul class="mt-1 list-disc list-inside space-y-1 text-xs">
                                            @foreach (array_slice($readiness['missing_fixed_subjects'], 0, 3) as $slotLabel)
                                                <li>{{ $slotLabel }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if (! empty($readiness['assigned_without_room']))
                                        <p class="mt-2 font-semibold">Assigned subjects without room ({{ count($readiness['assigned_without_room']) }}):</p>
                                        <ul class="mt-1 list-disc list-inside space-y-1 text-xs">
                                            @foreach (array_slice($readiness['assigned_without_room'], 0, 3) as $slotLabel)
                                                <li>{{ $slotLabel }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if (! empty($readiness['assigned_without_proctor']))
                                        <p class="mt-2 font-semibold">Assigned subjects without proctor ({{ count($readiness['assigned_without_proctor']) }}):</p>
                                        <ul class="mt-1 list-disc list-inside space-y-1 text-xs">
                                            @foreach (array_slice($readiness['assigned_without_proctor'], 0, 3) as $slotLabel)
                                                <li>{{ $slotLabel }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @else
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                    <p class="font-semibold">Ready for upload.</p>
                                    <p class="mt-1 text-xs">All fixed slots have subjects, and all assigned subjects have room and proctor.</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    @include('academic-head.schedules.partials.schedule-table', ['schedule' => $schedule])
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const programs = @json($programsJson);
            const sections = @json($sectionsJson);
            const sectionStatusesByPeriod = @json($sectionStatusesByPeriod);
            const programSelect = document.getElementById('program_id');
            const yearLevelSelect = document.getElementById('year_level');
            const sectionSelect = document.getElementById('section_id');
            const examPeriodSelect = document.getElementById('exam_period');

            const selectedProgramId = '{{ $filters['program_id'] }}';
            const selectedYearLevel = '{{ $filters['year_level'] }}';
            const selectedSectionId = '{{ $filters['section_id'] }}';

            const statusMeta = {
                uploaded: { label: 'Uploaded', color: '#15803d', marker: '🟢' },
                draft: { label: 'Draft Saved', color: '#b45309', marker: '🟡' },
                no_plot: { label: 'Not Yet Plotted', color: '#b91c1c', marker: '🔴' },
            };

            const uniqueSorted = (arr) => Array.from(new Set(arr)).sort((a, b) => a - b);

            const getSectionStatus = (sectionId, examPeriod) => {
                if (!examPeriod || !sectionStatusesByPeriod[examPeriod]) {
                    return 'no_plot';
                }

                return sectionStatusesByPeriod[examPeriod][String(sectionId)] || 'no_plot';
            };

            const rollupStatus = (statuses) => {
                if (statuses.length === 0 || statuses.includes('no_plot')) {
                    return 'no_plot';
                }

                if (statuses.includes('draft')) {
                    return 'draft';
                }

                return 'uploaded';
            };

            const buildStatusLabel = (baseLabel, status) => {
                const meta = statusMeta[status] || statusMeta.no_plot;

                return meta.marker + ' ' + baseLabel + ' (' + meta.label + ')';
            };

            const setOptions = (element, placeholder, options, selectedValue) => {
                element.innerHTML = '';

                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = placeholder;
                element.appendChild(placeholderOption);

                options.forEach((option) => {
                    const opt = document.createElement('option');
                    opt.value = String(option.value);
                    opt.textContent = option.label;

                    if (option.status && statusMeta[option.status]) {
                        opt.style.color = statusMeta[option.status].color;
                    }

                    if (String(option.value) === String(selectedValue)) {
                        opt.selected = true;
                    }

                    element.appendChild(opt);
                });
            };

            const refreshPrograms = (selected) => {
                const period = examPeriodSelect.value;
                const options = programs.map((program) => {
                    const statuses = sections
                        .filter((section) => section.program_id === program.id)
                        .map((section) => getSectionStatus(section.id, period));

                    const status = rollupStatus(statuses);

                    return {
                        value: program.id,
                        label: buildStatusLabel(program.code + ' - ' + program.name, status),
                        status,
                    };
                });

                setOptions(programSelect, 'Select program', options, selected);
            };

            const refreshYearLevels = (selected) => {
                const programId = Number(programSelect.value || 0);
                const period = examPeriodSelect.value;
                const levels = uniqueSorted(
                    sections
                        .filter((section) => section.program_id === programId)
                        .map((section) => section.year_level)
                );

                setOptions(
                    yearLevelSelect,
                    'Select year level',
                    levels.map((level) => {
                        const levelStatuses = sections
                            .filter((section) => section.program_id === programId && section.year_level === level)
                            .map((section) => getSectionStatus(section.id, period));
                        const status = rollupStatus(levelStatuses);

                        return {
                            value: level,
                            label: buildStatusLabel('Year ' + level, status),
                            status,
                        };
                    }),
                    selected
                );
            };

            const refreshSections = (selected) => {
                const programId = Number(programSelect.value || 0);
                const yearLevel = Number(yearLevelSelect.value || 0);
                const period = examPeriodSelect.value;
                const filteredSections = sections
                    .filter((section) => section.program_id === programId && section.year_level === yearLevel)
                    .sort((a, b) => a.section_code.localeCompare(b.section_code));

                setOptions(
                    sectionSelect,
                    'Select section',
                    filteredSections.map((section) => {
                        const status = getSectionStatus(section.id, period);

                        return {
                            value: section.id,
                            label: buildStatusLabel(section.section_code, status),
                            status,
                        };
                    }),
                    selected
                );
            };

            programSelect.addEventListener('change', function () {
                refreshYearLevels('');
                refreshSections('');
            });

            yearLevelSelect.addEventListener('change', function () {
                refreshSections('');
            });

            examPeriodSelect.addEventListener('change', function () {
                refreshPrograms(programSelect.value);
                refreshYearLevels(yearLevelSelect.value);
                refreshSections(sectionSelect.value);
            });

            refreshPrograms(selectedProgramId);
            refreshYearLevels(selectedYearLevel);
            refreshSections(selectedSectionId);
        });
    </script>
</x-app-layout>
