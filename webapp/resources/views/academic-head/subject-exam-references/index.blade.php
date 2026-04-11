<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Subject Exam References</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
            @if (session('status'))
                <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <h3 class="font-semibold text-gray-800 mb-3">Reference Scope</h3>
                <form method="GET" action="{{ route('academic-head.subject-exam-references.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                    <div>
                        <x-input-label for="academic_year" value="Academic Year" />
                        <input id="academic_year" type="text" name="academic_year" class="mt-1 block w-full rounded-md border-gray-300" value="{{ $filters['academic_year'] }}" required>
                    </div>
                    <div>
                        <x-input-label for="semester" value="Semester" />
                        <select id="semester" name="semester" class="mt-1 block w-full rounded-md border-gray-300" required>
                            <option value="1" @selected((int) $filters['semester'] === 1)>1st Semester</option>
                            <option value="2" @selected((int) $filters['semester'] === 2)>2nd Semester</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="exam_period" value="Exam Period" />
                        <select id="exam_period" name="exam_period" class="mt-1 block w-full rounded-md border-gray-300" required>
                            @foreach (['Prelim', 'Midterm', 'Prefinals', 'Finals'] as $period)
                                <option value="{{ $period }}" @selected($filters['exam_period'] === $period)>{{ $period }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="program_id" value="Program (optional)" />
                        <select id="program_id" name="program_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All programs</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected((int) $filters['program_id'] === $program->id)>{{ $program->code }} - {{ $program->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded bg-slate-700 text-white hover:bg-slate-800 font-semibold">Apply Scope</button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-4">
                <form method="POST" action="{{ route('academic-head.subject-exam-references.update') }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="academic_year" value="{{ $filters['academic_year'] }}">
                    <input type="hidden" name="semester" value="{{ $filters['semester'] }}">
                    <input type="hidden" name="exam_period" value="{{ $filters['exam_period'] }}">
                    <input type="hidden" name="program_id" value="{{ $filters['program_id'] }}">

                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-800">Exam Reference Numbers</h3>
                            <p class="text-sm text-gray-500">
                                Update exam reference numbers for {{ $filters['academic_year'] }}, {{ (int) $filters['semester'] === 1 ? '1st Semester' : '2nd Semester' }}, {{ $filters['exam_period'] }}.
                                Leave a value blank to clear it for this scope only.
                            </p>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700 font-semibold">Save Reference Numbers</button>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-100 text-slate-700">
                                <tr>
                                    <th class="px-3 py-2 text-left">Subject Code</th>
                                    <th class="px-3 py-2 text-left">Course Serial</th>
                                    <th class="px-3 py-2 text-left">Subject Name</th>
                                    <th class="px-3 py-2 text-left">Programs</th>
                                    <th class="px-3 py-2 text-left">Exam Reference Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subjects as $subject)
                                    @php
                                        $existingReference = $subject->examReferences->first()?->exam_reference_number;
                                    @endphp
                                    <tr class="border-t">
                                        <td class="px-3 py-2 font-semibold text-slate-800">{{ $subject->code }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ $subject->course_serial_number ?: 'Not set' }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ $subject->name }}</td>
                                        <td class="px-3 py-2 text-slate-700">{{ $subject->programs->pluck('code')->implode(', ') ?: 'No program linked' }}</td>
                                        <td class="px-3 py-2">
                                            <input
                                                type="text"
                                                name="references[{{ $subject->id }}]"
                                                value="{{ old('references.' . $subject->id, $existingReference) }}"
                                                class="block w-full rounded-md border-gray-300 text-sm"
                                                placeholder="1000012434"
                                            >
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-8 text-center text-slate-500">No subjects available for this filter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $subjects->links() }}
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
