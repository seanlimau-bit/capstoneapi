<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Test ID</th>
                <th>Completed</th>
                <th>Result</th>
                <th>Attempts</th>
                <th>Kudos</th>
                <th>Completed Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tests as $test)
            <tr>
                <td>{{ $test->id }}</td>
                <td><span class="badge bg-{{ $test->pivot->test_completed ? 'success' : 'warning' }}">{{ $test->pivot->test_completed ? 'Yes' : 'No' }}</span></td>
                <td>{{ $test->pivot->result ?? 'N/A' }}</td>
                <td>{{ $test->pivot->attempts ?? 0 }}</td>
                <td>{{ $test->pivot->kudos ?? 0 }}</td>
                <td>{{ formatDate($test->pivot->completed_date, 'M d, Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center text-muted">No tests found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>