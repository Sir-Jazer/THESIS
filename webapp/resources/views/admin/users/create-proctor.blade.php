<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">Register Proctor</h2></x-slot>
    <div class="py-8"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('admin.users.proctor.store') }}" class="bg-slate-800 dark:bg-slate-800 p-6 shadow-lg sm:rounded-lg space-y-4">
            @csrf
            @include('admin.users.partials.base-user-fields')
            <div>
                <x-input-label for="employee_id" value="Employee ID" />
                <x-text-input id="employee_id" name="employee_id" class="mt-1 block w-full text-base" :value="old('employee_id')" required />
            </div>
            <div>
                <x-input-label for="department" value="Department" />
                <select id="department" name="department" class="mt-1 block w-full text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600" required>
                    @foreach (['IT','Tourism and Hospitality','General Education','Business and Management','Arts and Sciences','Senior High'] as $dept)
                        <option value="{{ $dept }}" @selected(old('department') === $dept)>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="advisory_section_id" value="Advisory Section (Optional)" />
                <select id="advisory_section_id" name="advisory_section_id" class="mt-1 block w-full text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600">
                    <option value="">No advisory section</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}">{{ $section->program?->code }} {{ $section->year_level }}{{ $section->section_code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end"><button class="px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Create Proctor</button></div>
        </form>
    </div></div>
</x-app-layout>
