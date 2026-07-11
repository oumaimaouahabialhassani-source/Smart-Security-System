{{-- Reusable bar chart: $data = [['label' => ..., 'count' => ...], …] --}}
@php($max = max(array_column($data, 'count')) ?: 1)
<div class="bar-chart bar-chart-short" role="img" aria-label="Bar chart">
    @foreach ($data as $point)
        <div class="bar-col" title="{{ $point['label'] }} — {{ $point['count'] }}">
            <div class="bar" style="height: {{ round($point['count'] / $max * 100) }}%">
                @if (count($data) <= 14)
                    <span class="bar-value">{{ $point['count'] }}</span>
                @endif
            </div>
            <span class="bar-label">{{ count($data) > 14 && ! str_contains($point['label'], ' ') && $loop->index % 2 === 1 ? '' : $point['label'] }}</span>
        </div>
    @endforeach
</div>
