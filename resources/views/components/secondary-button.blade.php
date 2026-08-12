{{-- Voir primary-button.blade.php — même correctif de cohérence. --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-md border border-gray-300 dark:border-slate-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition']) }}>
    {{ $slot }}
</button>
