<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit General Exam Matrix</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-[92rem] mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3">
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('academic-head.general-exam-matrix.update', $matrix) }}" class="space-y-6">
                        @csrf
                        @method('PUT')
                        @include('academic-head.general-exam-matrix.partials.form', ['matrix' => $matrix])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
