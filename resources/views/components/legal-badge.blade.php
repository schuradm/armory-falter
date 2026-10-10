@props(['legalClass'])
@php
    $code = $legalClass->code;
    [$label, $style] = match (true) {
        $legalClass->requires_entitlement && str_starts_with($code, 'ammo') => ['Erwerbsberechtigung', 'bg-red-500/15 text-red-300 light:text-red-700'],
        $legalClass->requires_entitlement => ['WBK / Jagdschein', 'bg-red-500/15 text-red-300 light:text-red-700'],
        $legalClass->shipping_mode->value === 'pickup_only' => ['Nur Abholung', 'bg-amber-500/15 text-amber-300 light:text-amber-700'],
        $legalClass->min_age >= 18 => ['Ab '.$legalClass->min_age, 'bg-amber-500/15 text-amber-300 light:text-amber-700'],
        default => [null, null],
    };
@endphp
@if ($label)
    <span {{ $attributes->merge(['class' => "badge $style"]) }} title="{{ $legalClass->name }}"><x-icon name="shield" class="size-3"/>{{ $label }}</span>
@endif
