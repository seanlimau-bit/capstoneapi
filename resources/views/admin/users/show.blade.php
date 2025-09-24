@extends('layouts.admin')

@section('title', 'User Details - ' . ($user->name ?? $user->email))

@section('content')
@php
    // Helper function for safe date formatting
    function formatDate($date, $format = 'M d, Y H:i') {
        if (!$date) return 'N/A';
        try {
            if (is_string($date)) {
                return \Carbon\Carbon::parse($date)->format($format);
            }
            return $date->format($format);
        } catch (\Exception $e) {
            return 'Invalid Date';
        }
    }
    use Illuminate\Support\Str;
@endphp

<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $user->name ?? 'User Profile' }}</h1>
            <p class="text-muted mb-0">User Profile & Management</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
            <button class="btn btn-danger">Action</button>
            <button class="btn btn-danger">Action</button>
            <button class="btn btn-danger">Action</button>
            <button class="btn btn-danger">Action</button>
        </div>
    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Metric Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="metric-card bg-danger text-white">
                <div class="metric-value">{{ $user->maxile_level ?? 0 }}</div>
                <div class="metric-label">Maxile Level</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card bg-success text-white">
                <div class="metric-value">{{ $user->game_level ?? 0 }}</div>
                <div class="metric-label">Game Level</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card bg-danger text-white">
                <div class="metric-value">{{ $stats['tests_completed'] ?? 0 }}</div>
                <div class="metric-label">Tests Completed</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card bg-primary text-white">
                <div class="metric-value">{{ $stats['quizzes_completed'] ?? 0 }}</div>
                <div class="metric-label">Quizzes Completed</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Profile Information --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Profile Information</h5>
                    <button class="btn btn-link text-primary p-0" onclick="toggleEdit()">
                        <span id="editText">Click to edit inline</span>
                    </button>
                </div>
                <div class="card-body">
                    <form id="userForm" action="{{ route('admin.users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="profile-field">
                                    <label class="field-label">Full Name</label>
                                    <input type="text" name="name" class="field-value editable" value="{{ $user->name }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-field">
                                    <label class="field-label">Email</label>
                                    <input type="email" name="email" class="field-value" value="{{ $user->email }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-field">
                                    <label class="field-label">First Name</label>
                                    <input type="text" name="firstname" class="field-value editable" value="{{ $user->firstname }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-field">
                                    <label class="field-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="field-value editable" 
                                           value="{{ $user->date_of_birth ? formatDate($user->date_of_birth, 'Y-m-d') : '' }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-field">
                                    <label class="field-label">Last Name</label>
                                    <input type="text" name="lastname" class="field-value editable" value="{{ $user->lastname }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-field">
                                    <label class="field-label">Account Status</label>
                                    <select name="status" class="field-value editable" disabled>
                                        <option value="active" {{ $user->status == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $user->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        <option value="suspended" {{ $user->status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    </select>
                                    <div class="mt-1">
                                        @if($user->status == 'active')
                                            <span class="badge bg-success">Active</span>
                                        @elseif($user->status == 'inactive')
                                            <span class="badge bg-secondary">Inactive</span>
                                        @else
                                            <span class="badge bg-danger">{{ ucfirst($user->status) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-field">
                                    <label class="field-label">Phone</label>
                                    <input type="text" name="phone_number" class="field-value editable" 
                                           value="{{ $user->phone_number ?? 'Not set' }}" placeholder="+65 8123 4567" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="profile-field">
                                    <label class="field-label">Role</label>
                                    <select name="role_id" class="field-value editable" disabled>
                                        <option value="">No Role</option>
                                        @foreach(\App\Models\Role::orderBy('role')->get() as $role)
                                            <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                                {{ $role->role }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="mt-1">
                                        @if($user->role)
                                            <span class="badge bg-warning text-dark">{{ $user->role->role }}</span>
                                        @else
                                            <span class="badge bg-secondary">No Role</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Save Button --}}
                        <div class="mt-4 text-end" id="saveButtons" style="display:none;">
                            <button type="button" class="btn btn-secondary me-2" onclick="cancelEdit()">Cancel</button>
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-warning d-flex align-items-center">
                            <i class="fas fa-sync-alt me-2"></i>Reset Progress
                        </button>
                        <button class="btn btn-outline-info d-flex align-items-center">
                            <i class="fas fa-stethoscope me-2"></i>Toggle Diagnostic
                        </button>
                        <button class="btn btn-outline-primary d-flex align-items-center">
                            <i class="fas fa-user-shield me-2"></i>Toggle Admin
                        </button>
                        <button class="btn btn-outline-success d-flex align-items-center">
                            <i class="fas fa-envelope-check me-2"></i>Verify Email
                        </button>
                        <button class="btn btn-outline-secondary d-flex align-items-center">
                            <i class="fas fa-chart-line me-2"></i>View Performance
                        </button>
                    </div>
                </div>
            </div>

            {{-- Additional Stats --}}
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-bottom-0">
                    <h5 class="mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="stat-item">
                                <div class="stat-number">{{ $stats['total_questions_answered'] ?? 0 }}</div>
                                <div class="stat-label">Questions</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                <div class="stat-number">{{ $stats['correct_answers'] ?? 0 }}</div>
                                <div class="stat-label">Correct</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                <div class="stat-number">{{ $stats['tracks_passed'] ?? 0 }}</div>
                                <div class="stat-label">Tracks</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                <div class="stat-number">{{ number_format($stats['accuracy_percentage'] ?? 0, 1) }}%</div>
                                <div class="stat-label">Accuracy</div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="small text-muted">
                        <div class="d-flex justify-content-between">
                            <span>Joined:</span>
                            <span>{{ formatDate($user->created_at, 'M d, Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Last Updated:</span>
                            <span>{{ formatDate($user->updated_at, 'M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Tables Section --}}
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-bottom-0">
            <ul class="nav nav-pills" id="dataTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="tests-tab" data-bs-toggle="pill" data-bs-target="#tests">
                        Tests ({{ $user->tests->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="quizzes-tab" data-bs-toggle="pill" data-bs-target="#quizzes">
                        Quizzes ({{ $user->quizzes->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="questions-tab" data-bs-toggle="pill" data-bs-target="#questions">
                        Questions ({{ $user->myQuestions->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tracks-tab" data-bs-toggle="pill" data-bs-target="#tracks">
                        Tracks ({{ $user->testedTracks->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="skills-tab" data-bs-toggle="pill" data-bs-target="#skills">
                        Skills ({{ $user->skill_user->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="fields-tab" data-bs-toggle="pill" data-bs-target="#fields">
                        Fields ({{ $user->fields->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="logs-tab" data-bs-toggle="pill" data-bs-target="#logs">
                        Logs ({{ $user->logs->count() }})
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                {{-- Tests Tab --}}
                <div class="tab-pane fade show active" id="tests">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr><th>ID</th><th>Completed</th><th>Result</th><th>Attempts</th><th>Kudos</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                @forelse($user->tests->take(50) as $test)
                                <tr>
                                    <td>{{ $test->id }}</td>
                                    <td><span class="badge bg-{{ $test->pivot->test_completed ? 'success' : 'warning' }}">{{ $test->pivot->test_completed ? 'Yes' : 'No' }}</span></td>
                                    <td>{{ $test->pivot->result ?? 'N/A' }}</td>
                                    <td>{{ $test->pivot->attempts ?? 0 }}</td>
                                    <td>{{ $test->pivot->kudos ?? 0 }}</td>
                                    <td>{{ formatDate($test->pivot->completed_date, 'M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">No tests found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Quizzes Tab --}}
                <div class="tab-pane fade" id="quizzes">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr><th>ID</th><th>Completed</th><th>Result</th><th>Attempts</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                @forelse($user->quizzes->take(50) as $quiz)
                                <tr>
                                    <td>{{ $quiz->id }}</td>
                                    <td><span class="badge bg-{{ $quiz->pivot->quiz_completed ? 'success' : 'warning' }}">{{ $quiz->pivot->quiz_completed ? 'Yes' : 'No' }}</span></td>
                                    <td>{{ $quiz->pivot->result ?? 'N/A' }}</td>
                                    <td>{{ $quiz->pivot->attempts ?? 0 }}</td>
                                    <td>{{ formatDate($quiz->pivot->completed_date, 'M d, Y') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">No quizzes found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Questions Tab --}}
                <div class="tab-pane fade" id="questions">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr><th>ID</th><th>Answered</th><th>Correct</th><th>Attempts</th><th>Test ID</th><th>Quiz ID</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                @forelse($user->myQuestions->take(50) as $question)
                                <tr>
                                    <td>{{ $question->id }}</td>
                                    <td><span class="badge bg-{{ $question->pivot->question_answered ? 'success' : 'warning' }}">{{ $question->pivot->question_answered ? 'Yes' : 'No' }}</span></td>
                                    <td><span class="badge bg-{{ $question->pivot->correct ? 'success' : 'danger' }}">{{ $question->pivot->correct ? '✓' : '✗' }}</span></td>
                                    <td>{{ $question->pivot->attempts ?? 0 }}</td>
                                    <td>{{ $question->pivot->test_id ?? 'N/A' }}</td>
                                    <td>{{ $question->pivot->quiz_id ?? 'N/A' }}</td>
                                    <td>{{ formatDate($question->pivot->answered_date, 'M d') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted">No questions found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tracks Tab --}}
                <div class="tab-pane fade" id="tracks">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr><th>Track</th><th>Track Maxile</th><th>Passed</th><th>Doneness</th><th>Test Date</th></tr>
                            </thead>
                            <tbody>
                                @forelse($user->testedTracks as $track)
                                <tr>
                                    <td>{{ $track->track }}</td>
                                    <td>{{ number_format($track->pivot->track_maxile, 2) }}</td>
                                    <td>
                                        @if($track->pivot->track_passed)
                                            <span class="badge bg-success">Passed</span>
                                        @else
                                            <span class="badge bg-danger">Failed</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($track->pivot->doneNess, 2) }}%</td>
                                    <td>{{ formatDate($track->pivot->track_test_date, 'M d, Y H:i') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted">No track attempts found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Skills Tab --}}
                <div class="tab-pane fade" id="skills">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Skill</th>
                                    <th>Skill Maxile</th>
                                    <th>Passed</th>
                                    <th>Difficulty</th>
                                    <th>Tries</th>
                                    <th>Correct Streak</th>
                                    <th>Total Correct</th>
                                    <th>Total Incorrect</th>
                                    <th>Fail Streak</th>
                                    <th>Test Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($user->skill_user->take(50) as $skill)
                                <tr>
                                    <td>{{ $skill->skill }}</td>
                                    <td>{{ number_format($skill->pivot->skill_maxile, 2) }}</td>
                                    <td>
                                        @if($skill->pivot->skill_passed)
                                            <span class="badge bg-success">Passed</span>
                                        @else
                                            <span class="badge bg-danger">Failed</span>
                                        @endif
                                    </td>
                                    <td>{{ $skill->pivot->difficulty_passed }}</td>
                                    <td>{{ $skill->pivot->noOfTries }}</td>
                                    <td>{{ $skill->pivot->correct_streak }}</td>
                                    <td>{{ $skill->pivot->total_correct_attempts }}</td>
                                    <td>{{ $skill->pivot->total_incorrect_attempts }}</td>
                                    <td>{{ $skill->pivot->fail_streak }}</td>
                                    <td>{{ formatDate($skill->pivot->skill_test_date, 'M d, Y H:i') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="10" class="text-center text-muted">No skill attempts found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($user->skill_user->count() > 50)
                            <p class="text-muted text-center small mt-2 mb-0">Showing first 50 of {{ $user->skill_user->count() }} skill attempts</p>
                        @endif
                    </div>
                </div>

                {{-- Fields Tab --}}
                <div class="tab-pane fade" id="fields">
                    @if($user->fields->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr><th>Field</th><th>Field Maxile</th><th>Month Achieved</th><th>Test Date</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($user->fields as $field)
                                    <tr>
                                        <td>{{ $field->field }}</td>
                                        <td>{{ number_format($field->pivot->field_maxile, 2) }}</td>
                                        <td>{{ $field->pivot->month_achieved }}</td>
                                        <td>{{ formatDate($field->pivot->field_test_date, 'M d, Y H:i') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-2"></i>No field attempts found
                        </div>
                    @endif
                </div>

                {{-- Logs Tab --}}
                <div class="tab-pane fade" id="logs">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr><th>Date</th><th>Action</th><th>Details</th></tr>
                            </thead>
                            <tbody>
                                @forelse($user->logs->take(50) as $log)
                                <tr>
                                    <td>{{ formatDate($log->created_at, 'M d H:i') }}</td>
                                    <td>{{ $log->action ?? 'Unknown' }}</td>
                                    <td>{{ $log->details ?? 'No details' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">No logs found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Metric Cards */
.metric-card {
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.metric-value {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.metric-label {
    font-size: 1rem;
    opacity: 0.9;
    font-weight: 500;
}

/* Profile Fields */
.profile-field {
    margin-bottom: 1.5rem;
}

.field-label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.field-value {
    border: none;
    background: transparent;
    padding: 0;
    font-size: 1rem;
    color: #111827;
    width: 100%;
}

.field-value:focus {
    outline: none;
    border-bottom: 2px solid #3B82F6;
    background: #F9FAFB;
    padding: 0.25rem;
}

.field-value[readonly] {
    border-bottom: 1px solid transparent;
}

.field-value[readonly]:hover {
    border-bottom: 1px solid #E5E7EB;
}

/* Stats */
.stat-item {
    padding: 1rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
}

.stat-label {
    font-size: 0.75rem;
    color: #6B7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 0.25rem;
}

/* Cards */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Pills Navigation */
.nav-pills .nav-link {
    border-radius: 20px;
    padding: 0.5rem 1rem;
    margin-right: 0.5rem;
    color: #6B7280;
    background: #F3F4F6;
    border: none;
}

.nav-pills .nav-link.active {
    background: #3B82F6;
    color: white;
}

/* Table */
.table th {
    border-top: none;
    border-bottom: 1px solid #E5E7EB;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.table-light {
    background-color: #F9FAFB;
}
</style>

<script>
let isEditMode = false;

function toggleEdit() {
    isEditMode = !isEditMode;
    const editText = document.getElementById('editText');
    const saveBtns = document.getElementById('saveButtons');
    
    document.querySelectorAll('.editable').forEach(el => {
        if (isEditMode) {
            if (el.hasAttribute('readonly')) el.removeAttribute('readonly');
            if (el.hasAttribute('disabled')) el.removeAttribute('disabled');
        } else {
            if (el.tagName === 'SELECT') {
                el.setAttribute('disabled', 'disabled');
            } else {
                el.setAttribute('readonly', 'readonly');
            }
        }
    });
    
    // Email always readonly
    document.querySelector('input[name="email"]').setAttribute('readonly', 'readonly');
    
    editText.textContent = isEditMode ? 'Cancel editing' : 'Click to edit inline';
    saveBtns.style.display = isEditMode ? 'block' : 'none';
}

function cancelEdit() {
    window.location.reload();
}

// Form submission with phone validation
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('userForm').addEventListener('submit', function(e) {
        const phone = document.querySelector('input[name="phone_number"]').value.trim();
        
        // Basic validation if phone is provided
        if (phone && phone !== 'Not set' && !phone.match(/^\+\d{1,4}\s?\d+/)) {
            e.preventDefault();
            alert('Please provide a valid phone number with country code (e.g., +65 8123 4567)');
            return;
        }
    });
});
</script>
@endsection