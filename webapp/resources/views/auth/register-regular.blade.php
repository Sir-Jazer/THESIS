<x-guest-layout>
    <form method="POST" action="{{ route('register.regular.store') }}" class="space-y-4">
        @csrf

        <h2 class="text-lg font-semibold text-gray-900">Regular Student Registration</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="first_name" :value="__('First Name')" />
                <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required autofocus />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="last_name" :value="__('Last Name')" />
                <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="student_id" :value="__('Student ID')" />
            <x-text-input id="student_id" class="block mt-1 w-full" type="text" name="student_id" :value="old('student_id')" required />
            <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="program_id" :value="__('Program')" />
            <select id="program_id" name="program_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                <option value="">Select Program</option>
                @foreach ($programs as $program)
                    <option value="{{ $program->id }}" @selected(old('program_id') == $program->id)>{{ $program->code }} - {{ $program->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('program_id')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="year_level" :value="__('Year Level')" />
                <select id="year_level" name="year_level" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                    <option value="">Select Year Level</option>
                    @for ($year = 1; $year <= 6; $year++)
                        <option value="{{ $year }}" @selected(old('year_level') == $year)>{{ $year }}</option>
                    @endfor
                </select>
                <x-input-error :messages="$errors->get('year_level')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="section_id" :value="__('Section (Optional)')" />
                <select id="section_id" name="section_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">No section selected</option>
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected(old('section_id') == $section->id)>
                            {{ $section->program?->code }} {{ $section->year_level }}{{ $section->section_code }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('section_id')" class="mt-2" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a class="text-sm text-gray-600 hover:text-gray-900" href="{{ route('register') }}">Back</a>
            <x-primary-button>{{ __('Submit Registration') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
