@extends('layouts.app')

@section('title', 'Poging #'.$submission->id.' · A-Mazing 20')

@section('content')
<section class="result-hero {{ $submission->status === 'Doel bereikt' ? 'success-result' : '' }}">
    <div><a class="back-link light" href="{{ route('submissions.index') }}">← Gemaakte opdrachten</a><span class="eyebrow">{{ $submission->assignment->name }} · poging #{{ $submission->id }}</span><h1>{{ $submission->status }}</h1><p>Deze oplossing is opgeslagen en kan altijd opnieuw bekeken worden.</p></div>
    <div class="result-score"><span>Resterend budget</span><strong>€ {{ number_format($submission->remaining_budget, 0, ',', '.') }}</strong><small>Totale kosten: € {{ number_format($submission->total_cost, 0, ',', '.') }}</small></div>
</section>

<section class="content-section">
    <div class="result-actions"><a class="button primary" href="{{ route('assignments.show', $submission->assignment) }}">Opdracht opnieuw maken</a><a class="button secondary" href="{{ route('submissions.index') }}">Alle pogingen</a></div>
    <div class="result-grid">
        <div><div class="section-title"><div><span class="section-number">01</span><h2>Eindpositie</h2></div></div><div class="map-card"><x-glade-map :tiles="$submission->assignment->tiles()" :final-state="$submission->final_state" /></div></div>
        <aside class="summary-card"><span class="eyebrow">Samenvatting</span><h2>Kosten en status</h2><dl><div><dt>Status</dt><dd>{{ $submission->status }}</dd></div><div><dt>Software + verbruik</dt><dd>€ {{ number_format($submission->total_cost, 0, ',', '.') }}</dd></div><div><dt>Startkapitaal</dt><dd>€ {{ number_format($submission->assignment->start_capital, 0, ',', '.') }}</dd></div><div><dt>Resterend budget</dt><dd>€ {{ number_format($submission->remaining_budget, 0, ',', '.') }}</dd></div>@foreach(($submission->final_state['variables'] ?? []) as $name => $value)<div><dt>Variabele {{ $name }}</dt><dd>{{ $value }}</dd></div>@endforeach</dl></aside>
    </div>

    <div class="result-detail-grid">
        <div><div class="section-title"><div><span class="section-number">02</span><h2>Opgeslagen code</h2></div></div><pre class="saved-code"><code>{{ $submission->code }}</code></pre></div>
        <div><div class="section-title"><div><span class="section-number">03</span><h2>Uitvoerlog</h2></div></div><div class="execution-log">@foreach($submission->execution_log as $line)<p><span>{{ str_pad((string)$loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $line }}</p>@endforeach</div></div>
    </div>
</section>
@endsection
