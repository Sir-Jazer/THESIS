<form method="POST" action="{{ $action }}" class="bg-slate-800 dark:bg-slate-800 p-6 shadow-lg sm:rounded-lg space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="bg-red-900 dark:bg-red-900 border border-red-700 dark:border-red-700 text-red-100 dark:text-red-100 p-3 rounded-lg text-base">
            <ul class="list-disc list-inside font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div><x-input-label for="code" value="Subject Code" /><x-text-input id="code" name="code" class="mt-1 block w-full text-base" :value="old('code', $subject?->code)" required /></div>
        <div><x-input-label for="course_serial_number" value="Course Serial Number" /><x-text-input id="course_serial_number" name="course_serial_number" class="mt-1 block w-full text-base" :value="old('course_serial_number', $subject?->course_serial_number)" placeholder="IT2032" required /></div>
        <div class="sm:col-span-2"><x-input-label for="name" value="Subject Name" /><x-text-input id="name" name="name" class="mt-1 block w-full text-base" :value="old('name', $subject?->name)" required /></div>
    </div>
    <p class="text-sm text-gray-300 dark:text-gray-300 font-medium">
        Subject Code and Course Serial Number identify one shared subject record. Use Program Associations below to attach this subject to one or more programs.
    </p>

    <div>
        <x-input-label for="units" value="Units" />
        <x-text-input id="units" name="units" type="number" min="1" max="10" class="mt-1 block w-40 text-base" :value="old('units', $subject?->units ?? 3)" required />
    </div>

    <div class="border border-slate-600 dark:border-slate-600 rounded-lg p-4 space-y-3 bg-slate-700 dark:bg-slate-700">
        <h3 class="font-semibold text-base text-gray-100 dark:text-gray-100">Program Associations</h3>
        @php
            $existingLinks = old('program_links');
            if ($existingLinks === null && $subject) {
                $existingLinks = $subject->programs->map(fn($p) => ['program_id' => $p->id, 'year_level' => $p->pivot->year_level, 'semester' => $p->pivot->semester])->values()->all();
            }
            $existingLinks = $existingLinks ?? [['program_id' => '', 'year_level' => 1, 'semester' => 1]];
        @endphp
        <div id="program-links-container" class="space-y-3">
            @foreach ($existingLinks as $idx => $link)
                <div class="program-link-row grid grid-cols-1 sm:grid-cols-4 gap-3" data-row-index="{{ $idx }}">
                    <select name="program_links[{{ $idx }}][program_id]" data-field="program_id" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                        <option value="">Program</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected((string)($link['program_id'] ?? '') === (string)$program->id)>{{ $program->code }}</option>
                        @endforeach
                    </select>
                    <select name="program_links[{{ $idx }}][year_level]" data-field="year_level" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                        @php $yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year']; @endphp
                        @foreach ($yearLabels as $value => $label)
                            <option value="{{ $value }}" @selected((int)($link['year_level'] ?? 1) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="program_links[{{ $idx }}][semester]" data-field="semester" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                        <option value="1" @selected((int)($link['semester'] ?? 1) === 1)>1st Semester</option>
                        <option value="2" @selected((int)($link['semester'] ?? 1) === 2)>2nd Semester</option>
                    </select>
                    <button type="button" class="js-remove-program-row px-3 py-2 text-sm font-semibold rounded-md border border-red-500 text-red-200 hover:bg-red-600/20 disabled:opacity-50 disabled:cursor-not-allowed">Remove</button>
                </div>
            @endforeach
        </div>

        <template id="program-link-row-template">
            <div class="program-link-row grid grid-cols-1 sm:grid-cols-4 gap-3" data-row-index="0">
                <select data-field="program_id" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                    <option value="">Program</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->code }}</option>
                    @endforeach
                </select>
                <select data-field="year_level" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                    @php $yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year']; @endphp
                    @foreach ($yearLabels as $value => $label)
                        <option value="{{ $value }}" @selected($value === 1)>{{ $label }}</option>
                    @endforeach
                </select>
                <select data-field="semester" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                    <option value="1" selected>1st Semester</option>
                    <option value="2">2nd Semester</option>
                </select>
                <button type="button" class="js-remove-program-row px-3 py-2 text-sm font-semibold rounded-md border border-red-500 text-red-200 hover:bg-red-600/20 disabled:opacity-50 disabled:cursor-not-allowed">Remove</button>
            </div>
        </template>

        <div>
            <button type="button" id="add-program-link-row" class="px-3 py-2 text-sm font-semibold rounded-md border border-blue-400 text-blue-100 hover:bg-blue-500/20">Add Program Association</button>
        </div>

        <p class="text-sm text-gray-400 dark:text-gray-400 font-medium">
            Add one row per program. You can attach the same subject record to multiple programs, each with its own year level and semester.
        </p>
    </div>

    @php
        $selectedPrereqs = collect(old('prerequisite_ids', $subject?->prerequisites->pluck('id')->all() ?? []));
        $selectedCoreqs  = collect(old('corequisite_ids',  $subject?->corequisites->pluck('id')->all()  ?? []));
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label value="Prerequisites" />
            <p class="text-xs text-gray-400 dark:text-gray-400 mt-0.5 mb-1">Click a row to select or deselect. Multiple selections allowed.</p>
            <div class="h-40 overflow-y-auto border border-slate-600 dark:border-slate-600 rounded-md bg-slate-700 dark:bg-slate-700 divide-y divide-slate-600 dark:divide-slate-600">
                @forelse ($subjects as $item)
                    @php $checked = $selectedPrereqs->contains($item->id); @endphp
                    <label class="flex items-center gap-3 px-3 py-1.5 cursor-pointer hover:bg-slate-600 dark:hover:bg-slate-600 {{ $checked ? 'bg-blue-700 dark:bg-blue-700 hover:bg-blue-600 dark:hover:bg-blue-600' : '' }}">
                        <input type="checkbox" name="prerequisite_ids[]" value="{{ $item->id }}" {{ $checked ? 'checked' : '' }} class="accent-blue-400 w-4 h-4 flex-shrink-0">
                        <span class="text-sm text-gray-100 dark:text-gray-100 leading-tight">{{ $item->code }} &ndash; {{ $item->name }}</span>
                    </label>
                @empty
                    <p class="px-3 py-2 text-sm text-gray-400 dark:text-gray-400">No subjects available.</p>
                @endforelse
            </div>
        </div>
        <div>
            <x-input-label value="Corequisites" />
            <p class="text-xs text-gray-400 dark:text-gray-400 mt-0.5 mb-1">Click a row to select or deselect. Multiple selections allowed.</p>
            <div class="h-40 overflow-y-auto border border-slate-600 dark:border-slate-600 rounded-md bg-slate-700 dark:bg-slate-700 divide-y divide-slate-600 dark:divide-slate-600">
                @forelse ($subjects as $item)
                    @php $checked = $selectedCoreqs->contains($item->id); @endphp
                    <label class="flex items-center gap-3 px-3 py-1.5 cursor-pointer hover:bg-slate-600 dark:hover:bg-slate-600 {{ $checked ? 'bg-blue-700 dark:bg-blue-700 hover:bg-blue-600 dark:hover:bg-blue-600' : '' }}">
                        <input type="checkbox" name="corequisite_ids[]" value="{{ $item->id }}" {{ $checked ? 'checked' : '' }} class="accent-blue-400 w-4 h-4 flex-shrink-0">
                        <span class="text-sm text-gray-100 dark:text-gray-100 leading-tight">{{ $item->code }} &ndash; {{ $item->name }}</span>
                    </label>
                @empty
                    <p class="px-3 py-2 text-sm text-gray-400 dark:text-gray-400">No subjects available.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="flex justify-end"><button class="px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Save Subject</button></div>
