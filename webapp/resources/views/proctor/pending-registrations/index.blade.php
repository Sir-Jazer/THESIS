<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pending Registrations</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if (! $hasSections)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-slate-500">No advisory section is assigned to your account.</p>
                </div>
            @elseif ($pendingStudents->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-slate-500">No pending registrations in your advisory section(s).</p>
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-semibold text-slate-800">Pending Students</h3>
                        <span class="text-xs text-slate-500">{{ $pendingStudents->count() }} {{ Str::plural('student', $pendingStudents->count()) }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-slate-700">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold">Name</th>
                                    <th class="px-4 py-2 text-left font-semibold">Email</th>
                                    <th class="px-4 py-2 text-left font-semibold">Student ID</th>
                                    <th class="px-4 py-2 text-left font-semibold">Program</th>
                                    <th class="px-4 py-2 text-left font-semibold">Section</th>
                                    <th class="px-4 py-2 text-left font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($pendingStudents as $student)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-medium text-slate-800">
                                            {{ trim($student->first_name . ' ' . $student->last_name) }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $student->email }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $student->studentProfile?->student_id ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $student->studentProfile?->program?->code ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $student->studentProfile?->section?->section_code ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <form method="POST"
                                                    action="{{ route('proctor.pending-registrations.approve', $student) }}"
                                                    onsubmit="return confirm('Approve {{ addslashes(trim($student->first_name . ' ' . $student->last_name)) }}?')">
                                                    @csrf
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-1.5 rounded-md bg-green-600 text-white text-xs font-semibold hover:bg-green-700 transition">
                                                        Approve
                                                    </button>
                                                </form>
                                                <form method="POST"
                                                    action="{{ route('proctor.pending-registrations.reject', $student) }}"
                                                    onsubmit="return confirm('Reject {{ addslashes(trim($student->first_name . ' ' . $student->last_name)) }}? This will archive their account.')">
                                                    @csrf
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-1.5 rounded-md bg-red-600 text-white text-xs font-semibold hover:bg-red-700 transition">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
