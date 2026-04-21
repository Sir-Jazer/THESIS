<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Student Payment Management</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-slate-700">Search and manage student clearances</p>
                <p class="mt-8 text-sm text-slate-600">Current context: {{ $setting?->academic_year ?? 'Not set' }} | {{ $setting?->semester ?? 'Not set' }} | {{ $setting?->exam_period ?? 'Not set' }}</p>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <form method="GET" action="{{ route('cashier.student-payments.index') }}" class="grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label for="search" class="text-sm font-medium text-slate-700">Search</label>
                        <input id="search" name="search" type="text" value="{{ $filters['search'] }}" placeholder="Student ID, name, or email" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="program_id" class="text-sm font-medium text-slate-700">Program</label>
                        <select id="program_id" name="program_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All Programs</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected((int) $filters['program_id'] === (int) $program->id)>{{ $program->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="year_level" class="text-sm font-medium text-slate-700">Year Level</label>
                        <select id="year_level" name="year_level" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">All Levels</option>
                            @foreach ([1, 2, 3, 4, 5, 6] as $yearLevel)
                                <option value="{{ $yearLevel }}" @selected((int) $filters['year_level'] === $yearLevel)>Year {{ $yearLevel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4 flex justify-end gap-3">
                        <a href="{{ route('cashier.student-payments.index') }}" class="inline-flex items-center rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Reset</a>
                        <button type="submit" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Apply Filters</button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th class="rounded-l-xl px-4 py-3 text-left font-semibold">Student ID</th>
                                <th class="px-4 py-3 text-left font-semibold">Name</th>
                                <th class="px-4 py-3 text-left font-semibold">Program</th>
                                <th class="px-4 py-3 text-left font-semibold">Year &amp; Section</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="rounded-r-xl px-4 py-3 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($students as $student)
                                @php
                                    $profile = $student->studentProfile;
                                    $permit = $student->current_exam_permit;
                                    $sectionLabel = $profile?->section?->section_code ?? 'No section';
                                    $statusLabel = $permit?->is_active ? 'Cleared' : 'Pending';
                                @endphp
                                <tr>
                                    <td class="px-4 py-4 text-slate-700">{{ $profile?->student_id ?? 'N/A' }}</td>
                                    <td class="px-4 py-4 text-slate-800">
                                        <div class="font-medium">{{ $student->full_name }}</div>
                                        <div class="text-xs text-slate-500">{{ $student->enrolled_subject_count }} enrolled subjects</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">{{ $profile?->program?->code ?? 'N/A' }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ $profile?->year_level ? 'Year ' . $profile->year_level : 'N/A' }} - {{ $sectionLabel }}</td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusLabel === 'Cleared' ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if ($profile)
                                            @if ($permit?->is_active)
                                                <form method="POST" action="{{ route('cashier.student-payments.revoke', $profile) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Revoke Permit</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('cashier.student-payments.generate', $profile) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-amber-300">Generate Permit</button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No students matched the current filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $students->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
