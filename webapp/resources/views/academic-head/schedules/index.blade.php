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
                    <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">Load Schedule</button>
                </form>

                @if ($selectedSchedule && $selectedSchedule->status === 'draft')
                    <form method="POST" action="{{ route('academic-head.schedules.fetch-matrix', $selectedSchedule) }}" class="mt-3" onsubmit="return confirm('Fetch latest uploaded matrix updates into this draft schedule? Slots newly assigned by matrix may overwrite current subject assignments for matching time slots.');">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">Fetch Matrix</button>
                    </form>
                @endif

                @if ($settingSemester === 1)
                    <p class="mt-2 text-xs text-amber-700">2nd Semester is currently locked by Academic Timeline settings.</p>
                @endif
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
                                <button type="submit" class="px-3 py-2 text-xs rounded bg-emerald-600 text-white hover:bg-emerald-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-emerald-600" @disabled($schedule->status === 'published')>Upload Schedule</button>
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

                    @include('academic-head.schedules.partials.schedule-table', ['schedule' => $schedule])
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sections = @json($sectionsJson);
            const programSelect = document.getElementById('program_id');
            const yearLevelSelect = document.getElementById('year_level');
            const sectionSelect = document.getElementById('section_id');

            const selectedYearLevel = '{{ $filters['year_level'] }}';
            const selectedSectionId = '{{ $filters['section_id'] }}';

            const uniqueSorted = (arr) => Array.from(new Set(arr)).sort((a, b) => a - b);

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
                    if (String(option.value) === String(selectedValue)) {
                        opt.selected = true;
                    }
                    element.appendChild(opt);
                });
            };

            const refreshYearLevels = (selected) => {
                const programId = Number(programSelect.value || 0);
                const levels = uniqueSorted(
                    sections
                        .filter((section) => section.program_id === programId)
                        .map((section) => section.year_level)
                );

                setOptions(
                    yearLevelSelect,
                    'Select year level',
                    levels.map((level) => ({ value: level, label: 'Year ' + level })),
                    selected
                );
            };

            const refreshSections = (selected) => {
                const programId = Number(programSelect.value || 0);
                const yearLevel = Number(yearLevelSelect.value || 0);
                const filteredSections = sections
                    .filter((section) => section.program_id === programId && section.year_level === yearLevel)
                    .sort((a, b) => a.section_code.localeCompare(b.section_code));

                setOptions(
                    sectionSelect,
                    'Select section',
                    filteredSections.map((section) => ({ value: section.id, label: section.section_code })),
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

            refreshYearLevels(selectedYearLevel);
            refreshSections(selectedSectionId);
        });
    </script>
</x-app-layout>
