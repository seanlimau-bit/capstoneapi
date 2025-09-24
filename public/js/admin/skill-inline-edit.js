/**
 * Skill Inline Edit JavaScript
 * Handles inline editing functionality for skill details
 */

let currentlyEditing = null;
let skillId = null;

function initializeInlineEditing(id) {
    skillId = id;
    
    // Initialize text/textarea inline editing
    document.querySelectorAll('.editable-field').forEach(field => {
        field.addEventListener('click', handleFieldEdit);
    });
    
    // Initialize status dropdown editing
    document.querySelectorAll('.editable-status').forEach(status => {
        status.addEventListener('click', handleStatusEdit);
    });
    
    // Initialize verification toggle editing
    document.querySelectorAll('.editable-check').forEach(check => {
        check.addEventListener('click', handleVerificationToggle);
    });
    
    // Handle click outside to cancel editing
    document.addEventListener('click', handleClickOutside);
    
    // Handle escape key to cancel editing
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && currentlyEditing) {
            cancelEdit();
        }
    });
}

function handleFieldEdit(e) {
    e.stopPropagation();
    
    if (currentlyEditing && currentlyEditing !== e.currentTarget) {
        cancelEdit();
    }
    
    if (currentlyEditing === e.currentTarget) {
        return; // Already editing this field
    }
    
    const field = e.currentTarget;
    const fieldName = field.dataset.field;
    const fieldType = field.dataset.type || 'text';
    const currentValue = field.textContent.trim();
    
    startEdit(field, fieldName, fieldType, currentValue);
}

function startEdit(field, fieldName, fieldType, currentValue) {
    currentlyEditing = field;
    field.classList.add('editing');
    
    // Hide the edit icon
    const editIcon = field.querySelector('.edit-icon');
    if (editIcon) {
        editIcon.style.display = 'none';
    }
    
    // Create input element
    let input;
    if (fieldType === 'textarea') {
        input = document.createElement('textarea');
        input.rows = 3;
        input.style.resize = 'vertical';
    } else {
        input = document.createElement('input');
        input.type = 'text';
    }
    
    input.value = currentValue;
    input.className = 'form-control';
    input.style.width = '100%';
    input.style.minHeight = fieldType === 'textarea' ? '80px' : '38px';
    
    // Create action buttons
    const actionDiv = document.createElement('div');
    actionDiv.className = 'mt-2 d-flex gap-2';
    actionDiv.innerHTML = `
        <button type="button" class="btn btn-success btn-sm" onclick="saveEdit('${fieldName}')">
            <i class="fas fa-check me-1"></i>Save
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelEdit()">
            <i class="fas fa-times me-1"></i>Cancel
        </button>
    `;
    
    // Replace content with input
    field.innerHTML = '';
    field.appendChild(input);
    field.appendChild(actionDiv);
    
    // Focus and select the input
    input.focus();
    if (fieldType === 'text') {
        input.select();
    }
    
    // Handle enter key for text inputs
    if (fieldType === 'text') {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveEdit(fieldName);
            }
        });
    }
    
    // Handle ctrl+enter for textarea
    if (fieldType === 'textarea') {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                e.preventDefault();
                saveEdit(fieldName);
            }
        });
    }
}

