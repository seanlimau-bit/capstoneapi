{{-- resources/views/admin/users/partials/skills-table.blade.php --}}
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Skill ID</th>
                <th>Skill Maxile</th>
                <th>Passed</th>
                <th>Difficulty Passed</th>
                <th>Tries</th>
                <th>Correct Streak</th>
                <th>Total Correct</th>
                <th>Total Incorrect</th>
                <th>Fail Streak</th>
                <th>Test Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($skills as $skill)
            <tr>
                <td>{{ $skill->id }}</td>
                <td>{{ $skill->pivot->skill_maxile ?? 'N/A' }}</td>
                <td>
                    <span class="badge bg-{{ $skill->pivot->skill_passed ? 'success' : 'danger' }}">
                        {{ $skill->pivot->skill_passed ? 'Passed' : 'Failed' }}
                    </span>
                </td>
                <td>{{ $skill->pivot->difficulty_passed ?? 0 }}</td>
                <td>{{ $skill->pivot->noOfTries ?? 0 }}</td>
                <td>{{ $skill->pivot->correct_streak ?? 0 }}</td>
                <td>{{ $skill->pivot->total_correct_attempts ?? 0 }}</td>
                <td>{{ $skill->pivot->total_incorrect_attempts ?? 0 }}</td>
                <td>{{ $skill->pivot->fail_streak ?? 0 }}</td>
                <td>{{ formatDate($skill->pivot->skill_test_date) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center text-muted">No skills found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>