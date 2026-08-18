@php
    $user = auth()->user();
@endphp

@if($user && !$user->hasAnyRole(['eleve', 'parent']))
    @php
        $context = app(\App\Services\SchoolYearContext::class);
        $currentYear = $context->current();
        $allYears = \App\Models\SchoolYear::orderBy('year_string', 'desc')->get();
    @endphp

    @if($currentYear)
        <div class="flex flex-wrap items-center gap-2 py-2 text-sm">
            <span class="text-gray-500 dark:text-gray-400">Année consultée :</span>
            <form method="POST" action="{{ route('context.school-year.update') }}" class="inline"
                  x-data
                  x-on:change="$el.submit()">
                @csrf
                <select name="school_year_id" class="rounded-md border-gray-300 bg-white py-1 pl-2 pr-7 font-semibold text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-indigo-400">
                    @foreach($allYears as $year)
                        <option value="{{ $year->id }}" {{ $currentYear->id === $year->id ? 'selected' : '' }}>
                            {{ $year->year_string }}{{ $year->is_active ? ' (active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </form>
            @unless($context->isViewingActiveYear())
                <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 text-xs font-medium text-amber-800 dark:text-amber-300">
                    Historique — lecture d'une année passée
                </span>
            @endunless
        </div>
    @endif
@endif
