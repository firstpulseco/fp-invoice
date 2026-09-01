@props([
    'businessLogoUrl' => null,
])

@if ($businessLogoUrl)
    <img src="{{ $businessLogoUrl }}" alt="" {{ $attributes->class('object-contain') }} />
@else
    <span aria-hidden="true" {{ $attributes->class('bg-sky block rounded-full') }}></span>
@endif
