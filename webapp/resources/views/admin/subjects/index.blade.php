<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">Subjects</h2></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('admin.subjects.create') }}" class="inline-block px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Add Subject</a>
        <div class="bg-slate-800 dark:bg-slate-800 shadow-lg sm:rounded-lg overflow-hidden">
            <table class="min-w-full text-base"><thead class="bg-slate-700 dark:bg-slate-700 text-gray-100 dark:text-gray-100"><tr><th class="p-4 text-left font-semibold">Code</th><th class="p-4 text-left font-semibold">Name</th><th class="p-4 text-left font-semibold">Units</th><th class="p-4 text-left font-semibold">Programs</th><th class="p-4 text-left font-semibold">Actions</th></tr></thead><tbody>
            @foreach ($subjects as $subject)
                <tr class="border-t border-slate-700 dark:border-slate-700 hover:bg-slate-750 dark:hover:bg-slate-750"><td class="p-4 text-gray-100 dark:text-gray-100">{{ $subject->code }}</td><td class="p-4 text-gray-100 dark:text-gray-100">{{ $subject->name }}</td><td class="p-4 text-gray-100 dark:text-gray-100">{{ $subject->units }}</td><td class="p-4 text-gray-100 dark:text-gray-100">{{ $subject->programs_count }}</td><td class="p-4 flex gap-2"><a href="{{ route('admin.subjects.edit',$subject) }}" class="px-3 py-1.5 text-sm bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded transition duration-150 ease-in-out">Edit</a><form method="POST" action="{{ route('admin.subjects.destroy',$subject) }}">@csrf @method('DELETE')<button class="px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white font-semibold rounded transition duration-150 ease-in-out">Delete</button></form></td></tr>
            @endforeach
            </tbody></table>
        </div>
        <div class="text-gray-100 dark:text-gray-100">{{ $subjects->links() }}</div>
    </div></div>
</x-app-layout>
