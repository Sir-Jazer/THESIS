@if ($errors->any())
    <div class="bg-red-900 dark:bg-red-900 border border-red-700 dark:border-red-700 text-red-100 dark:text-red-100 p-3 rounded-lg text-base">
        <ul class="list-disc list-inside font-semibold">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="first_name" value="First Name" />
        <x-text-input id="first_name" name="first_name" class="mt-1 block w-full text-base" :value="old('first_name')" required />
    </div>
    <div>
        <x-input-label for="last_name" value="Last Name" />
        <x-text-input id="last_name" name="last_name" class="mt-1 block w-full text-base" :value="old('last_name')" required />
    </div>
</div>

<div>
    <x-input-label for="email" value="Email" />
    <x-text-input id="email" type="email" name="email" class="mt-1 block w-full text-base" :value="old('email')" required />
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="password" value="Password" />
        <x-text-input id="password" type="password" name="password" class="mt-1 block w-full text-base" required />
    </div>
    <div>
        <x-input-label for="password_confirmation" value="Confirm Password" />
        <x-text-input id="password_confirmation" type="password" name="password_confirmation" class="mt-1 block w-full text-base" required />
    </div>
</div>
