@extends('layouts.app')

@section('title', $assignment->name.' · A-Mazing 20')

@section('content')
<section class="detail-header">
    <div><a class="back-link" href="{{ route('assignments.index') }}">← Alle opdrachten</a><span class="eyebrow">{{ $assignment->is_custom ? 'Eigen glade' : 'Zandbakopdracht' }}</span><h1>{{ $assignment->name }}</h1><p>{{ $assignment->description }}</p></div>
    <div class="header-facts"><div><span>Startkapitaal</span><strong>€ {{ number_format($assignment->start_capital, 0, ',', '.') }}</strong></div><div><span>Eerdere pogingen</span><strong>{{ $assignment->submissions_count }}</strong></div></div>
</section>

<section class="content-section assignment-workspace">
    @if ($errors->any())<div class="validation">{{ $errors->first() }}</div>@endif
    <div class="section-title"><div><span class="section-number">01</span><h2>De Glade</h2></div><div class="legend"><span><i class="legend-field"></i>Veld</span><span><i class="legend-obstacle"></i>Obstakel</span><span><i class="legend-goal"></i>Doel</span><span><i class="legend-bonus"></i>Bonus</span></div></div>
    <div class="map-card"><x-glade-map :tiles="$assignment->tiles()" /></div>

    <div class="section-title spaced"><div><span class="section-number">02</span><h2>Kostenkaart</h2></div><p>Gemarkeerde waarden wijken af van de standaard.</p></div>
    <x-cost-card :assignment="$assignment" />

    <div class="section-title spaced"><div><span class="section-number">03</span><h2>Jouw programma</h2></div><span>Iedere uitvoering wordt als nieuwe poging opgeslagen</span></div>
    <form class="code-workspace" method="post" action="{{ route('submissions.store', $assignment) }}">
        @csrf
        <div class="editor">
            <div class="editor-bar"><span><i></i><i></i><i></i></span><b>oplossing.20</b><small id="lineCount">1 regel</small></div>
            <div class="editor-body"><pre id="lineNumbers">1</pre><textarea id="code" name="code" spellcheck="false" required>{{ old('code', "stapVooruit\nstapVooruit\nstapVooruit\nstapVooruit\ndraaiRechts\nstapVooruit") }}</textarea></div>
            <div class="editor-footer"><span>Ondersteund: stapVooruit, stapAchteruit, draaiLinks, draaiRechts</span><button class="button primary" type="submit">▶ Uitvoeren en opslaan</button></div>
        </div>
        <aside class="run-help"><span class="run-icon">⌘</span><h3>Hoe werkt uitvoeren?</h3><p>De code wordt door de Laravel-server gecontroleerd en uitgevoerd. Daarna zie je de status, totale kosten, resterend budget en het volledige logboek.</p><ul><li>Een nieuwe uitvoering overschrijft niets.</li><li>Bonussen worden bij je budget opgeteld.</li><li>Doelen moeten op volgorde worden bereikt.</li></ul></aside>
    </form>
</section>
@endsection

@push('scripts')<script src="{{ asset('js/editor.js') }}"></script>@endpush
