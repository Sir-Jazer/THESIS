<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">General Exam Matrix</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[105rem] mx-auto sm:px-6 lg:px-8 space-y-4">
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

            <a href="{{ route('academic-head.general-exam-matrix.create') }}" class="inline-block px-4 py-2.5 text-sm bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg">Create Matrix</a>

            <div class="space-y-5">
                @forelse ($matrices as $matrix)
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="border-b border-slate-200 px-4 py-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">{{ $matrix->name ?: 'General Exam Matrix' }}</h3>
                                <p class="text-sm text-slate-600 mt-1">
                                    {{ $matrix->academic_year }} | {{ $matrix->semester === 1 ? '1st Semester' : '2nd Semester' }} | {{ $matrix->exam_period }}
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    Status:
                                    @if ($matrix->status === 'uploaded')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700 font-semibold">Uploaded</span>
                                        @if ($matrix->uploaded_at)
                                            <span class="ml-1">{{ $matrix->uploaded_at->format('M d, Y h:i A') }}</span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-amber-700 font-semibold">Draft</span>
                                    @endif
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('academic-head.general-exam-matrix.edit', $matrix) }}" class="px-3 py-2 text-xs rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">Edit</a>

                                <form method="POST" action="{{ route('academic-head.general-exam-matrix.upload', $matrix) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-2 text-xs rounded bg-emerald-600 text-white hover:bg-emerald-700 font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors">{{ $matrix->status === 'uploaded' ? 'Re-upload' : 'Upload' }}</button>
                                </form>

                                <form method="POST" action="{{ route('academic-head.general-exam-matrix.destroy', $matrix) }}" onsubmit="return confirm('Delete this matrix?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-2 text-xs rounded bg-red-600 text-white hover:bg-red-700 font-semibold">Delete</button>
                                </form>
                            </div>
                        </div>

                        <div class="p-4">
                            @include('academic-head.general-exam-matrix.partials.matrix-table', [
                                'matrix' => $matrix,
                                'standardPeriods' => $standardPeriods,
                                'examDayCount' => $examDayCount,
                            ])
                        </div>
                    </div>
                @empty
                    <div class="bg-white shadow-sm sm:rounded-lg px-4 py-10 text-center text-slate-500">
                        No matrix records yet.
                    </div>
                @endforelse
            </div>

            <div>
                {{ $matrices->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
