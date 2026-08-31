@props(['defaults'])

@php
    $groups = [
        'Hardware' => [
            'kompas' => 'kompas',
            'zwOogHardware' => 'zwOog',
            'kleurOogHardware' => 'kleurOog',
            'variabele' => 'variabele (a-z)',
        ],
        'Verbruik' => [
            'stapVooruit' => 'stapVooruit',
            'stapAchteruit' => 'stapAchteruit',
            'draaiLinks' => 'draaiLinks',
            'draaiRechts' => 'draaiRechts',
            'zwOog' => 'zwOog',
            'kleurOog' => 'kleurOog',
            'kompasVerbruik' => 'kompas',
            'duwen' => 'duw obstakel',
            'toewijzing' => 'toewijzing uitvoeren',
            'operatie' => 'operatie (+, -, *, %, /)',
            'vergelijking' => 'vergelijking',
        ],
        'Software' => [
            'zolang' => 'zolang (lus)',
            'als' => 'als (keuze)',
            'opdracht' => 'opdracht',
            'toekenning' => 'toekenning',
        ],
    ];
@endphp

<div class="editable-cost-board">
    @foreach($groups as $group => $items)
        <section class="editable-cost-column">
            <h3>{{ $group }} <small>{{ $group === 'Hardware' ? 'eenmalige aanschaf' : ($group === 'Software' ? 'per geschreven statement' : 'per uitvoering') }}</small></h3>
            @foreach($items as $key => $label)
                @php($value = old("costs.{$key}", $defaults[$key]))
                <label class="cost-input-row {{ (int) $value !== (int) $defaults[$key] ? 'changed' : '' }}">
                    <span>{{ $label }}</span>
                    <span class="cost-input"><b>€</b><input type="number" min="0" max="1000000" name="costs[{{ $key }}]" value="{{ $value }}" data-default-cost="{{ $defaults[$key] }}" required></span>
                </label>
            @endforeach
        </section>
    @endforeach
</div>
