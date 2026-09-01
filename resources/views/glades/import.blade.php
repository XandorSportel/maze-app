@extends('layouts.app')

@section('title', 'Glade importeren · A-Mazing 20')

@section('content')
<section class="detail-header import-header">
    <div><a class="back-link" href="{{ route('assignments.index') }}">← Alle opdrachten</a><span class="eyebrow">Screenshot-import</span><h1>Importeer een bestaande glade.</h1><p>Upload een screenshot waarop de volledige 20×20-kaart zichtbaar is. De herkende tegels worden eerst in de editor geopend, zodat je alles kunt controleren voordat de glade wordt opgeslagen.</p></div>
    <div class="header-facts"><div><span>Formaat</span><strong>20 × 20</strong></div><div><span>Maximaal</span><strong>5 MB</strong></div></div>
</section>

<section class="content-section import-section">
    @if($errors->any())<div class="validation"><strong>De screenshot kon niet worden verwerkt.</strong> {{ $errors->first() }}</div>@endif
    <form class="import-upload" method="post" action="{{ route('glades.import.preview') }}" enctype="multipart/form-data">
        @csrf
        <label class="import-dropzone" for="screenshot">
            <span class="import-icon">▧</span>
            <strong>Kies een screenshot</strong>
            <small>PNG, JPG of WebP · volledige kaart · maximaal 5 MB</small>
            <input id="screenshot" name="screenshot" type="file" accept="image/png,image/jpeg,image/webp" required>
        </label>
        <div class="import-actions"><p>De afbeelding wordt alleen gebruikt om de kaart te herkennen en wordt niet permanent opgeslagen.</p><button class="button primary" type="submit">Screenshot herkennen →</button></div>
    </form>
</section>
@endsection
