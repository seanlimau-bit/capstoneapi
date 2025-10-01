{{-- resources/views/admin/components/stats-row.blade.php --}}
<div class="row mb-4">
    @foreach($stats as $stat)
        <div class="col-lg-{{ 12 / count($stats) }} col-md-6 mb-3">
            <div class="card bg-{{ $stat['color'] ?? 'primary' }} text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0" 
                            @if(isset($stat['id'])) id="{{ $stat['id'] }}" @endif
                            @if(isset($stat['data-stat'])) data-stat="{{ $stat['data-stat'] }}" @endif>
                            {{ $stat['value'] ?? $stat['count'] ?? 0 }}
                        </h4>
                        <small class="opacity-90">{{ $stat['label'] ?? $stat['title'] ?? 'Statistic' }}</small>
                        @if(isset($stat['subtitle']))
                            <div class="small opacity-75">{{ $stat['subtitle'] }}</div>
                        @endif
                    </div>
                    @if(isset($stat['icon']))
                        <i class="fas fa-{{ $stat['icon'] }} fa-2x opacity-75"></i>
                    @endif
                </div>
                @if(isset($stat['progress']))
                    <div class="card-footer bg-transparent border-0 pt-0">
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: {{ $stat['progress'] }}%"></div>
                        </div>
                        <small class="opacity-75">{{ $stat['progress'] }}% of target</small>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
@if(empty($stats))
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info text-center">
            <i class="fas fa-chart-bar fa-2x mb-2"></i>
            <p class="mb-0">No statistics available</p>
        </div>
    </div>
</div>
@endif