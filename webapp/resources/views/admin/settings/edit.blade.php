<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">Academic Timeline Settings</h2></x-slot>
    <div class="py-8"><div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-4 bg-green-900 dark:bg-green-900 border border-green-700 dark:border-green-700 text-green-100 dark:text-green-100 px-4 py-3 rounded-lg text-base font-semibold">{{ session('status') }}</div>
        @endif
        <form method="POST" action="{{ route('admin.settings.update') }}" class="bg-slate-800 dark:bg-slate-800 p-6 shadow-lg sm:rounded-lg space-y-4">
            @csrf
            @method('PUT')
            <div>
                <x-input-label for="academic_year" value="Academic Year" />
                <x-text-input id="academic_year" name="academic_year" class="mt-1 block w-full text-base" :value="old('academic_year', $setting?->academic_year ?? '2025-2026')" required />
            </div>
            <div>
                <x-input-label for="semester" value="Current Semester" />
                <select id="semester" name="semester" class="mt-1 block w-full text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600">
                    <option value="1st Semester" @selected(old('semester', $setting?->semester) === '1st Semester')>1st Semester</option>
                    <option value="2nd Semester" @selected(old('semester', $setting?->semester) === '2nd Semester')>2nd Semester</option>
                </select>
            </div>
            <div>
                <x-input-label for="exam_period" value="Current Exam Period" />
                <select id="exam_period" name="exam_period" class="mt-1 block w-full text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600">
                    @foreach (['Prelim','Midterm','Prefinals','Finals'] as $period)
                        <option value="{{ $period }}" @selected(old('exam_period', $setting?->exam_period) === $period)>{{ $period }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end"><button class="px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Save Timeline</button></div>
        </form>
    </div></div>
</x-app-layout>