function saveEdit(fieldName) {
    if (!currentlyEditing) return;
    
    const input = currentlyEditing.querySelector('input, textarea');
    const newValue = input.value.trim();
    
    if (newValue === '') {
        showToast('Field cannot be empty', 'warning');
        input.focus();
        return;
    }
    
    // Show loading state
    const saveBtn = currentlyEditing.querySelector('.btn-success');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    saveBtn.disabled = true;
    
    // Send AJAX request
    fetch(`/admin/skills/${skillId}/update-field`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            field: fieldName,
            value: newValue
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the field with new value
            finishEdit(newValue);
            showToast(`${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} updated successfully`, 'success');
        } else {
            showToast(data.message || 'Error updating field', 'error');
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating field', 'error');
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

function cancelEdit() {
    if (!currentlyEditing) return;
    
    const field = currentlyEditing;
    const fieldName = field.dataset.field;
    const originalValue = field.dataset.originalValue || field.textContent.trim();
    
    finishEdit(originalValue);
}

function finishEdit(value) {
    if (!currentlyEditing) return;
    
    const field = currentlyEditing;
    const fieldName = field.dataset.field;
    
    // Restore original content
    field.innerHTML = `
        ${value}
        <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
    `;
    
    field.classList.remove('editing');
    currentlyEditing = null;
}

function handleStatusEdit(e) {
    e.stopPropagation();
    
    if (currentlyEditing) {
        cancelEdit();
        return;
    }
    
    const statusElement = e.currentTarget;
    const currentStatus = statusElement.dataset.current;
    
    // Create dropdown
    const dropdown = document.createElement('select');
    dropdown.className = 'form-select form-select-sm';
    dropdown.innerHTML = `
        <option value="3" ${currentStatus === '3' ? 'selected' : ''}>Active</option>
        <option value="4" ${currentStatus === '4' ? 'selected' : ''}>Draft</option>
    `;
    
    // Create action buttons
    const actionDiv = document.createElement('div');
    actionDiv.className = 'mt-2 d-flex gap-2';
    actionDiv.innerHTML = `
        <button type="button" class="btn btn-success btn-sm" onclick="saveStatus()">
            <i class="fas fa-check me-1"></i>Save
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelStatusEdit()">
            <i class="fas fa-times me-1"></i>Cancel
        </button>
    `;
    
    // Store original content
    const originalContent = statusElement.innerHTML;
    statusElement.dataset.originalContent = originalContent;
    
    // Replace content
    statusElement.innerHTML = '';
    statusElement.appendChild(dropdown);
    statusElement.appendChild(actionDiv);
    
    currentlyEditing = statusElement;
    dropdown.focus();
}

function saveStatus() {
    if (!currentlyEditing) return;
    
    const dropdown = currentlyEditing.querySelector('select');
    const newStatus = dropdown.value;
    const saveBtn = currentlyEditing.querySelector('.btn-success');
    
    // Show loading
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
    saveBtn.disabled = true;
    
    fetch(`/admin/skills/${skillId}/update-field`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            field: 'status_id',
            value: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the status display
            const statusBadge = newStatus === '3' 
                ? '<span class="badge bg-success status-badge"><i class="fas fa-check-circle me-1"></i>Active</span>'
                : '<span class="badge bg-warning status-badge"><i class="fas fa-edit me-1"></i>Draft</span>';
            
            currentlyEditing.innerHTML = statusBadge + '<i class="fas fa-chevron-down text-muted ms-2 edit-icon"></i>';
            currentlyEditing.dataset.current = newStatus;
            currentlyEditing = null;
            
            showToast('Status updated successfully', 'success');
        } else {
            showToast(data.message || 'Error updating status', 'error');
            cancelStatusEdit();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating status', 'error');
        cancelStatusEdit();
    });
}

function cancelStatusEdit() {
    if (!currentlyEditing) return;
    
    const originalContent = currentlyEditing.dataset.originalContent;
    currentlyEditing.innerHTML = originalContent;
    currentlyEditing = null;
}

function handleVerificationToggle(e) {
    e.stopPropagation();
    
    const checkElement = e.currentTarget;
    const currentValue = checkElement.dataset.current === 'true';
    const newValue = !currentValue;
    
    // Show loading state
    const originalContent = checkElement.innerHTML;
    checkElement.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
    
    fetch(`/admin/skills/${skillId}/update-field`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            field: 'check',
            value: newValue
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the verification display
            const verificationBadge = newValue
                ? '<span class="badge bg-success verification-badge"><i class="fas fa-check-circle me-1"></i>Verified</span>'
                : '<span class="badge bg-warning verification-badge"><i class="fas fa-clock me-1"></i>Pending</span>';
            
            checkElement.innerHTML = verificationBadge + '<i class="fas fa-toggle-on text-muted ms-2 edit-icon"></i>';
            checkElement.dataset.current = newValue.toString();
            
            showToast(`Verification ${newValue ? 'enabled' : 'disabled'}`, 'success');
        } else {
            showToast(data.message || 'Error updating verification', 'error');
            checkElement.innerHTML = originalContent;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating verification', 'error');
        checkElement.innerHTML = originalContent;
    });
}

function handleClickOutside(e) {
    if (!currentlyEditing) return;
    
    // Check if click is outside the currently editing element
    if (!currentlyEditing.contains(e.target)) {
        if (currentlyEditing.classList.contains('editable-field')) {
            // For text fields, save the changes
            const fieldName = currentlyEditing.dataset.field;
            saveEdit(fieldName);
        } else {
            // For status/verification, cancel the edit
            cancelEdit();
        }
    }
}

// Utility function to show loading state on buttons
function setButtonLoading(button, loading = true) {
    if (loading) {
        button.disabled = true;
        const originalText = button.innerHTML;
        button.setAttribute('data-original-text', originalText);
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
    } else {
        button.disabled = false;
        const originalText = button.getAttribute('data-original-text');
        if (originalText) {
            button.innerHTML = originalText;
        }
    }
}