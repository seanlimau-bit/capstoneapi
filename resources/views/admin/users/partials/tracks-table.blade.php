{{-- resources/views/admin/users/partials/tracks-table.blade.php --}}
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Track ID</th>
                <th>Track Maxile</th>
                <th>Passed</th>
                <th>Doneness</th>
                <th>Test Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tracks as $track)
            <tr>
                <td>{{ $track->id }}</td>
                <td>{{ $track->pivot->track_maxile ?? 'N/A' }}</td>
                <td>
                    <span class="badge bg-{{ $track->pivot->track_passed ? 'success' : 'danger' }}">
                        {{ $track->pivot->track_passed ? 'Passed' : 'Failed' }}
                    </span>
                </td>
                <td>{{ $track->pivot->doneNess ?? 'N/A' }}</td>
                <td>{{ formatDate($track->pivot->track_test_date) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted">No tracks found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>