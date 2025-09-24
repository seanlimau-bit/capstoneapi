{{-- resources/views/admin/components/welcome-header.blade.php --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">{{ $greeting }}, {{ $userName }}!</h2>
                        <p class="text-muted mb-0">{{ $subtitle }}</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">{{ $date }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>