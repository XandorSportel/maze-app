@props(['assignment'])

@php($costs = $assignment->costs)
<div class="cost-board">
    <div class="cost-column">
        <h3>Hardware <small>eenmalige aanschaf</small></h3>
        <dl><div><dt>kompas</dt><dd>€ {{ $costs['kompas'] }}</dd></div><div><dt>zwOog</dt><dd>€ {{ $costs['zwOogHardware'] }}</dd></div><div class="changed"><dt>kleurOog</dt><dd>€ {{ $costs['kleurOogHardware'] }}</dd></div><div><dt>variabele (a–z)</dt><dd>€ {{ $costs['variabele'] }}</dd></div></dl>
    </div>
    <div class="cost-column">
        <h3>Verbruik <small>per uitvoering</small></h3>
        <dl><div><dt>stapVooruit</dt><dd>€ {{ $costs['stapVooruit'] }}</dd></div><div><dt>stapAchteruit</dt><dd>€ {{ $costs['stapAchteruit'] }}</dd></div><div><dt>draaien</dt><dd>€ {{ $costs['draaien'] }}</dd></div><div><dt>kleurOog</dt><dd>€ {{ $costs['kleurOog'] }}</dd></div><div><dt>vergelijking</dt><dd>€ {{ $costs['vergelijking'] }}</dd></div></dl>
    </div>
    <div class="cost-column">
        <h3>Software <small>per geschreven statement</small></h3>
        <dl><div><dt>zolang (lus)</dt><dd>€ {{ $costs['zolang'] }}</dd></div><div><dt>als (keuze)</dt><dd>€ {{ $costs['als'] }}</dd></div><div><dt>opdracht</dt><dd>€ {{ $costs['opdracht'] }}</dd></div><div><dt>toekenning</dt><dd>€ {{ $costs['toekenning'] }}</dd></div><div><dt>operatie</dt><dd>€ {{ $costs['operatie'] }}</dd></div></dl>
    </div>
    <div class="start-capital"><span>Startkapitaal</span><strong>€ {{ number_format($assignment->start_capital, 0, ',', '.') }}</strong></div>
</div>
