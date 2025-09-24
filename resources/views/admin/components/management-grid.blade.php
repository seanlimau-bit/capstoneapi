{{-- resources/views/admin/components/management-grid.blade.php --}}
<div class="row g-3">
    @foreach($items as $item)
    <div class="col-xl-{{ $columns ?? 4 }} col-lg-6 col-md-6 col-sm-12 mb-3">
        <div class="card h-100 hover-lift {{ $item['disabled'] ?? false ? 'opacity-75' : '' }}">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-{{ $item['icon'] }} fa-3x text-{{ $item['color'] }}"></i>
                </div>
                <h5 class="card-title">{{ $item['title'] }}</h5>
                <p class="card-text text-muted">{{ $item['description'] }}</p>
                
                @if(isset($item['stats']))
                <div class="d-flex justify-content-between mb-3">
                    <small class="text-muted">{{ $item['stats']['label'] }}: {{ $item['stats']['value'] }}</small>
                    <small class="text-{{ $item['status_color'] ?? 'success' }}">{{ $item['status'] ?? 'Active' }}</small>
                </div>
                @endif
                
                @if(isset($item['url']) && $item['url'])
                    <a href="{{ $item['url'] }}" 
                       class="btn btn-outline-{{ $item['disabled'] ?? false ? 'secondary' : 'primary' }} btn-sm {{ $item['disabled'] ?? false ? 'disabled' : '' }}"
                       @if($item['disabled'] ?? false) tabindex="-1" aria-disabled="true" @endif>
                        <i class="fas fa-{{ $item['action_icon'] ?? 'arrow-right' }} me-1"></i> 
                        {{ $item['action_text'] ?? 'Manage' }}
                    </a>
                @elseif(isset($item['onclick']))
                    <button class="btn btn-outline-primary btn-sm" onclick="{{ $item['onclick'] }}">
                        <i class="fas fa-{{ $item['action_icon'] ?? 'arrow-right' }} me-1"></i> 
                        {{ $item['action_text'] ?? 'Manage' }}
                    </button>
                @else
                    <button class="btn btn-outline-secondary btn-sm disabled" tabindex="-1" aria-disabled="true">
                        <i class="fas fa-{{ $item['action_icon'] ?? 'arrow-right' }} me-1"></i> 
                        {{ $item['action_text'] ?? 'Coming Soon' }}
                    </button>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

@if(empty($items))
<div class="text-center py-5">
    <i class="fas fa-grid fa-3x text-muted mb-3"></i>
    <h5 class="text-muted">No management sections configured</h5>
    <p class="text-muted">Configure management sections to display here</p>
</div>
@endif