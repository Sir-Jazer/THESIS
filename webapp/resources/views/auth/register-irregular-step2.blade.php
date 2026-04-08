<x-guest-layout>
    <form method="POST" action="{{ route('register.irregular.step2.store') }}" class="space-y-4" x-data="subjectPicker()">
        @csrf

        <h2 class="text-lg font-semibold text-gray-900">Irregular Student Registration (Step 2 of 2)</h2>
        <p class="text-sm text-gray-600">Program: <span class="font-medium">{{ $program->code }} - {{ $program->name }}</span></p>

        <div>
            <x-input-label for="subject_search" :value="__('Add Subject')" />
            <input id="subject_search" type="text" list="subject_options" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                placeholder="Type subject code or name" @change="addSubject($event.target.value); $event.target.value = ''">
            <datalist id="subject_options">
                @foreach ($subjectsByYear as $year => $subjects)
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->code }} - {{ $subject->name }} (Year {{ $year }})</option>
                    @endforeach
                @endforeach
            </datalist>
            <x-input-error :messages="$errors->get('selected_subject_ids')" class="mt-2" />
        </div>

        <div class="rounded-md border border-gray-200 p-3">
            <p class="text-sm font-medium text-gray-700 mb-2">Selected Subjects</p>
            <template x-if="selected.length === 0">
                <p class="text-sm text-gray-500">No subjects selected yet.</p>
            </template>
            <div class="flex flex-wrap gap-2">
                <template x-for="subject in selected" :key="subject.id">
                    <span class="inline-flex items-center gap-2 rounded-full bg-indigo-100 text-indigo-800 px-3 py-1 text-xs">
                        <span x-text="subject.label"></span>
                        <button type="button" class="text-indigo-900" @click="removeSubject(subject.id)">x</button>
                        <input type="hidden" name="selected_subject_ids[]" :value="subject.id">
                    </span>
                </template>
            </div>
        </div>

        <div class="rounded-md bg-gray-50 p-3 border border-gray-200">
            <p class="text-sm text-gray-700 font-medium mb-1">Available Subjects by Year Level</p>
            @foreach ($subjectsByYear as $year => $subjects)
                <div class="mb-2">
                    <p class="text-xs font-semibold text-gray-600">Year {{ $year }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $subjects->pluck('code')->join(', ') }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between">
            <a class="text-sm text-gray-600 hover:text-gray-900" href="{{ route('register.irregular') }}">Back</a>
            <x-primary-button>{{ __('Submit Registration') }}</x-primary-button>
        </div>
    </form>

    <script>
        function subjectPicker() {
            const allSubjects = [
                @foreach ($subjectsByYear as $year => $subjects)
                    @foreach ($subjects as $subject)
                        {
                            id: {{ $subject->id }},
                            label: @js($subject->code . ' - ' . $subject->name . ' (Year ' . $year . ')')
                        },
                    @endforeach
                @endforeach
            ];

            return {
                selected: [],
                addSubject(value) {
                    const id = parseInt(value, 10);

                    if (Number.isNaN(id) || this.selected.find(item => item.id === id)) {
                        return;
                    }

                    const subject = allSubjects.find(item => item.id === id);
                    if (!subject) {
                        return;
                    }

                    this.selected.push(subject);
                },
                removeSubject(id) {
                    this.selected = this.selected.filter(item => item.id !== id);
                }
            };
        }
    </script>
</x-guest-layout>
