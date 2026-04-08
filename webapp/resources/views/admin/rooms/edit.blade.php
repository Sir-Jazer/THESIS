<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">Edit Room</h2></x-slot>
    <div class="py-8"><div class="max-w-xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('admin.rooms.update', $room) }}" class="bg-slate-800 dark:bg-slate-800 p-6 shadow-lg sm:rounded-lg space-y-4">@csrf @method('PUT')
            <div><x-input-label for="name" value="Room Name" /><x-text-input id="name" name="name" class="mt-1 block w-full text-base" :value="old('name',$room->name)" required /></div>
            <div><x-input-label for="capacity" value="Capacity" /><x-text-input id="capacity" type="number" min="1" name="capacity" class="mt-1 block w-full text-base" :value="old('capacity',$room->capacity)" required /></div>
            <div><x-input-label for="is_available" value="Availability" /><select id="is_available" name="is_available" class="mt-1 block w-full text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600"><option value="1" @selected(old('is_available',$room->is_available ? 1:0)==1)>Available</option><option value="0" @selected(old('is_available',$room->is_available ? 1:0)==0)>Unavailable</option></select></div>
            <div class="flex justify-end"><button class="px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Update</button></div>
        </form>
    </div></div>
</x-app-layout>
