@extends('layouts.app')

@section('title', 'Opdrachten · A-Mazing 20')

@section('content')
<section class="page-hero compact">
    <div><span class="eyebrow">Sandboxomgeving</span><h1>Openstaande<br><em>opdrachten.</em></h1></div>
    <div class="hero-aside"><p>Kies een glade, programmeer je griever en probeer een zo hoog mogelijke score neer te zetten. Je kunt iedere opdracht zo vaak opnieuw maken als je wilt.</p><a class="button secondary" href="{{ route('glades.create') }}">+ Eigen glade maken</a></div>
</section>

<section class="content-section">
    <div class="section-title"><div><span class="section-number">01</span><h2>Opdrachten overzicht</h2></div><span>{{ $assignments->count() }} beschikbaar</span></div>
    <div class="assignment-list">
        @forelse ($assignments as $assignment)
            <article class="assignment-row">
                <div class="assignment-index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                <div class="assignment-name"><span>{{ $assignment->is_custom ? 'Eigen glade' : 'Sandbox' }}</span><h3>{{ $assignment->name }}</h3></div>
                <div class="assignment-meta"><span>Startbudget</span><strong>€ {{ number_format($assignment->start_capital, 0, ',', '.') }}</strong></div>
                <div class="assignment-meta"><span>Pogingen</span><strong>{{ $assignment->submissions_count }}</strong></div>
                <a class="row-action" href="{{ route('assignments.show', $assignment) }}">Maak opdracht <b>→</b></a>
            </article>
        @empty
            <div class="empty-state"><h3>Nog geen opdrachten</h3><p>Maak eerst een eigen glade om te beginnen.</p></div>
        @endforelse
    </div>
</section>
@endsection
