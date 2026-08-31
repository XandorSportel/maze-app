@props(['assignment'])

@php
    $defaults = config('glade.default_costs');
    $costs = array_replace($defaults, $assignment->costs);
    $changed = fn (string $name): bool => (int) $costs[$name] !== (int) $defaults[$name];
@endphp

<div class="cost-board">
    <div class="cost-column">
        <h3>Hardware <small>eenmalige aanschaf</small></h3>
        <dl>
            <div @class(['changed' => $changed('kompas')])><dt>kompas</dt><dd>€ {{ $costs['kompas'] }}</dd></div>
            <div @class(['changed' => $changed('zwOogHardware')])><dt>zwOog</dt><dd>€ {{ $costs['zwOogHardware'] }}</dd></div>
            <div @class(['changed' => $changed('kleurOogHardware')])><dt>kleurOog</dt><dd>€ {{ $costs['kleurOogHardware'] }}</dd></div>
            <div @class(['changed' => $changed('variabele')])><dt>variabele (a-z)</dt><dd>€ {{ $costs['variabele'] }}</dd></div>
        </dl>
    </div>
    <div class="cost-column">
        <h3>Verbruik <small>per uitvoering</small></h3>
        <dl>
            <div @class(['changed' => $changed('stapVooruit')])><dt>stapVooruit</dt><dd>€ {{ $costs['stapVooruit'] }}</dd></div>
            <div @class(['changed' => $changed('stapAchteruit')])><dt>stapAchteruit</dt><dd>€ {{ $costs['stapAchteruit'] }}</dd></div>
            <div @class(['changed' => $changed('draaiLinks')])><dt>draaiLinks</dt><dd>€ {{ $costs['draaiLinks'] }}</dd></div>
            <div @class(['changed' => $changed('draaiRechts')])><dt>draaiRechts</dt><dd>€ {{ $costs['draaiRechts'] }}</dd></div>
            <div @class(['changed' => $changed('zwOog')])><dt>zwOog</dt><dd>€ {{ $costs['zwOog'] }}</dd></div>
            <div @class(['changed' => $changed('kleurOog')])><dt>kleurOog</dt><dd>€ {{ $costs['kleurOog'] }}</dd></div>
            <div @class(['changed' => $changed('kompasVerbruik')])><dt>kompas</dt><dd>€ {{ $costs['kompasVerbruik'] }}</dd></div>
            <div @class(['changed' => $changed('duwen')])><dt>duw obstakel</dt><dd>€ {{ $costs['duwen'] }}</dd></div>
            <div @class(['changed' => $changed('toewijzing')])><dt>toewijzing uitvoeren</dt><dd>€ {{ $costs['toewijzing'] }}</dd></div>
            <div @class(['changed' => $changed('operatie')])><dt>operatie</dt><dd>€ {{ $costs['operatie'] }}</dd></div>
            <div @class(['changed' => $changed('vergelijking')])><dt>vergelijking</dt><dd>€ {{ $costs['vergelijking'] }}</dd></div>
        </dl>
    </div>
    <div class="cost-column">
        <h3>Software <small>per geschreven statement</small></h3>
        <dl>
            <div @class(['changed' => $changed('zolang')])><dt>zolang (lus)</dt><dd>€ {{ $costs['zolang'] }}</dd></div>
            <div @class(['changed' => $changed('als')])><dt>als (keuze)</dt><dd>€ {{ $costs['als'] }}</dd></div>
            <div @class(['changed' => $changed('opdracht')])><dt>opdracht</dt><dd>€ {{ $costs['opdracht'] }}</dd></div>
            <div @class(['changed' => $changed('toekenning')])><dt>toekenning</dt><dd>€ {{ $costs['toekenning'] }}</dd></div>
            <div @class(['changed' => $changed('operatie')])><dt>operatie</dt><dd>€ {{ $costs['operatie'] }}</dd></div>
        </dl>
    </div>
    <div class="start-capital"><span>Startkapitaal</span><strong>€ {{ number_format($assignment->start_capital, 0, ',', '.') }}</strong></div>
</div>
