{{-- Une étape du composant <x-wizard>. Voir components/wizard.blade.php. --}}
@props(['step'])

<div x-show="step === {{ (int) $step }}" x-cloak {{ $attributes->only('class') }}>
    {{ $slot }}
</div>
