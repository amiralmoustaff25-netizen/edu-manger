{{--
    Formulaire multi-étapes réutilisable — pour les formulaires longs à
    découper en étapes (ex. inscription, paiement avec recherche préalable).

    Usage :
    <x-wizard :steps="['Élève', 'Frais', 'Confirmation']">
        <x-wizard-step :step="1">... champs de l'étape 1 ...</x-wizard-step>
        <x-wizard-step :step="2">... champs de l'étape 2 ...</x-wizard-step>
        <x-wizard-step :step="3">... récapitulatif + bouton de soumission ...</x-wizard-step>
    </x-wizard>

    Dans chaque étape, utiliser x-on:click="next()" / "prev()" / "goTo(n)" sur
    les boutons de navigation (le composant expose ces méthodes via son
    x-data). Les étapes suivantes ne sont pas encore validées côté client :
    la validation serveur reste la source de vérité, comme sur le reste de
    l'application — ce composant ne fait que regrouper visuellement les
    champs, il ne remplace pas Form Request/@error.
--}}
@props(['steps' => []])

<div
    x-data="{
        step: 1,
        total: {{ count($steps) }},
        next() { if (this.step < this.total) this.step++; },
        prev() { if (this.step > 1) this.step--; },
        goTo(n) { if (n >= 1 && n <= this.total) this.step = n; },
    }"
    {{ $attributes->only('class') }}
>
    <nav aria-label="Étapes du formulaire" class="mb-8">
        <ol class="flex items-center justify-between">
            @foreach ($steps as $index => $label)
                @php $n = $index + 1; @endphp
                <li class="flex flex-1 items-center {{ !$loop->last ? 'after:mx-3 after:h-px after:flex-1 after:bg-gray-200 dark:after:bg-slate-700' : '' }}">
                    <button
                        type="button"
                        x-on:click="goTo({{ $n }})"
                        :disabled="{{ $n }} > step"
                        class="flex items-center gap-2 text-left disabled:cursor-not-allowed"
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold transition-colors"
                            :class="step > {{ $n }} ? 'bg-indigo-600 text-white' : (step === {{ $n }} ? 'bg-indigo-100 text-indigo-700 ring-2 ring-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-300' : 'bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-gray-500')"
                        >
                            <template x-if="step > {{ $n }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            </template>
                            <template x-if="step <= {{ $n }}"><span x-text="{{ $n }}"></span></template>
                        </span>
                        <span class="hidden text-sm font-medium sm:inline" :class="step === {{ $n }} ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400'">{{ $label }}</span>
                    </button>
                </li>
            @endforeach
        </ol>
    </nav>

    {{ $slot }}
</div>
