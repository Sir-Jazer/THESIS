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
        Enter subjects manually per program code. Similar subject names are allowed across programs, but each program-specific offering should have its own subject code and subject record. Course Serial Number is the fixed identifier used during examinations.
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
        @foreach ($existingLinks as $idx => $link)
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <select name="program_links[{{ $idx }}][program_id]" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                    <option value="">Program</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected((string)($link['program_id'] ?? '') === (string)$program->id)>{{ $program->code }}</option>
                    @endforeach
                </select>
                <select name="program_links[{{ $idx }}][year_level]" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                    @php $yearLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year']; @endphp
                    @foreach ($yearLabels as $value => $label)
                        <option value="{{ $value }}" @selected((int)($link['year_level'] ?? 1) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="program_links[{{ $idx }}][semester]" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                    <option value="1" @selected((int)($link['semester'] ?? 1) === 1)>1st Semester</option>
                    <option value="2" @selected((int)($link['semester'] ?? 1) === 2)>2nd Semester</option>
                </select>
            </div>
        @endforeach
        <p class="text-sm text-gray-400 dark:text-gray-400 font-medium">
            Program Associations are for this specific subject record only. If another program offers a similar subject with a different course code, create a separate subject entry for that program.
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
