{{-- resources/views/admin/components/system-status.blade.php --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ $title ?? 'System Status' }}</h5>
    </div>
    <div class="card-body">
        @foreach($statuses as $status)
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small">{{ $status['label'] }}</span>
                @if($status['type'] === 'badge')
                    <span class="badge bg-{{ $status['color'] }}">{{ $status['value'] }}</span>
                @else
                    <small class="text-muted">{{ $status['value'] }}</small>
                @endif
            </div>
        @endforeach
    </div>
</div>