@extends('layouts.app')

@php($editing = isset($assignment))

@section('title', ($editing ? 'Glade bewerken' : 'Glade maken').' · A-Mazing 20')

@section('content')
<section class="detail-header builder-header">
    <div><a class="back-link" href="{{ $editing ? route('assignments.show', $assignment) : route('assignments.index') }}">← {{ $editing ? 'Terug naar de opdracht' : 'Alle opdrachten' }}</a><span class="eyebrow">Glade-ontwerper</span><h1>{{ $editing ? 'Pas je glade aan.' : 'Bouw je eigen glade.' }}</h1><p>Selecteer een tegel en schilder de 20×20-kaart. Iedere glade heeft precies één starttegel en minimaal één doel nodig.</p></div>
    <div class="header-facts"><div><span>Formaat</span><strong>20 × 20</strong></div><div><span>Geselecteerd</span><strong id="selectedCode">C3</strong></div></div>
</section>

<section class="content-section builder-section">
    @if ($errors->any())<div class="validation"><strong>De glade kon niet worden opgeslagen.</strong> {{ $errors->first() }}</div>@endif
    <form id="gladeForm" method="post" action="{{ $editing ? route('glades.update', $assignment) : route('glades.store') }}">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="builder-layout">
            <aside class="palette-panel">
                <h2>Tegelpalet</h2><p>Kies een tegel en klik of sleep over het veld.</p>
                <div class="palette-groups">
                    <fieldset><legend>Kleuren</legend><div class="swatches">@foreach(range(0, 8) as $color)<button type="button" class="palette-button color-{{ $color }} {{ $color === 3 ? 'selected' : '' }}" data-tile="C{{ $color }}">C{{ $color }}</button>@endforeach</div></fieldset>
                    <fieldset><legend>Objecten</legend><div class="object-buttons"><button type="button" data-tile="O0">◆ Spijkers</button><button type="button" data-tile="O1">♣ Heg</button><button type="button" data-tile="O2">● Muur (O2)</button><button type="button" data-tile="O3">▰ Hout</button><button type="button" data-tile="R1">↻ Draai</button></div></fieldset>
                    <fieldset><legend>Bonussen</legend><div class="object-buttons">@foreach(range(1, 9) as $bonus)<button type="button" data-tile="E{{ $bonus }}">✦ E{{ $bonus }} · €{{ 2 ** $bonus }}</button>@endforeach</div></fieldset>
                    <fieldset><legend>Bommen</legend><div class="object-buttons">@foreach(range(0, 8) as $timer)<button type="button" data-tile="B{{ $timer }}">● B{{ $timer }} · {{ $timer === 0 ? 'direct' : $timer.' sec' }}</button>@endforeach</div></fieldset>
                    <fieldset><legend>Start en doel</legend><div class="object-buttons"><button type="button" data-tile="S0">▲ Start N</button><button type="button" data-tile="S1">▶ Start O</button><button type="button" data-tile="S2">▼ Start Z</button><button type="button" data-tile="S3">◀ Start W</button><button type="button" data-tile="D1">⌖ Doel 1</button></div></fieldset>
                </div>
                <button type="button" class="button secondary full" id="fillField">Vul met C3</button>
                <button type="button" class="button secondary full palette-secondary-action" id="wallBorder">Plaats murenrand</button>
            </aside>
            <div class="builder-canvas"><div class="map-card"><x-glade-map :tiles="preg_split('/\s+/', old('map_definition', $defaultMap))" editable /></div><div class="builder-status"><span id="tileCounts">1 start · 1 doel</span><span>Tip: houd de muisknop ingedrukt om te schilderen</span></div></div>
        </div>

        <input type="hidden" name="map_definition" id="mapDefinition" value="{{ old('map_definition', $defaultMap) }}">
        <div class="section-title spaced builder-cost-title"><div><span class="section-number">02</span><h2>Kostenkaart instellen</h2></div><p>Afwijkende waarden worden geel gemarkeerd</p></div>
        <x-cost-editor :defaults="$defaultCosts" :values="$editing ? $assignment->costs : []" />

        <div class="section-title spaced builder-cost-title"><div><span class="section-number">03</span><h2>Opdrachtgegevens</h2></div></div>
        <div class="glade-details">
            <div><label for="name">Naam van de opdracht</label><input id="name" name="name" value="{{ old('name', $editing ? $assignment->name : '') }}" placeholder="Bijvoorbeeld: Team Delta Glade" required></div>
            <div><label for="start_capital">Startkapitaal</label><input id="start_capital" name="start_capital" type="number" min="1" value="{{ old('start_capital', $editing ? $assignment->start_capital : 2024) }}" required></div>
            <div class="wide"><label for="description">Korte toelichting</label><textarea id="description" name="description" rows="3" placeholder="Wat maakt deze glade bijzonder?">{{ old('description', $editing ? $assignment->description : '') }}</textarea></div>
            <div class="wide submit-row"><p>{{ $editing ? 'Bestaande pogingen blijven bewaard.' : 'Na het opslaan verschijnt de glade direct tussen de openstaande opdrachten.' }}</p><button class="button primary" type="submit">{{ $editing ? 'Wijzigingen opslaan' : 'Glade opslaan en openen →' }}</button></div>
        </div>
    </form>
</section>
@endsection

@push('scripts')<script src="{{ asset('js/glade-builder.js') }}"></script>@endpush
