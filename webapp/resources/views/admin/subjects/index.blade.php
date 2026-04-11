<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">Subjects</h2></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <a href="{{ route('admin.subjects.create') }}" class="inline-block px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Add Subject</a>
            <form method="GET" action="{{ route('admin.subjects.index') }}" class="grid grid-cols-1 gap-3 rounded-lg bg-slate-800 p-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="program_id" class="block text-sm font-medium text-gray-200">Program</label>
                    <select id="program_id" name="program_id" class="mt-1 block w-full rounded-md border-slate-600 bg-slate-900 text-sm text-gray-100">
                        <option value="">All programs</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected((int) ($filters['program_id'] ?? 0) === (int) $program->id)>
                                {{ $program->code }} - {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="year_level" class="block text-sm font-medium text-gray-200">Year</label>
                    <select id="year_level" name="year_level" class="mt-1 block w-full rounded-md border-slate-600 bg-slate-900 text-sm text-gray-100">
                        <option value="">All years</option>
                        @foreach ([1, 2, 3, 4] as $yearLevel)
                            <option value="{{ $yearLevel }}" @selected((int) ($filters['year_level'] ?? 0) === $yearLevel)>
                                Year {{ $yearLevel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="semester" class="block text-sm font-medium text-gray-200">Semester</label>
                    <select id="semester" name="semester" class="mt-1 block w-full rounded-md border-slate-600 bg-slate-900 text-sm text-gray-100">
                        <option value="">All semesters</option>
                        <option value="1" @selected((int) ($filters['semester'] ?? 0) === 1)>1st Semester</option>
                        <option value="2" @selected((int) ($filters['semester'] ?? 0) === 2)>2nd Semester</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-slate-600 px-4 py-2 text-sm font-semibold text-white transition duration-150 ease-in-out hover:bg-slate-700">Apply</button>
                    <a href="{{ route('admin.subjects.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-500 px-4 py-2 text-sm font-semibold text-gray-200 transition duration-150 ease-in-out hover:bg-slate-700">Clear</a>
                </div>
            </form>
        </div>
        <div class="bg-slate-800 dark:bg-slate-800 shadow-lg sm:rounded-lg overflow-hidden">
            <table class="min-w-full text-base"><thead class="bg-slate-700 dark:bg-slate-700 text-gray-100 dark:text-gray-100"><tr><th class="p-4 text-left font-semibold">Subject</th><th class="p-4 text-left font-semibold">Name</th><th class="p-4 text-left font-semibold">Units</th><th class="p-4 text-left font-semibold">Programs</th><th class="p-4 text-left font-semibold">Actions</th></tr></thead><tbody>
            @forelse ($subjects as $subject)
                <tr class="border-t border-slate-700 dark:border-slate-700 hover:bg-slate-750 dark:hover:bg-slate-750"><td class="p-4 text-gray-100 dark:text-gray-100"><div class="font-semibold">{{ $subject->code }}</div><div class="text-sm text-gray-300 dark:text-gray-300">Serial: {{ $subject->course_serial_number ?: 'Not set' }}</div></td><td class="p-4 text-gray-100 dark:text-gray-100">{{ $subject->name }}</td><td class="p-4 text-gray-100 dark:text-gray-100">{{ $subject->units }}</td><td class="p-4 text-gray-100 dark:text-gray-100">
                    @if ($subject->programs_count > 0)
                        <div class="font-semibold">{{ $subject->programs_count }} program(s)</div>
                        <div class="text-sm text-gray-300 dark:text-gray-300">{{ $subject->programs->pluck('code')->implode(', ') }}</div>
                    @else
                        <span class="text-sm text-gray-400 dark:text-gray-400">No program linked</span>
                    @endif
                </td><td class="p-4 flex gap-2"><a href="{{ route('admin.subjects.edit',$subject) }}" class="px-3 py-1.5 text-sm bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded transition duration-150 ease-in-out">Edit</a><form method="POST" action="{{ route('admin.subjects.destroy',$subject) }}">@csrf @method('DELETE')<button class="px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white font-semibold rounded transition duration-150 ease-in-out">Delete</button></form></td></tr>
            @empty
                <tr>
                    <td colspan="5" class="p-6 text-center text-sm text-gray-300">No subjects found for the selected filters.</td>
                </tr>
            @endforelse
            </tbody></table>
        </div>
        <div class="text-gray-100 dark:text-gray-100">{{ $subjects->links() }}</div>
    </div></div>
</x-app-layout>
