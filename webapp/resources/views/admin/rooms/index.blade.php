<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">Rooms</h2></x-slot>
    <div class="py-8"><div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <a href="{{ route('admin.rooms.create') }}" class="inline-block px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Add Room</a>
        <div class="bg-slate-800 dark:bg-slate-800 shadow-lg sm:rounded-lg overflow-hidden">
            <table class="min-w-full text-base">
                <thead class="bg-slate-700 dark:bg-slate-700 text-gray-100 dark:text-gray-100"><tr><th class="p-4 text-left font-semibold">Name</th><th class="p-4 text-left font-semibold">Capacity</th><th class="p-4 text-left font-semibold">Available</th><th class="p-4 text-left font-semibold">Actions</th></tr></thead>
                <tbody>
                @foreach ($rooms as $room)
                    <tr class="border-t border-slate-700 dark:border-slate-700 hover:bg-slate-750 dark:hover:bg-slate-750">
                        <td class="p-4 text-gray-100 dark:text-gray-100">{{ $room->name }}</td><td class="p-4 text-gray-100 dark:text-gray-100">{{ $room->capacity }}</td><td class="p-4 text-gray-100 dark:text-gray-100">{{ $room->is_available ? 'Yes' : 'No' }}</td>
                        <td class="p-4 flex gap-2">
                            <a href="{{ route('admin.rooms.edit', $room) }}" class="px-3 py-1.5 text-sm bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded transition duration-150 ease-in-out">Edit</a>
                            <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}">@csrf @method('DELETE')<button class="px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white font-semibold rounded transition duration-150 ease-in-out">Delete</button></form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="text-gray-100 dark:text-gray-100">{{ $rooms->links() }}</div>
    </div></div>
</x-app-layout>
