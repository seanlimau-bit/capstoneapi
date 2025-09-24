{{-- resources/views/admin/users/partials/logs-table.blade.php --}}
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Date</th>
                <th>Action</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>{{ formatDate($log->created_at) }}</td>
                <td>{{ $log->action ?? 'Unknown' }}</td>
                <td>{{ $log->details ?? 'No details' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="text-center text-muted">No logs found</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>