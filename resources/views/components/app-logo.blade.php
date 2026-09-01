@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Invoice" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center">
            <x-app-mark class="size-8" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Invoice" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center">
            <x-app-mark class="size-8" />
        </x-slot>
    </flux:brand>
@endif
