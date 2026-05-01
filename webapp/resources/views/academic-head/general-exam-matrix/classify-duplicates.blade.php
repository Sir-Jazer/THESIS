<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Classify Duplicate Subjects</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-5">
                <h3 class="text-base font-semibold text-slate-900">{{ $matrix->name ?: 'General Exam Matrix' }}</h3>
                <p class="text-sm text-slate-600 mt-1">
                    {{ $matrix->academic_year }} | {{ (int) $matrix->semester === 1 ? '1st Semester' : '2nd Semester' }} | {{ $matrix->exam_period }}
                </p>
                <p class="text-sm text-slate-600 mt-2">
                    Duplicate subjects were detected in multiple slots. Assign each affected section to exactly one batch so each section receives only one schedule slot for the duplicated subject.
                </p>
            </div>

            <form method="POST" action="{{ route('academic-head.general-exam-matrix.save-duplicate-classification', $matrix) }}" class="space-y-5">
                @csrf

                @foreach ($classification['subjects'] as $subject)
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden border border-slate-200">
                        <div class="px-4 py-4 border-b border-slate-200 bg-slate-50">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h4 class="font-semibold text-slate-900">{{ $subject['subject_label'] }}</h4>
                                    <p class="text-xs text-slate-600 mt-1">
                                        @if ($subject['is_complete'])
                                            Batch mapping complete.
                                        @else
                                            {{ $subject['missing_count'] }} section assignment(s) still required.
                                        @endif
                                    </p>
                                </div>
                                <div class="text-xs font-semibold {{ $subject['is_complete'] ? 'text-emerald-700 bg-emerald-100' : 'text-amber-800 bg-amber-100' }} rounded-full px-3 py-1 w-fit">
                                    {{ $subject['is_complete'] ? 'Complete' : 'Incomplete' }}
                                </div>
                            </div>

                            <div class="grid gap-2 mt-3 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ($subject['batches'] as $batch)
                                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                        <div class="text-xs font-semibold text-slate-700">Batch {{ $batch['batch_no'] }}</div>
                                        <div class="text-xs text-slate-600 mt-1">{{ $batch['slot_label'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            @if (count($subject['sections']) === 0)
                                <div class="px-4 py-4 text-sm text-slate-600">
                                    No sections currently match this subject for Semester {{ (int) $matrix->semester }}.
                                </div>
                            @else
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-100 text-slate-700">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Program</th>
                                            <th class="px-3 py-2 text-left">Year</th>
                                            <th class="px-3 py-2 text-left">Section</th>
                                            <th class="px-3 py-2 text-left">Assigned Batch</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        @foreach ($subject['sections'] as $section)
                                            <tr>
                                                <td class="px-3 py-2">{{ $section['program_code'] }}</td>
                                                <td class="px-3 py-2">{{ $section['year_level'] }}</td>
                                                <td class="px-3 py-2">{{ $section['section_code'] }}</td>
                                                <td class="px-3 py-2">
                                                    <select
                                                        name="assignments[{{ $subject['subject_id'] }}][{{ $section['id'] }}]"
                                                        class="w-48 rounded-md border-gray-300 text-sm"
                                                        required
                                                    >
                                                        <option value="">Select batch</option>
                                                        @foreach ($subject['batches'] as $batch)
                                                            <option
                                                                value="{{ $batch['batch_no'] }}"
                                                                @selected((int) ($subject['assignments'][$section['id']] ?? 0) === (int) $batch['batch_no'])
                                                            >
                                                                Batch {{ $batch['batch_no'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700 font-semibold">
                        Save Batch Mapping
                    </button>
                    <a href="{{ route('academic-head.general-exam-matrix.index') }}" class="px-4 py-2 rounded bg-slate-600 text-white hover:bg-slate-700 font-semibold">
                        Back to Matrix List
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
