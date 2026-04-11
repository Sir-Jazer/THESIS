<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">
            Edit Section - {{ $program->code }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.programs.sections.update', [$program, $section]) }}" class="bg-slate-800 dark:bg-slate-800 p-6 shadow-lg sm:rounded-lg space-y-4">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="bg-red-900 border border-red-700 text-red-100 p-3 rounded-lg text-base">
                        <ul class="list-disc list-inside font-semibold">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <x-input-label for="year_level" value="Year Level" />
                    <select id="year_level" name="year_level" class="mt-1 block w-full text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                        @foreach ([1 => '1st year', 2 => '2nd year', 3 => '3rd year', 4 => '4th year'] as $year => $label)
                            <option value="{{ $year }}" @selected((int) old('year_level', $section->year_level) === $year)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="section_code" value="Section Code" />
                    <x-text-input id="section_code" name="section_code" class="mt-1 block w-full text-base" :value="old('section_code', $section->section_code)" required />
                </div>

                <div class="flex justify-between">
                    <a href="{{ route('admin.programs.sections.index', $program) }}" class="px-4 py-2.5 text-base bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Cancel</a>
                    <button class="px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Update Section</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
