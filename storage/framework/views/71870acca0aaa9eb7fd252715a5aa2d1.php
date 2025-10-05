
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Fields Information</h6>
    <button type="button" class="btn btn-sm btn-primary" onclick="toggleFieldsEdit()">
        <i class="fas fa-edit me-1"></i><span id="fieldsEditText">Edit Fields</span>
    </button>
</div>

<?php if($user->fields->count() > 0): ?>
    <div id="fieldsContainer">
        <?php $__currentLoopData = $user->fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="card mb-3 field-item" data-field-id="<?php echo e($field->id); ?>" data-user-id="<?php echo e($user->id); ?>">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Field ID</label>
                            <input type="number" class="form-control field-id" value="<?php echo e($field->id); ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Field Maxile</label>
                            <input type="number" class="form-control field-editable field-maxile" 
                                   value="<?php echo e($field->pivot->field_maxile); ?>" min="0" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Month Achieved</label>
                            <input type="text" class="form-control field-editable month-achieved" 
                                   value="<?php echo e($field->pivot->month_achieved); ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Test Date</label>
                            <input type="datetime-local" class="form-control field-editable field-test-date" 
                                   value="<?php echo e($field->pivot->field_test_date ? date('Y-m-d\TH:i', strtotime($field->pivot->field_test_date)) : ''); ?>" readonly>
                        </div>
                    </div>
                    <div class="text-end mt-2 field-save-buttons" style="display:none;">
                        <button type="button" class="btn btn-sm btn-secondary me-2" onclick="cancelFieldEdit(this)">Cancel</button>
                        <button type="button" class="btn btn-sm btn-success" onclick="saveField(this)">
                            <i class="fas fa-save me-1"></i>Save
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div class="modal fade" id="fieldUpdateModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <i class="fas fa-check-circle text-success" style="font-size:3rem;"></i>
                    <h5 class="mt-2">Field Updated!</h5>
                    <p class="text-muted">The field information has been saved successfully.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Fields editing functions
    let isFieldsEditMode = false;

    function toggleFieldsEdit() {
        isFieldsEditMode = !isFieldsEditMode;
        const editText = document.getElementById('fieldsEditText');
        
        document.querySelectorAll('.field-editable').forEach(el => {
            if (isFieldsEditMode) {
                el.removeAttribute('readonly');
                el.closest('.field-item').classList.add('editing');
            } else {
                el.setAttribute('readonly', 'readonly');
                el.closest('.field-item').classList.remove('editing');
            }
        });

        document.querySelectorAll('.field-save-buttons').forEach(btn => {
            btn.style.display = isFieldsEditMode ? 'block' : 'none';
        });

        editText.textContent = isFieldsEditMode ? 'Cancel Edit' : 'Edit Fields';
    }

    function cancelFieldEdit(button) {
        window.location.reload();
    }

    function saveField(button) {
        const fieldItem = button.closest('.field-item');
        const fieldId = fieldItem.dataset.fieldId;
        const userId = fieldItem.dataset.userId;
        
        const data = {
            field_maxile: fieldItem.querySelector('.field-maxile').value,
            month_achieved: fieldItem.querySelector('.month-achieved').value,
            field_test_date: fieldItem.querySelector('.field-test-date').value,
            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            _method: 'PUT'
        };

        // Show loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        button.disabled = true;

        fetch(`/admin/users/${userId}/fields/${fieldId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = new bootstrap.Modal(document.getElementById('fieldUpdateModal'));
                modal.show();
                
                setTimeout(() => {
                    modal.hide();
                    button.innerHTML = '<i class="fas fa-save me-1"></i>Save';
                    button.disabled = false;
                    fieldItem.classList.remove('editing');
                    
                    fieldItem.querySelectorAll('.field-editable').forEach(input => {
                        input.setAttribute('readonly', 'readonly');
                    });
                    fieldItem.querySelector('.field-save-buttons').style.display = 'none';
                }, 2000);
            } else {
                throw new Error(data.message || 'Failed to update field');
            }
        })
        .catch(error => {
            alert('Error updating field: ' + error.message);
            button.innerHTML = '<i class="fas fa-save me-1"></i>Save';
            button.disabled = false;
        });
    }
    </script>

    <style>
    .field-item{transition:all 0.3s ease}
    .field-item.editing{border-color:#007bff;box-shadow:0 0 0 0.2rem rgba(0,123,255,0.25)}
    </style>
<?php else: ?>
    <div class="text-center text-muted py-5">
        <i class="fas fa-info-circle me-2"></i>No fields assigned to this user
    </div>
<?php endif; ?>
<?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\users\partials\fields-tab.blade.php ENDPATH**/ ?>