@extends('layouts.app')

@section('title', 'Gemaakte opdrachten · A-Mazing 20')

@section('content')
<section class="page-hero compact">
    <div><span class="eyebrow">Pogingengeschiedenis</span><h1>Gemaakte<br><em>opdrachten.</em></h1></div>
    <div class="hero-aside"><p>Iedere uitvoering wordt afzonderlijk bewaard. Vergelijk oplossingen, kosten en resterend budget zonder eerdere pogingen kwijt te raken.</p></div>
</section>

<section class="content-section">
    <div class="section-title"><div><span class="section-number">01</span><h2>Alle pogingen</h2></div><span>{{ $submissions->total() }} opgeslagen</span></div>
    <div class="submission-table-wrap">
        <table class="submission-table">
            <thead><tr><th>Opdracht</th><th>Status</th><th>Totale kosten</th><th>Resterend</th><th>Uitgevoerd</th><th></th></tr></thead>
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
                <tr><td colspan="6"><div class="empty-state"><h3>Nog geen gemaakte opdrachten</h3><p>Voer eerst een programma uit bij een openstaande opdracht.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $submissions->links() }}
</section>
@endsection
