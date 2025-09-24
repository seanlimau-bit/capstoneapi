{{-- resources/views/admin/users/partials/quizzes-table.blade.php --}}
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Quiz ID</th>
                <th>Completed</th>
                <th>Result</th>
                <th>Attempts</th>
                <th>Completed Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quizzes as $quiz)
            <tr>
                <td>{{ $quiz->id }}</td>
                <td>
                    <span class="badge bg-{{ $quiz->pivot->quiz_completed ? 'success' : 'warning' }}">
                        {{ $quiz->pivot->quiz_completed ? 'Yes' : 'No' }}
                    </span>
                </td>
                <td>{{ $quiz->pivot->result ?? 'N/A' }}</td>
                <td>{{ $quiz->pivot->attempts ?? 0 }}</td>
                <td>{{ formatDate($quiz->pivot->completed_date, 'M d, Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted">No quizzes found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>