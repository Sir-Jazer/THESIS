<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-3xl text-gray-100 dark:text-gray-100 leading-tight">User Management</h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.users.proctor.create') }}" class="text-base px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">New Proctor</a>
                <a href="{{ route('admin.users.cashier.create') }}" class="text-base px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">New Cashier</a>
                <a href="{{ route('admin.users.academic-head.create') }}" class="text-base px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">New Academic Head</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-900 dark:bg-green-900 border border-green-700 dark:border-green-700 text-green-100 dark:text-green-100 px-4 py-3 rounded-lg text-base font-semibold">{{ session('status') }}</div>
            @endif

            <div class="bg-slate-800 dark:bg-slate-800 p-4 shadow-lg sm:rounded-lg">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name/email" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600">
                    <select name="role" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600">
                        <option value="">All roles</option>
                        @foreach (['student','proctor','cashier','academic_head','admin'] as $role)
                            <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="text-base border-slate-600 rounded-md dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600">
                        <option value="">All statuses</option>
                        @foreach (['pending','active','deactivated','archived'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2.5 text-base bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150 ease-in-out">Filter</button>
                </form>
            </div>

            <div class="bg-slate-800 dark:bg-slate-800 shadow-lg sm:rounded-lg overflow-hidden">
                <table class="min-w-full text-base">
                    <thead class="bg-slate-700 dark:bg-slate-700 text-gray-100 dark:text-gray-100">
                        <tr>
                            <th class="text-left p-4 font-semibold">Name</th>
                            <th class="text-left p-4 font-semibold">Email</th>
                            <th class="text-left p-4 font-semibold">Role</th>
                            <th class="text-left p-4 font-semibold">Status</th>
                            <th class="text-left p-4 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="border-t border-slate-700 dark:border-slate-700 hover:bg-slate-750 dark:hover:bg-slate-750">
                                <td class="p-4 text-gray-100 dark:text-gray-100">{{ $user->full_name }}</td>
                                <td class="p-4 text-gray-100 dark:text-gray-100">{{ $user->email }}</td>
                                <td class="p-4 text-gray-100 dark:text-gray-100">{{ $user->role }}</td>
                                <td class="p-4 text-gray-100 dark:text-gray-100">{{ $user->status }}</td>
                                <td class="p-4">
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('admin.users.status.update', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <select name="status" class="text-sm border-slate-600 rounded dark:bg-slate-700 dark:text-gray-100 dark:border-slate-600">
                                                @foreach (['pending','active','deactivated','archived'] as $status)
                                                    <option value="{{ $status }}" @selected($user->status === $status)>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                            <button class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition duration-150 ease-in-out">Update</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.send-reset', $user) }}">
                                            @csrf
                                            <button class="px-3 py-1.5 text-sm bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded transition duration-150 ease-in-out">Send Reset</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-center text-gray-400 dark:text-gray-400 text-base">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-gray-100 dark:text-gray-100">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
