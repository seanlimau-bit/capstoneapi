@extends('layouts.admin')

@section('title', 'Learning OS Dashboard')

@push('styles')
<style>
.learning-neural-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    opacity: 0.08;
    pointer-events: none;
}

.learning-hero-header {
    background: var(--grad-primary);
    color: var(--on-primary);
    margin: calc(var(--spacing-2xl) * -1) calc(var(--spacing-2xl) * -1) var(--spacing-2xl);
    padding: var(--spacing-2xl) var(--spacing-2xl) var(--spacing-xl);
    border-radius: 0 0 var(--radius-xl) var(--radius-xl);
    position: relative;
    overflow: hidden;
}

.learning-hero-header::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: float 20s infinite ease-in-out;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    33% { transform: translate(30px, -30px) rotate(120deg); }
    66% { transform: translate(-20px, 20px) rotate(240deg); }
}

.hero-title {
    font-size: var(--font-size-3xl);
    font-weight: 700;
    margin: 0 0 var(--spacing-sm);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.hero-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    animation: pulse-glow 2s infinite;
}

@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 20px rgba(255, 255, 255, 0.3); transform: scale(1); }
    50% { box-shadow: 0 0 40px rgba(255, 255, 255, 0.5); transform: scale(1.05); }
}

.hero-subtitle {
    opacity: 0.95;
    font-size: var(--font-size-lg);
    margin-bottom: var(--spacing-lg);
}

.system-status {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm) var(--spacing-lg);
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 50px;
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.status-pulse {
    width: 12px;
    height: 12px;
    background: var(--success-color);
    border-radius: 50%;
    animation: pulse-dot 2s infinite;
    box-shadow: 0 0 10px var(--success-color);
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(0.9); }
}

.hierarchy-card {
    background: var(--surface-color);
    border-radius: var(--radius-lg);
    overflow: hidden;
    border: 1px solid var(--surface-container-low);
    position: relative;
}

.hierarchy-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--grad-primary);
}

.hierarchy-level {
    padding: var(--spacing-xl);
    border-bottom: 2px dashed var(--surface-container-low);
    position: relative;
    transition: background var(--t);
}

.hierarchy-level:last-child {
    border-bottom: none;
}

.hierarchy-level:hover {
    background: var(--surface-container);
}

.hierarchy-level::before {
    content: '';
    position: absolute;
    left: var(--spacing-lg);
    top: 50%;
    width: 12px;
    height: 12px;
    background: var(--primary-color);
    border-radius: 50%;
    transform: translateY(-50%);
    box-shadow: 0 0 0 4px var(--surface-color);
}

.hierarchy-content {
    margin-left: var(--spacing-2xl);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.hierarchy-label {
    font-weight: 600;
    color: var(--on-surface);
    font-size: var(--font-size-lg);
    margin-bottom: 4px;
}

.hierarchy-count {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    background: var(--grad-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.metric-card-advanced {
    position: relative;
    background: var(--surface-color);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: all var(--t);
    border: 1px solid var(--surface-container-low);
}

.metric-card-advanced::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--primary-color));
    background-size: 200% 100%;
    animation: shimmer 3s linear infinite;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

.metric-card-advanced:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-light);
}

.metric-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-lg);
}

.metric-icon-wrapper {
    width: 56px;
    height: 56px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.metric-value-large {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
    background: var(--grad-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: var(--spacing-xs);
}

.metric-label {
    font-size: var(--font-size-sm);
    font-weight: 600;
    color: var(--on-surface-variant);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: var(--spacing-md);
}

.metric-change {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: var(--font-size-xs);
    font-weight: 600;
}

.change-positive {
    background: rgba(80, 210, 0, 0.1);
    color: var(--success-dark);
}

.change-negative {
    background: rgba(216, 0, 0, 0.1);
    color: var(--error-dark);
}

.metric-progress {
    height: 8px;
    background: var(--surface-container-low);
    border-radius: var(--radius-sm);
    overflow: hidden;
    margin-top: var(--spacing-md);
}

.progress-fill {
    height: 100%;
    background: var(--grad-primary);
    border-radius: var(--radius-sm);
    transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.quick-action-card {
    background: var(--surface-color);
    border: 2px solid var(--surface-container-low);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    text-align: center;
    transition: all var(--t);
    cursor: pointer;
    text-decoration: none;
    color: var(--on-surface);
    display: block;
    position: relative;
    overflow: hidden;
}

.quick-action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--grad-primary);
    transform: scaleX(0);
    transition: transform var(--t);
}

.quick-action-card:hover::before {
    transform: scaleX(1);
}

.quick-action-card:hover {
    transform: translateY(-4px);
    border-color: var(--primary-color);
    box-shadow: var(--shadow-md);
    color: var(--on-surface);
}

.action-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto var(--spacing-md);
    background: var(--grad-primary);
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: white;
    transition: transform var(--t);
}

