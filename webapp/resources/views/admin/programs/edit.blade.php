<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">Edit Program</h2></x-slot>
    <div class="py-8"><div class="max-w-xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-4 flex justify-end">
            <a href="{{ route('admin.programs.sections.index', $program) }}" class="px-4 py-2.5 text-base bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Manage Sections</a>
        </div>
        <form method="POST" action="{{ route('admin.programs.update',$program) }}" class="bg-slate-800 dark:bg-slate-800 p-6 shadow-lg sm:rounded-lg space-y-4">@csrf @method('PUT')
            <div><x-input-label for="code" value="Program Code" /><x-text-input id="code" name="code" class="mt-1 block w-full text-base" :value="old('code',$program->code)" required /></div>
            <div><x-input-label for="name" value="Program Name" /><x-text-input id="name" name="name" class="mt-1 block w-full text-base" :value="old('name',$program->name)" required /></div>
            <div class="flex justify-end"><button class="px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Update</button></div>
        </form>
    </div></div>
</x-app-layout>
