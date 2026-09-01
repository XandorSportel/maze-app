@extends('layouts.app')

@section('title', 'Gemaakte opdrachten · A-Mazing 20')

@section('content')
<section class="page-hero compact">
    <div><span class="eyebrow">Pogingengeschiedenis</span><h1>Gemaakte<br><em>opdrachten.</em></h1></div>
    <div class="hero-aside"><p>Iedere uitvoering wordt afzonderlijk bewaard. Vergelijk oplossingen, kosten en resterend budget zonder eerdere pogingen kwijt te raken.</p></div>
</section>

<section class="content-section">
    @php
        $currentSort = $filters['sort'] ?? 'newest';
        $sortUrl = fn (string $sort) => route('submissions.index', array_merge(request()->except(['sort', 'page']), ['sort' => $sort]));
    @endphp
    <div class="section-title"><div><span class="section-number">01</span><h2>Alle pogingen</h2></div><span>{{ $submissions->total() }} {{ filled($filters['q'] ?? null) ? 'gevonden' : 'opgeslagen' }}</span></div>
    @if($errors->any())<div class="validation">{{ $errors->first() }}</div>@endif
    <form class="filter-panel assignment-search submission-search" method="get" action="{{ route('submissions.index') }}">
        <input type="hidden" name="sort" value="{{ $currentSort }}">
        <label class="search-field"><span>Zoeken</span><span class="search-input"><i>⌕</i><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Opdracht, status of code…" autocomplete="off"></span></label>
        <button class="button primary" type="submit">Zoeken</button>
        @if(filled($filters['q'] ?? null))<a class="clear-filters" href="{{ route('submissions.index', ['sort' => $currentSort]) }}">Zoekopdracht wissen</a>@endif
    </form>
    <div class="submission-table-wrap">
        <table class="submission-table">
            <thead><tr><th>Opdracht</th><th>Status</th><th><a @class(['sortable-heading', 'active' => in_array($currentSort, ['cost_asc', 'cost_desc'])]) href="{{ $sortUrl($currentSort === 'cost_asc' ? 'cost_desc' : 'cost_asc') }}">Totale kosten <span aria-hidden="true">{{ $currentSort === 'cost_asc' ? '↑' : ($currentSort === 'cost_desc' ? '↓' : '↕') }}</span></a></th><th><a @class(['sortable-heading', 'active' => in_array($currentSort, ['remaining_asc', 'remaining_desc'])]) href="{{ $sortUrl($currentSort === 'remaining_asc' ? 'remaining_desc' : 'remaining_asc') }}">Resterend <span aria-hidden="true">{{ $currentSort === 'remaining_asc' ? '↑' : ($currentSort === 'remaining_desc' ? '↓' : '↕') }}</span></a></th><th><a @class(['sortable-heading', 'active' => in_array($currentSort, ['newest', 'oldest'])]) href="{{ $sortUrl($currentSort === 'newest' ? 'oldest' : 'newest') }}">Uitgevoerd <span aria-hidden="true">{{ $currentSort === 'oldest' ? '↑' : ($currentSort === 'newest' ? '↓' : '↕') }}</span></a></th><th></th></tr></thead>
            <tbody>
            @forelse($submissions as $submission)
                <tr>
                    <td><span class="mobile-label">Opdracht</span><strong>{{ $submission->assignment->name }}</strong><small>Poging #{{ $submission->id }}</small></td>
                    <td><span class="status-pill {{ $submission->status === 'Doel bereikt' ? 'positive' : 'neutral' }}">{{ $submission->status }}</span></td>
                    <td>€ {{ number_format($submission->total_cost, 0, ',', '.') }}</td>
                    <td><strong class="money {{ $submission->remaining_budget < 0 ? 'negative' : '' }}">€ {{ number_format($submission->remaining_budget, 0, ',', '.') }}</strong></td>
                    <td>{{ $submission->created_at->format('d-m-Y H:i') }}</td>
                    <td><a class="text-link" href="{{ route('submissions.show', $submission) }}">Bekijk →</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><h3>Geen pogingen gevonden</h3><p>Pas de zoekterm aan om meer resultaten te zien.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">
        {{ $submissions->links() }}
    </div>
</section>
@endsection