</form>

@once
    @push('scripts')
        <script>
            (function () {
                const container = document.getElementById('program-links-container');
                const addButton = document.getElementById('add-program-link-row');
                const template = document.getElementById('program-link-row-template');

                if (!container || !addButton || !template) {
                    return;
                }

                const clearRow = (row) => {
                    row.querySelectorAll('select[data-field]').forEach((select) => {
                        if (select.getAttribute('data-field') === 'year_level' || select.getAttribute('data-field') === 'semester') {
                            select.value = '1';
                        } else {
                            select.value = '';
                        }
                    });
                };

                const setRowIndex = (row, index) => {
                    row.dataset.rowIndex = String(index);
                    row.querySelectorAll('select[data-field]').forEach((select) => {
                        const field = select.getAttribute('data-field');
                        select.name = `program_links[${index}][${field}]`;
                    });
                };

                const reindexRows = () => {
                    container.querySelectorAll('.program-link-row').forEach((row, index) => {
                        setRowIndex(row, index);
                    });
                };

                const refreshRemoveButtons = () => {
                    const rows = container.querySelectorAll('.program-link-row');
                    const shouldDisable = rows.length === 1;
                    rows.forEach((row) => {
                        const removeButton = row.querySelector('.js-remove-program-row');
                        if (removeButton) {
                            removeButton.disabled = shouldDisable;
                        }
                    });
                };

                const addRow = () => {
                    const fragment = template.content.cloneNode(true);
                    const newRow = fragment.querySelector('.program-link-row');
                    if (!newRow) {
                        return;
                    }

                    container.appendChild(newRow);
                    reindexRows();
                    refreshRemoveButtons();
                };

                addButton.addEventListener('click', () => {
                    addRow();
                });

                container.addEventListener('click', (event) => {
                    const button = event.target.closest('.js-remove-program-row');
                    if (!button) {
                        return;
                    }

                    const rows = container.querySelectorAll('.program-link-row');
                    const row = button.closest('.program-link-row');

                    if (!row) {
                        return;
                    }

                    if (rows.length === 1) {
                        clearRow(row);
                        return;
                    }

                    row.remove();
                    reindexRows();
                    refreshRemoveButtons();
                });

                reindexRows();
                refreshRemoveButtons();
            })();
        </script>
    @endpush
@endonce
