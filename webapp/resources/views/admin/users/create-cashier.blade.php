<x-app-layout>
    <x-slot name="header"><h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">Register Cashier</h2></x-slot>
    <div class="py-8"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('admin.users.cashier.store') }}" class="bg-slate-800 dark:bg-slate-800 p-6 shadow-lg sm:rounded-lg space-y-4">
            @csrf
            @include('admin.users.partials.base-user-fields')
            <div>
                <x-input-label for="employee_id" value="Employee ID" />
                <x-text-input id="employee_id" name="employee_id" class="mt-1 block w-full text-base" :value="old('employee_id')" required />
            </div>
            <div class="flex justify-end"><button class="px-4 py-2.5 text-base bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Create Cashier</button></div>
        </form>
    </div></div>
</x-app-layout>
