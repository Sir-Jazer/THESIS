<x-guest-layout>
    <div class="mb-6 text-sm text-gray-600">
        Select your student type to continue registration.
    </div>

    <div class="space-y-4">
        <a href="{{ route('register.regular') }}" class="block rounded-lg border border-gray-300 p-4 hover:border-indigo-400 hover:bg-indigo-50 transition">
            <h3 class="font-semibold text-gray-900">Regular Student</h3>
            <p class="text-sm text-gray-600 mt-1">Register with full section and year-level details.</p>
        </a>

        <a href="{{ route('register.irregular') }}" class="block rounded-lg border border-gray-300 p-4 hover:border-indigo-400 hover:bg-indigo-50 transition">
            <h3 class="font-semibold text-gray-900">Irregular Student</h3>
            <p class="text-sm text-gray-600 mt-1">Register in two steps and select current subjects.</p>
        </a>
    </div>

    <div class="mt-6 text-center text-sm text-gray-600">
        Already registered?
        <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Sign in</a>
    </div>
</x-guest-layout>
