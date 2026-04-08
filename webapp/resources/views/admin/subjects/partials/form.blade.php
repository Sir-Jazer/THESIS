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

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div><x-input-label for="code" value="Subject Code" /><x-text-input id="code" name="code" class="mt-1 block w-full text-base" :value="old('code', $subject?->code)" required /></div>
        <div class="sm:col-span-2"><x-input-label for="name" value="Subject Name" /><x-text-input id="name" name="name" class="mt-1 block w-full text-base" :value="old('name', $subject?->name)" required /></div>
    </div>

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
                    @for ($year = 1; $year <= 6; $year++)
                        <option value="{{ $year }}" @selected((int)($link['year_level'] ?? 1) === $year)>Year {{ $year }}</option>
                    @endfor
                </select>
                <select name="program_links[{{ $idx }}][semester]" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                    <option value="1" @selected((int)($link['semester'] ?? 1) === 1)>1st Semester</option>
                    <option value="2" @selected((int)($link['semester'] ?? 1) === 2)>2nd Semester</option>
                </select>
            </div>
        @endforeach
        <p class="text-sm text-gray-400 dark:text-gray-400 font-medium">For now, add one row per program/year/semester combination. We can enhance this with dynamic add/remove next.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="prerequisite_ids" value="Prerequisites" />
            <select id="prerequisite_ids" name="prerequisite_ids[]" multiple class="mt-1 block w-full text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600 h-40">
                @foreach ($subjects as $item)
                    <option value="{{ $item->id }}" @selected(collect(old('prerequisite_ids', $subject?->prerequisites->pluck('id')->all() ?? []))->contains($item->id))>
                        {{ $item->code }} - {{ $item->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="corequisite_ids" value="Corequisites" />
            <select id="corequisite_ids" name="corequisite_ids[]" multiple class="mt-1 block w-full text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600 h-40">
                @foreach ($subjects as $item)
                    <option value="{{ $item->id }}" @selected(collect(old('corequisite_ids', $subject?->corequisites->pluck('id')->all() ?? []))->contains($item->id))>
                        {{ $item->code }} - {{ $item->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex justify-end"><button class="px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Save Subject</button></div>
</form>
