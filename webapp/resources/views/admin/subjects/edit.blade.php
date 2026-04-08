<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">Edit Subject</h2></x-slot>
    <div class="py-8"><div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        @include('admin.subjects.partials.form', ['action' => route('admin.subjects.update', $subject), 'method' => 'PUT', 'subject' => $subject])
    </div></div>
</x-app-layout>
