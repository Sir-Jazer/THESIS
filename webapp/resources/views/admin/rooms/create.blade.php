<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">Create Room</h2></x-slot>
    <div class="py-8"><div class="max-w-xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('admin.rooms.store') }}" class="bg-slate-800 dark:bg-slate-800 p-6 shadow-lg sm:rounded-lg space-y-4">@csrf
            <div><x-input-label for="name" value="Room Name" /><x-text-input id="name" name="name" class="mt-1 block w-full text-base" :value="old('name')" required /></div>
            <div><x-input-label for="capacity" value="Capacity" /><x-text-input id="capacity" type="number" min="1" name="capacity" class="mt-1 block w-full text-base" :value="old('capacity',50)" required /></div>
            <div><x-input-label for="is_available" value="Availability" /><select id="is_available" name="is_available" class="mt-1 block w-full text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600"><option value="1">Available</option><option value="0">Unavailable</option></select></div>
            <div class="flex justify-end"><button class="px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Save</button></div>
        </form>
    </div></div>
</x-app-layout>
