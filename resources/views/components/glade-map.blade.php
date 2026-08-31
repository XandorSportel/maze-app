@props(['tiles', 'finalState' => null, 'editable' => false])

<div {{ $attributes->class(['glade-map', 'editable-map' => $editable]) }} data-map @if($editable) data-editable="true" @endif>
    @foreach ($tiles as $index => $tile)
        @php
            $kind = strtolower(substr($tile, 0, 1));
            $classes = [
                'tile',
                'tile-'.$kind,
                'color-'.($kind === 'c' ? substr($tile, 1) : match ($kind) { 'd', 'e' => 4, 'r' => 2, 's', 'b' => 0, default => 3 }),
                'final-robot' => $finalState && ($finalState['position'] ?? null) === $index,
            ];
        @endphp
        <button type="button" @class($classes) data-index="{{ $index }}" data-code="{{ $tile }}" title="{{ $tile }} · rij {{ intdiv($index, 20) + 1 }}, kolom {{ ($index % 20) + 1 }}" @disabled(!$editable)>
            <span>{{ substr($tile, 1) }}</span>
            @if ($finalState && ($finalState['position'] ?? null) === $index)
                <i class="robot" style="--direction: {{ ($finalState['direction'] ?? 0) * 90 }}deg">▲</i>
            @endif
        </button>
    @endforeach
</div>
