{{--
    Bouton primaire standardisé — bg-indigo-600, rounded-md, text-sm font-semibold,
    déjà le style dominant sur les pages construites après le scaffolding Breeze initial
    (celui-ci reprenait encore l'ancien style Breeze : bg-gray-800, uppercase, text-xs).
--}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition']) }}>
    {{ $slot }}
</button>
