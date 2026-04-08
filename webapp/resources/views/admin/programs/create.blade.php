<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">Create Program</h2></x-slot>
    <div class="py-8"><div class="max-w-xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('admin.programs.store') }}" class="bg-slate-800 dark:bg-slate-800 p-6 shadow-lg sm:rounded-lg space-y-4">@csrf
            <div><x-input-label for="code" value="Program Code" /><x-text-input id="code" name="code" class="mt-1 block w-full text-base" :value="old('code')" required /></div>
            <div><x-input-label for="name" value="Program Name" /><x-text-input id="name" name="name" class="mt-1 block w-full text-base" :value="old('name')" required /></div>
            <div class="flex justify-end"><button class="px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Save</button></div>
        </form>
    </div></div>
</x-app-layout>
