@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Rechercher...',
    'emptyLabel' => 'Aucun',
])

@php
    $selectedOption = collect($options)->first(fn ($option) => (string) $option['value'] === (string) $selected);
@endphp

<div
    x-data="{
        open: false,
        query: @js($selectedOption['label'] ?? ''),
        selectedValue: @js($selected !== null ? (string) $selected : ''),
        options: @js(array_values($options)),
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (! q) return this.options;
            return this.options.filter((o) => o.label.toLowerCase().includes(q));
        },
        currentLabel() {
            const found = this.options.find((o) => String(o.value) === this.selectedValue);
            return found ? found.label : '';
        },
        select(option) {
            this.selectedValue = option ? String(option.value) : '';
            this.query = option ? option.label : '';
            this.open = false;
        },
        closeAndRestore() {
            this.open = false;
            this.query = this.currentLabel();
        },
    }"
    @click.outside="closeAndRestore()"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" x-model="selectedValue">
    <input
        type="text"
        x-model="query"
        @focus="open = true; $el.select()"
        @input="open = true"
        @keydown.escape="closeAndRestore()"
        @keydown.enter.prevent="if (filtered.length === 1) select(filtered[0])"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}
    >
    <div
        x-show="open"
        x-cloak
        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-slate-800"
    >
        <div
            class="cursor-pointer px-3 py-2 text-sm text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-slate-700"
            @click="select(null)"
        >{{ $emptyLabel }}</div>
        <template x-for="option in filtered" :key="option.value">
            <div
                class="cursor-pointer px-3 py-2 text-sm text-gray-900 hover:bg-indigo-50 dark:text-gray-100 dark:hover:bg-slate-700"
                :class="{ 'bg-indigo-50 dark:bg-slate-700': String(option.value) === selectedValue }"
                @click="select(option)"
                x-text="option.label"
            ></div>
        </template>
        <div x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">Aucun résultat</div>
    </div>
</div>
