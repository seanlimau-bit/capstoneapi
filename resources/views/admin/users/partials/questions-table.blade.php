{{-- resources/views/admin/users/partials/questions-table.blade.php --}}
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Question ID</th>
                <th>Answered</th>
                <th>Correct</th>
                <th>Attempts</th>
                <th>Kudos</th>
                <th>Test ID</th>
                <th>Quiz ID</th>
                <th>Assessment Type</th>
                <th>Answered Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($questions as $question)
            <tr>
                <td>{{ $question->id }}</td>
                <td>
                    <span class="badge bg-{{ $question->pivot->question_answered ? 'success' : 'warning' }}">
                        {{ $question->pivot->question_answered ? 'Yes' : 'No' }}
                    </span>
                </td>
                <td>
                    <span class="badge bg-{{ $question->pivot->correct ? 'success' : 'danger' }}">
                        {{ $question->pivot->correct ? '✓' : '✗' }}
                    </span>
                </td>
                <td>{{ $question->pivot->attempts ?? 0 }}</td>
                <td>{{ $question->pivot->kudos ?? 0 }}</td>
                <td>{{ $question->pivot->test_id ?? 'N/A' }}</td>
                <td>{{ $question->pivot->quiz_id ?? 'N/A' }}</td>
                <td>{{ $question->pivot->assessment_type ?? 'N/A' }}</td>
                <td>{{ formatDate($question->pivot->answered_date) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center text-muted">No questions found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($questions->count() >= 100)
        <p class="text-muted text-center">Showing first 100 questions</p>
    @endif
</div>