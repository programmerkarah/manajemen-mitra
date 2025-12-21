<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# Ups, Ada Masalah!
@else
# Halo!
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success' => 'success',
        'error' => 'error',
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
Salam,<br>
**Tim {{ config('app.name') }}**
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
---

**Catatan:** Jika Anda mengalami kesulitan mengklik tombol "{{ $actionText }}", salin dan tempel URL berikut ke browser web Anda:

<span class="break-all">{{ $actionUrl }}</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
