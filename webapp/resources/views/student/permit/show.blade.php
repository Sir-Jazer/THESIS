<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Permit</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-600"><span class="font-semibold text-slate-900">Academic Year:</span> {{ $setting?->academic_year ?? 'Not set' }}</p>
                <p class="mt-2 text-sm text-slate-600"><span class="font-semibold text-slate-900">Term:</span> {{ $setting?->semester ?? 'Not set' }}</p>
                <p class="mt-2 text-sm text-slate-600"><span class="font-semibold text-slate-900">Exam Period:</span> {{ $setting?->exam_period ?? 'Not set' }}</p>
            </div>

            <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
                @if ($permit)
                    <div class="grid gap-8 lg:grid-cols-[260px,1fr] lg:items-start">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex justify-center rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(220)->margin(1)->generate($qrPayload) !!}
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Active Exam Permit</h3>
                                <p class="mt-1 text-sm text-slate-600">This QR code is reusable for all of your enrolled subjects in the current exam period until it is revoked.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Generated</p>
                                    <p class="mt-2 text-sm font-medium text-slate-800">{{ optional($permit->generated_at)->format('Y-m-d h:i A') ?? 'Not recorded' }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Permit Token</p>
                                    <p class="mt-2 break-all text-sm font-medium text-slate-800">{{ $permit->qr_token }}</p>
                                </div>
                            </div>

                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                                Show this permit to the proctor before each scheduled subject exam. Once a subject attendance is logged, that subject will show as Cleared on your My Subjects page.
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                        <h3 class="text-lg font-semibold text-slate-900">Permit not generated yet</h3>
                        <p class="mt-2 text-sm text-slate-600">Your QR permit will appear here after the cashier generates it for the current exam period.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
