<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">
            Sections - {{ $program->code }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-900 border border-green-700 text-green-100 px-4 py-3 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-900 border border-red-700 text-red-100 p-3 rounded-lg text-base">
                    <ul class="list-disc list-inside font-semibold">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.programs.sections.create', $program) }}" class="inline-block px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Add Section</a>
                <a href="{{ route('admin.programs.edit', $program) }}" class="inline-block px-4 py-2.5 text-base bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Back to Program</a>
            </div>

            <div class="bg-slate-800 dark:bg-slate-800 shadow-lg sm:rounded-lg overflow-hidden">
                <table class="min-w-full text-base">
                    <thead class="bg-slate-700 dark:bg-slate-700 text-gray-100 dark:text-gray-100">
                        <tr>
                            <th class="p-4 text-left font-semibold">Year Level</th>
                            <th class="p-4 text-left font-semibold">Section Code</th>
                            <th class="p-4 text-left font-semibold">Students</th>
                            <th class="p-4 text-left font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sections as $section)
                            <tr class="border-t border-slate-700 dark:border-slate-700 hover:bg-slate-750 dark:hover:bg-slate-750">
                                <td class="p-4 text-gray-100 dark:text-gray-100">Year {{ $section->year_level }}</td>
                                <td class="p-4 text-gray-100 dark:text-gray-100">{{ $section->section_code }}</td>
                                <td class="p-4 text-gray-100 dark:text-gray-100">{{ $section->students_count }}</td>
                                <td class="p-4 flex gap-2">
                                    <a href="{{ route('admin.programs.sections.edit', [$program, $section]) }}" class="px-3 py-1.5 text-sm bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded transition duration-150 ease-in-out">Edit</a>
                                    <form method="POST" action="{{ route('admin.programs.sections.destroy', [$program, $section]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white font-semibold rounded transition duration-150 ease-in-out">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-gray-300">No sections created for this program yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-gray-100 dark:text-gray-100">{{ $sections->links() }}</div>
        </div>
    </div>
</x-app-layout>
