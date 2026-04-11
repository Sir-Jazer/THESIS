<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">System Administrator Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="w-full space-y-4">
            <div class="bg-slate-800 dark:bg-slate-800 overflow-hidden shadow-lg sm:rounded-lg">
                <div class="p-6 text-gray-100 dark:text-gray-100 text-lg">Welcome, {{ auth()->user()->full_name }}.</div>
            </div>
            <div class="bg-slate-800 dark:bg-slate-800 overflow-hidden shadow-lg sm:rounded-lg">
                <div class="p-6 text-base text-gray-200 dark:text-gray-200">
                    <p class="mb-4 font-semibold">This portal now includes your initial admin modules:</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <a href="{{ route('admin.users.index') }}" class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Manage Users</a>
                        <a href="{{ route('admin.rooms.index') }}" class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Manage Rooms</a>
                        <a href="{{ route('admin.programs.index') }}" class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Manage Programs</a>
                        <a href="{{ route('admin.subjects.index') }}" class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Manage Subjects</a>
                        <a href="{{ route('admin.settings.edit') }}" class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out sm:col-span-2">Academic Timeline</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