.quick-action-card:hover .action-icon {
    transform: scale(1.1) rotate(5deg);
}

.action-title {
    font-weight: 600;
    font-size: var(--font-size);
    margin-bottom: var(--spacing-xs);
}

.action-count {
    font-size: var(--font-size-xl);
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: var(--spacing-xs);
}

.action-description {
    font-size: var(--font-size-sm);
    color: var(--on-surface-variant);
}

.insight-panel {
    background: linear-gradient(135deg, rgba(150, 0, 0, 0.03) 0%, rgba(255, 191, 102, 0.03) 100%);
    border: 2px solid var(--surface-container-low);
    border-left-width: 4px;
    border-left-color: var(--primary-color);
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-md);
    transition: all var(--t);
}

.insight-panel:hover {
    border-color: var(--primary-light);
    border-left-color: var(--primary-color);
    box-shadow: var(--shadow);
    transform: translateX(4px);
}

.insight-header {
    display: flex;
    gap: var(--spacing-md);
}

.insight-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius);
    background: var(--grad-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.insight-content h4 {
    font-weight: 600;
    margin-bottom: var(--spacing-xs);
}

.insight-content p {
    color: var(--on-surface-variant);
    font-size: var(--font-size-sm);
    line-height: 1.6;
    margin: 0;
}

.activity-timeline {
    position: relative;
    padding-left: var(--spacing-xl);
}

.activity-timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
}

.timeline-item {
    position: relative;
    padding-bottom: var(--spacing-xl);
}

.timeline-dot {
    position: absolute;
    left: -29px;
    top: 4px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--surface-color);
    border: 3px solid var(--primary-color);
    box-shadow: 0 0 0 4px var(--surface-color);
    z-index: 1;
}

.timeline-content {
    background: var(--surface-container);
    padding: var(--spacing-md) var(--spacing-lg);
    border-radius: var(--radius);
    border-left: 3px solid var(--primary-color);
}

.timeline-title {
    font-weight: 600;
    margin-bottom: 4px;
}

.timeline-description {
    color: var(--on-surface-variant);
    font-size: var(--font-size-sm);
    margin-bottom: 4px;
}

.timeline-time {
    color: var(--on-surface-variant);
    font-size: var(--font-size-xs);
}

.chart-card {
    background: var(--surface-color);
    border-radius: var(--radius-lg);
    border: 1px solid var(--surface-container-low);
    overflow: hidden;
}

