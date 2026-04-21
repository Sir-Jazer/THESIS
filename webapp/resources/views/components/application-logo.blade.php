@php
    $logoFile = file_exists(public_path('sti_logo_full.png')) ? asset('sti_logo_full.png') : asset('sti_logo.png');
@endphp

<img src="{{ $logoFile }}" alt="STI Logo" {{ $attributes->merge(['class' => 'object-contain']) }} />
