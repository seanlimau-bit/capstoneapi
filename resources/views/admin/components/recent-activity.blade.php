{{-- resources/views/admin/components/recent-activity.blade.php --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ $title ?? 'Recent Activity' }}</h5>
        @if($viewAllRoute ?? false)
            <a href="{{ $viewAllRoute }}" class="btn btn-sm btn-outline-secondary">View All</a>
        @endif
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        @foreach($columns as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            @foreach($activity as $key => $value)
                                <td>
                                    @if($key === 'type')
                                        <span class="badge bg-{{ $value === 'create' ? 'success' : ($value === 'update' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($value) }}
                                        </span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="text-center text-muted">
                                <i class="fas fa-clock me-1"></i>{{ $emptyMessage ?? 'No recent activity' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