.chart-header {
    padding: var(--spacing-xl);
    background: var(--surface-container);
    border-bottom: 2px solid var(--surface-container-low);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-title {
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.chart-body {
    padding: var(--spacing-xl);
}

.chart-container {
    height: 300px;
    position: relative;
}

@media (max-width: 768px) {
    .hero-title {
        font-size: var(--font-size-2xl);
    }
    
    .metric-value-large {
        font-size: 2rem;
    }
}
</style>
@endpush

@section('content')
<canvas class="learning-neural-bg" id="neuralBg"></canvas>

<div class="learning-hero-header">
    <h1 class="hero-title">
        <span class="hero-icon">🎓</span>
        All Gifted Learning OS Command Center
    </h1>
    <p class="hero-subtitle">
        Welcome back, {{ auth()->user()->firstname ?? 'Admin' }} | Manage your intelligent mathematics learning platform
    </p>
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="system-status">
            <span class="status-pulse"></span>
            All Systems Operational
        </div>
        <div class="system-status">
            <i class="fas fa-clock"></i>
            {{ now()->format('l, F j, Y • g:i A') }}
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6 mb-3">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-sitemap me-2"></i>Content Hierarchy</h3>
            </div>
            <div class="card-body p-0">
                <div class="hierarchy-card">
                    <div class="hierarchy-level">
                        <div class="hierarchy-content">
                            <div>
                                <div class="hierarchy-label">Fields</div>
                                <small class="text-muted">Top-level subject areas</small>
                            </div>
                            <div class="text-end">
                                <div class="hierarchy-count">{{ $counts['fields'] }}</div>
                                <div class="mt-2">
                                    <a href="{{ route('admin.fields.index') }}" class="btn btn-sm btn-primary">Manage</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hierarchy-level">
                        <div class="hierarchy-content">
                            <div>
                                <div class="hierarchy-label">Tracks</div>
                                <small class="text-muted">Learning pathways within fields</small>
                            </div>
                            <div class="text-end">
                                <div class="hierarchy-count">{{ $counts['tracks'] }}</div>
                                <div class="mt-2">
                                    <a href="{{ route('admin.tracks.index') }}" class="btn btn-sm btn-primary">Manage</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hierarchy-level">
                        <div class="hierarchy-content">
                            <div>
                                <div class="hierarchy-label">Skills</div>
                                <small class="text-muted">Specific competencies to master</small>
                            </div>
                            <div class="text-end">
                                <div class="hierarchy-count">{{ $counts['skills'] }}</div>
                                <div class="mt-2">
                                    <a href="{{ route('admin.skills.index') }}" class="btn btn-sm btn-primary">Manage</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="hierarchy-level">
                        <div class="hierarchy-content">
                            <div>
                                <div class="hierarchy-label">Questions</div>
                                <small class="text-muted">Practice problems & assessments</small>
                            </div>
                            <div class="text-end">
                                <div class="hierarchy-count">{{ number_format($counts['questions']) }}</div>
                                <div class="mt-2">
                                    <a href="{{ route('admin.questions.index') }}" class="btn btn-sm btn-primary">Manage</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card metric-card-advanced">
                    <div class="card-body">
                        <div class="metric-header">
                            <div class="metric-icon-wrapper" style="background: rgba(80, 210, 0, 0.1);">
                                <i class="fas fa-users" style="color: var(--success-color);"></i>
                            </div>
                            @if($metrics['user_growth_rate'] > 0)
                            <div class="metric-change change-positive">
                                <i class="fas fa-arrow-up"></i> {{ number_format($metrics['user_growth_rate'], 1) }}%
                            </div>
                            @endif
                        </div>
                        <div class="metric-value-large">{{ number_format($counts['active_users']) }}</div>
                        <div class="metric-label">Active Learners</div>
                        <div class="metric-progress">
                            <div class="progress-fill" style="width: {{ min(100, ($counts['active_users'] / max($counts['total_users'], 1)) * 100) }}%; background: var(--grad-success);"></div>
                        </div>
                        <small class="text-muted mt-2 d-block">{{ $counts['total_users'] }} total users</small>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card metric-card-advanced">
                    <div class="card-body">
                        <div class="metric-header">
                            <div class="metric-icon-wrapper" style="background: rgba(255, 149, 0, 0.1);">
                                <i class="fas fa-clipboard-check" style="color: var(--warning-color);"></i>
                            </div>
                            <div class="metric-change change-negative">
                                <i class="fas fa-exclamation"></i> {{ $counts['pending_qa'] }}
                            </div>
                        </div>
                        <div class="metric-value-large">{{ number_format($counts['pending_qa']) }}</div>
                        <div class="metric-label">Needs QA Review</div>
                        <div class="metric-progress">
                            <div class="progress-fill" style="width: {{ min(100, ($counts['pending_qa'] / max($counts['questions'], 1)) * 100) }}%; background: var(--grad-warning);"></div>
                        </div>
                        <small class="text-muted mt-2 d-block">{{ $counts['flagged'] }} flagged items</small>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card metric-card-advanced">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="metric-icon-wrapper" style="background: rgba(33, 150, 243, 0.1);">
                                <i class="fas fa-chart-line" style="color: var(--info-color);"></i>
                            </div>
                            <div class="text-end">
                                <div class="metric-value-large" style="font-size: 2rem;">{{ number_format($metrics['qa_approval_rate'], 1) }}%</div>
                                <div class="metric-label mb-0">QA Approval Rate</div>
                            </div>
                        </div>
                        <div class="metric-progress">
                            <div class="progress-fill" style="width: {{ $metrics['qa_approval_rate'] }}%; background: var(--grad-info);"></div>
                        </div>
                        <small class="text-muted mt-2 d-block">{{ number_format($counts['approved']) }} approved questions</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="{{ route('admin.fields.index') }}" class="quick-action-card">
                    <div class="action-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="action-count">{{ $counts['fields'] }}</div>
                    <div class="action-title">Fields</div>
                    <div class="action-description">Subject areas</div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="{{ route('admin.tracks.index') }}" class="quick-action-card">
                    <div class="action-icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <div class="action-count">{{ $counts['tracks'] }}</div>
                    <div class="action-title">Tracks</div>
                    <div class="action-description">Learning paths</div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="{{ route('admin.skills.index') }}" class="quick-action-card">
                    <div class="action-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div class="action-count">{{ $counts['skills'] }}</div>
                    <div class="action-title">Skills</div>
                    <div class="action-description">Competencies</div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="{{ route('admin.questions.index') }}" class="quick-action-card">
                    <div class="action-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="action-count">{{ number_format($counts['questions']) }}</div>
                    <div class="action-title">Questions</div>
                    <div class="action-description">Practice items</div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="{{ route('admin.qa.index') }}" class="quick-action-card">
                    <div class="action-icon" style="background: var(--grad-warning);">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="action-count">{{ $counts['pending_qa'] }}</div>
                    <div class="action-title">QA Review</div>
                    <div class="action-description">Needs attention</div>
                </a>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <a href="{{ route('admin.users.index') }}" class="quick-action-card">
                    <div class="action-icon" style="background: var(--grad-success);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="action-count">{{ number_format($counts['total_users']) }}</div>
                    <div class="action-title">Users</div>
                    <div class="action-description">All learners</div>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6 mb-3">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Platform Insights</h3>
            </div>
            <div class="card-body">
                @if($insights['top_track'])
                <div class="insight-panel">
                    <div class="insight-header">
                        <div class="insight-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="insight-content">
                            <h4>{{ $insights['top_track']['name'] }} Track Excelling</h4>
                            <p>{{ $insights['top_track']['question_count'] }} questions with {{ number_format($insights['top_track']['approval_rate'], 1) }}% QA approval rate. This track can serve as a quality benchmark.</p>
                        </div>
                    </div>
                </div>
                @endif

                @if($insights['needs_attention'])
                <div class="insight-panel">
                    <div class="insight-header">
                        <div class="insight-icon" style="background: var(--grad-warning);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="insight-content">
                            <h4>QA Review Backlog</h4>
                            <p>{{ $counts['pending_qa'] }} questions pending review. Consider allocating more QA resources to reduce wait times.</p>
                        </div>
                    </div>
                </div>
                @endif

                @if($metrics['content_growth_rate'] > 0)
                <div class="insight-panel">
                    <div class="insight-header">
                        <div class="insight-icon" style="background: var(--grad-info);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="insight-content">
                            <h4>Content Growth Trending</h4>
                            <p>{{ $metrics['questions_last_30days'] }} questions created in the last 30 days, representing {{ number_format($metrics['content_growth_rate'], 1) }}% growth.</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h3>
            </div>
            <div class="card-body">
                <div class="activity-timeline">
                    @forelse($recent_questions as $question)
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-title">Question {{ $question['qa_status'] === 'approved' ? 'Approved' : 'Created' }}</div>
                            <div class="timeline-description">{{ $question['question'] }}</div>
                            <div class="timeline-time">
                                <i class="fas fa-clock me-1"></i>{{ $question['updated_at']->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No recent activity</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-8 mb-3">
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Content Creation Trends
                </h3>
                <span class="text-muted">Last 14 days</span>
            </div>
            <div class="chart-body">
                <div class="chart-container">
                    <canvas id="contentChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-3">
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Top Fields
                </h3>
            </div>
            <div class="chart-body">
                <div class="chart-container">
                    <canvas id="fieldChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="mb-0"><i class="fas fa-tasks me-2"></i>Quality Assurance Status</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card" style="border-left: 4px solid var(--warning-color);">
                    <div class="card-body">
                        <h4 class="text-warning mb-2">{{ number_format($counts['unreviewed']) }}</h4>
                        <p class="mb-0 text-muted">Unreviewed</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card" style="border-left: 4px solid var(--success-color);">
                    <div class="card-body">
                        <h4 class="text-success mb-2">{{ number_format($counts['approved']) }}</h4>
                        <p class="mb-0 text-muted">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card" style="border-left: 4px solid var(--error-color);">
                    <div class="card-body">
                        <h4 class="text-danger mb-2">{{ number_format($counts['flagged']) }}</h4>
                        <p class="mb-0 text-muted">Flagged</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card" style="border-left: 4px solid var(--info-color);">
                    <div class="card-body">
                        <h4 class="text-info mb-2">{{ number_format($counts['needs_revision']) }}</h4>
                        <p class="mb-0 text-muted">Needs Revision</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
const chartData = @json($chart_data);
const fieldDistribution = @json($field_distribution);

const canvas = document.getElementById('neuralBg');
const ctx = canvas.getContext('2d');
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

class Node {
    constructor() {
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.vx = (Math.random() - 0.5) * 0.25;
        this.vy = (Math.random() - 0.5) * 0.25;
    }
    update() {
        this.x += this.vx;
        this.y += this.vy;
        if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
        if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
    }
    draw() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, 2, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(150, 0, 0, 0.5)';
        ctx.fill();
    }
}

const nodes = Array.from({ length: 35 }, () => new Node());

function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    nodes.forEach(node => { node.update(); node.draw(); });
    for (let i = 0; i < nodes.length; i++) {
        for (let j = i + 1; j < nodes.length; j++) {
            const dx = nodes[i].x - nodes[j].x;
            const dy = nodes[i].y - nodes[j].y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            if (distance < 140) {
                ctx.beginPath();
                ctx.moveTo(nodes[i].x, nodes[i].y);
                ctx.lineTo(nodes[j].x, nodes[j].y);
                ctx.strokeStyle = `rgba(150, 0, 0, ${0.12 * (1 - distance / 140)})`;
                ctx.lineWidth = 1;
                ctx.stroke();
            }
        }
    }
    requestAnimationFrame(animate);
}
animate();
window.addEventListener('resize', () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
});

new Chart(document.getElementById('contentChart'), {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [{
            label: 'Questions Created',
            data: chartData.created,
            borderColor: '#960000',
            backgroundColor: 'rgba(150, 0, 0, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Questions Approved',
            data: chartData.approved,
            borderColor: '#50d200',
            backgroundColor: 'rgba(80, 210, 0, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: '#212529' } } },
        scales: {
            y: { beginAtZero: true, ticks: { color: '#6c757d' }, grid: { color: 'rgba(108, 117, 125, 0.1)' } },
            x: { ticks: { color: '#6c757d' }, grid: { color: 'rgba(108, 117, 125, 0.1)' } }
        }
    }
});

new Chart(document.getElementById('fieldChart'), {
    type: 'doughnut',
    data: {
        labels: fieldDistribution.labels,
        datasets: [{
            data: fieldDistribution.data,
            backgroundColor: [
                'rgba(150, 0, 0, 0.85)',
                'rgba(255, 191, 102, 0.85)',
                'rgba(80, 210, 0, 0.85)',
                'rgba(255, 149, 0, 0.85)',
                'rgba(33, 150, 243, 0.85)',
                'rgba(156, 39, 176, 0.85)'
            ],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { color: '#212529', padding: 10, font: { size: 11 } } } }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.progress-fill').forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => { bar.style.width = width; }, 100);
    });
});
</script>
@endpush