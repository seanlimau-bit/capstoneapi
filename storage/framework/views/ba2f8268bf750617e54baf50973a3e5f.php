<?php use \Illuminate\Support\Str; ?>


<?php $__env->startSection('title', 'Field: ' . $field->field); ?>

<?php $__env->startSection('content'); ?>
<?php
$fieldId = $field->id;
$statusBadge = $field->status && $field->status->status === 'active' ? 'success' : 'warning';
$tracks = $field->tracks ?? collect();
?>

<div class="container-fluid">
    
    <?php echo $__env->make('admin.components.page-header', [
    'title' => $field->field,
    'subtitle' => 'Manage field details and tracks',
    'icon' => 'cube',
    'breadcrumbs' => [
    ['title' => 'Dashboard', 'url' => route('admin.dashboard.index')],
    ['title' => 'Fields', 'url' => route('admin.fields.index')],
    ['title' => $field->field, 'url' => '']
    ],
    'actions' => [
    [
    'text' => 'Add Track',
    'onclick' => 'openCreateTrackModal()',
    'icon' => 'plus',
    'class' => 'success'
    ],
    [
    'text' => 'Actions',
    'type' => 'dropdown',
    'icon' => 'ellipsis-v',
    'class' => 'outline-secondary',
    'items' => [
    ['text' => 'Export Data', 'icon' => 'download', 'onclick' => 'exportField()'],
    ['text' => 'Duplicate Field', 'icon' => 'copy', 'onclick' => 'duplicateField()'],
    'divider',
    ['text' => 'Delete Field', 'icon' => 'trash', 'onclick' => 'deleteField()']
    ]
    ]
    ]
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php echo $__env->make('admin.components.stats-row', [
    'stats' => [
    [
    'value' => $field->tracks_count,
    'label' => 'Tracks',
    'color' => 'primary',
    'icon' => 'route'
    ],
    [
    'value' => $field->skills_count,
    'label' => 'Skills',
    'color' => 'info',
    'icon' => 'brain'
    ],
    [
    'value' => $field->questions_count,
    'label' => 'Questions',
    'color' => 'success',
    'icon' => 'question-circle'
    ],
    [
    'value' => $field->active_questions_count,
    'label' => 'Active Questions',
    'color' => 'warning',
    'icon' => 'check-circle'
    ]
    ]
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="row">
        
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Field Details</h5>
                    <small class="text-muted">Click to edit inline</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Field Name</label>
                            <div class="editable-field" data-field="field" data-type="text">
                                <span class="field-display"><?php echo e($field->field); ?></span>
                                <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <div class="editable-field" data-field="status_id" data-type="select">
                                <span class="field-display">
                                    <span class="badge bg-<?php echo e($statusBadge); ?>">
                                        <?php echo e($field->status ? ucfirst($field->status->status) : 'Unknown'); ?>

                                    </span>
                                </span>
                                <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <div class="editable-field" data-field="description" data-type="textarea">
                                <span class="field-display"><?php echo e($field->description ?? 'No description'); ?></span>
                                <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Created</label>
                            <div><?php echo e($field->created_at->format('M j, Y g:i A')); ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Last Updated</label>
                            <div><?php echo e($field->updated_at->format('M j, Y g:i A')); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Tracks (<?php echo e($field->tracks_count); ?>)</h5>
                    <button class="btn btn-sm btn-success" onclick="openCreateTrackModal()">
                        <i class="fas fa-plus"></i> Add Track
                    </button>
                </div>
                <div class="card-body p-0">
                    <?php if($tracks->count() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Track Name</th>
                                    <th>Description</th>
                                    <th>Level</th>
                                    <th>Skills</th>
                                    <th>Status</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $tracks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $track): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr id="track-<?php echo e($track->id); ?>">
                                    <td>
                                        <a href="<?php echo e(route('admin.tracks.show', $track)); ?>" class="fw-semibold text-decoration-none">
                                            <?php echo e($track->track); ?>

                                        </a>
                                    </td>
                                    <td>
                                        <div style="max-width: 300px;">
                                            <?php echo e($track->description ? Str::limit($track->description, 80) : 'No description'); ?>

                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo e($track->level->description); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo e($track->skills->count()); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo e($track->status && $track->status->status === 'active' ? 'success' : 'warning'); ?>">
                                            <?php echo e($track->status ? ucfirst($track->status->status) : 'Unknown'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo e(route('admin.tracks.show', $track)); ?>" class="btn btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-outline-danger" onclick="deleteTrack(<?php echo e($track->id); ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <?php echo $__env->make('admin.components.empty-state', [
                    'icon' => 'route',
                    'title' => 'No Tracks Found',
                    'message' => 'Add tracks to this field to get started'
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        
        <div class="col-lg-4">
            
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Field Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Total Tracks</span>
                        <span class="badge bg-primary"><?php echo e($field->tracks_count); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Active Tracks</span>
                        <span class="badge bg-success">
                            <?php echo e($tracks->filter(fn($t) => $t->status && $t->status->status === 'active')->count()); ?>

                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Total Skills</span>
                        <span class="badge bg-info"><?php echo e($field->skills_count); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">Total Questions</span>
                        <span class="badge bg-success"><?php echo e($field->questions_count); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small">Active Questions</span>
                        <span class="badge bg-warning"><?php echo e($field->active_questions_count); ?></span>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="openCreateTrackModal()">
                            <i class="fas fa-plus me-2"></i>Create Track
                        </button>
                        <button class="btn btn-outline-success" onclick="exportField()">
                            <i class="fas fa-download me-2"></i>Export Field Data
                        </button>
                        <button class="btn btn-outline-warning" onclick="duplicateField()">
                            <i class="fas fa-copy me-2"></i>Duplicate Field
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="trackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Track</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="trackForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Track Name *</label>
                        <input type="text" class="form-control" name="track" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Level *</label>
                        <select class="form-select" name="level_id" required>
                            <option value="">Select level</option>
                            <?php $__currentLoopData = $levels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $level): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($level['id']); ?>">
                                <?php echo e($level['description']); ?> (Level <?php echo e($level['level']); ?>)
                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status_id" required>
                            <option value="">Select status</option>
                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($status['id']); ?>">
                                <?php echo e(ucfirst($status['text'])); ?>

                            </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Track</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    const FIELD_ID = <?php echo e($fieldId); ?>;
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const STATUSES = <?php echo json_encode($statuses, 15, 512) ?>;
    let trackModal;

    document.addEventListener('DOMContentLoaded', function() {
        setupInlineEditing();
        trackModal = new bootstrap.Modal(document.getElementById('trackModal'));
        document.getElementById('trackForm').addEventListener('submit', handleTrackSubmit);
    });

    function setupInlineEditing() {
        document.querySelectorAll('.editable-field').forEach(element => {
            element.addEventListener('click', function() {
                const field = this.dataset.field;
                const type = this.dataset.type;
                const fieldDisplay = this.querySelector('.field-display');
                const currentValue = fieldDisplay.textContent.trim();

                showInlineEditor(this, field, type, currentValue);
            });
        });
    }

    function showInlineEditor(element, field, type, currentValue) {
        const fieldDisplay = element.querySelector('.field-display');
        let input;

        switch(type) {
            case 'text':
            input = `<input type="text" class="form-control form-control-sm" value="${escapeHtml(currentValue)}" 
            onblur="saveInlineEdit(this, '${field}')" onkeypress="handleEnterKey(event, this, '${field}')" autofocus>`;
            break;
            case 'textarea':
            const textValue = currentValue === 'No description' ? '' : currentValue;
            input = `
            <textarea class="form-control form-control-sm mb-2" rows="3" autofocus data-field="${field}">${escapeHtml(textValue)}</textarea>
            <div>
            <button class="btn btn-sm btn-success me-1" onclick="saveTextareaEdit('${field}')">Save</button>
            <button class="btn btn-sm btn-secondary" onclick="cancelEdit(this)">Cancel</button>
            </div>`;
            break;
            case 'select':
            if (field === 'status_id') {
                let options = STATUSES.map(status => 
                    `<option value="${status.id}">${status.text}</option>`
                    ).join('');
                input = `<select class="form-select form-select-sm" onchange="saveInlineEdit(this, '${field}')" autofocus>${options}</select>`;
            }
            break;
        }

        fieldDisplay.innerHTML = input;
        element.dataset.originalValue = currentValue;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function saveTextareaEdit(field) {
        const textarea = document.querySelector(`textarea[data-field="${field}"]`);
        if (textarea) saveInlineEdit(textarea, field);
    }

    function cancelEdit(button) {
        const container = button.closest('.editable-field');
        const originalValue = container.dataset.originalValue;
        const fieldDisplay = container.querySelector('.field-display');
        fieldDisplay.innerHTML = originalValue === '' || originalValue === 'No description' 
        ? '<span class="text-muted">No description</span>' 
        : originalValue;
    }

    function handleEnterKey(event, input, field) {
        if (event.key === 'Enter') saveInlineEdit(input, field);
    }

    function saveInlineEdit(input, field) {
        const value = input.value;
        const container = input.closest('.editable-field');
        
        fetch(`/admin/fields/${FIELD_ID}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'  // ← ADD THIS LINE
            },
            body: JSON.stringify({ field: field, value: value })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let displayValue = value;
                
                if (field === 'status_id') {
                    const status = STATUSES.find(s => s.id == value);
                    const badgeClass = status && status.text === 'active' ? 'bg-success' : 'bg-warning';
                    displayValue = `<span class="badge ${badgeClass}">${status ? status.text : 'Unknown'}</span>`;
                } else if (field === 'description' && value.trim() === '') {
                    displayValue = '<span class="text-muted">No description</span>';
                }
                
                container.querySelector('.field-display').innerHTML = displayValue;
                showToast('Updated successfully', 'success');
            } else {
                showToast(data.message || 'Error updating field', 'error');
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
            location.reload();
        });
    }

    function openCreateTrackModal() {
        document.getElementById('trackForm').reset();
        trackModal.show();
    }

    function handleTrackSubmit(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('field_id', FIELD_ID);

        fetch('/admin/tracks', {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || data.message) {
                showToast(data.message || 'Track created successfully', 'success');
                trackModal.hide();
                location.reload();
            } else {
                throw new Error(data.message || 'Error creating track');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast(error.message, 'error');
        });
    }

    function deleteTrack(trackId) {
        if (!confirm('Delete this track? This cannot be undone.')) return;

        fetch(`/admin/tracks/${trackId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Track deleted successfully', 'success');
                document.getElementById(`track-${trackId}`).remove();
            } else {
                throw new Error(data.message || 'Error deleting track');
            }
        })
        .catch(error => showToast(error.message, 'error'));
    }

    function deleteField() {
        if (!confirm('Delete this field? This will also delete all associated tracks.')) return;

        fetch(`/admin/fields/${FIELD_ID}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        })
        .then(response => response.json())
        .then(data => {
            showToast('Field deleted successfully', 'success');
            window.location.href = '/admin/fields';
        })
        .catch(error => showToast('Error deleting field', 'error'));
    }

    function exportField() {
        window.location.href = `/admin/fields/${FIELD_ID}/export`;
    }

    function duplicateField() {
        if (!confirm('Create a duplicate of this field?')) return;

        fetch(`/admin/fields/${FIELD_ID}/duplicate`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        })
        .then(response => response.json())
        .then(data => {
            showToast('Field duplicated successfully', 'success');
            window.location.href = `/admin/fields/${data.field_id}`;
        })
        .catch(error => showToast('Error duplicating field', 'error'));
    }

    function showToast(message, type = 'info') {
        alert(`${type.toUpperCase()}: ${message}`);
    }
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\fields\show.blade.php ENDPATH**/ ?>