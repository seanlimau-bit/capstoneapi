@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="dashboard=wrapper">
    <div class="container-fluid">
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-1">Welcome back, {{ auth()->user()->firstname ?? 'Admin' }}!</h2>
                                <p class="text-muted mb-0">Manage your math learning platform</p>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">{{ now()->format('l, F j, Y') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white hover-lift">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-question-circle fa-3x me-3"></i>
                            <div>
                                <h3 class="card-title mb-1">{{ number_format($stats['total_questions'] ?? 0) }}</h3>
                                <p class="card-text mb-0">Total Questions</p>
                                <small class="opacity-75">In Database</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white hover-lift">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-users fa-3x me-3"></i>
                            <div>
                                <h3 class="card-title mb-1">{{ number_format($stats['active_users'] ?? 0) }}</h3>
                                <p class="card-text mb-0">Active Users</p>
                                <small class="opacity-75">Currently Online</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white hover-lift">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clipboard-check fa-3x me-3"></i>
                            <div>
                                <h3 class="card-title mb-1">{{ number_format($stats['pending_qa'] ?? 0) }}</h3>
                                <p class="card-text mb-0">Pending QA</p>
                                <small class="opacity-75">Need Review</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white hover-lift">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-brain fa-3x me-3"></i>
                            <div>
                                <h3 class="card-title mb-1">{{ number_format($stats['total_skills'] ?? 0) }}</h3>
                                <p class="card-text mb-0">Skills</p>
                                <small class="opacity-75">Available</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management Grid -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-cog me-2"></i>System Management
                        </h3>
                    </div>
                    <div class="card-body">
                        @include('admin.components.management-grid', ['items' => $managementItems, 'columns' => 4])
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>Recent Activity
                        </h3>
                    </div>
                    <div class="card-body">
                        @if(isset($recent_activity) && count($recent_activity) > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recent_activity as $activity)
                                <div class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">{{ $activity['action'] }}</div>
                                        <small class="text-muted">{{ $activity['description'] }}</small>
                                    </div>
                                    <small class="text-muted">{{ $activity['time_ago'] }}</small>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No recent activity to display</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin Dashboard loaded');
});
</script>
@endpush