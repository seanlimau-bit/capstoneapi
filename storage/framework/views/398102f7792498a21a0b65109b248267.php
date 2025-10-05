
<div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateModalLabel">
                    <i class="fas fa-copy me-2 text-info"></i>Duplicate Skill
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="duplicateSkillForm">
                    <div class="mb-3">
                        <label for="duplicateSkillName" class="form-label">New Skill Name</label>
                        <input type="text" class="form-control" id="duplicateSkillName" name="skill_name" 
                               value="<?php echo e($skill->skill); ?> (Copy)" required>
                        <div class="form-text">The duplicated skill will use this name</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">What to Include:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="tracks" id="includeTracks" checked>
                            <label class="form-check-label" for="includeTracks">
                                <i class="fas fa-route me-1"></i>Associated Tracks (<?php echo e($skill->tracks->count()); ?>)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="questions" id="includeQuestions" checked>
                            <label class="form-check-label" for="includeQuestions">
                                <i class="fas fa-question-circle me-1"></i>Questions (<?php echo e($skill->questions->count()); ?>)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="videos" id="includeVideos" checked>
                            <label class="form-check-label" for="includeVideos">
                                <i class="fas fa-video me-1"></i>Video Links (<?php echo e($skill->links->count()); ?>)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="image" id="includeImage" checked>
                            <label class="form-check-label" for="includeImage">
                                <i class="fas fa-image me-1"></i>Skill Image
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="duplicateStatus" class="form-label">Initial Status</label>
                        <select class="form-select" id="duplicateStatus" name="status_id">
                            <option value="4">Draft (Recommended)</option>
                            <option value="3">Active</option>
                        </select>
                        <div class="form-text">Duplicated skills are usually set to Draft for review</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> The duplicated skill will be created with you as the creator. 
                        All content will be copied but marked as unverified and requiring review.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" onclick="submitDuplication()">
                    <i class="fas fa-copy me-1"></i>Duplicate Skill
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function submitDuplication() {
    const form = document.getElementById('duplicateSkillForm');
    const submitBtn = event.target;
    
    // Get form data
    const formData = new FormData(form);
    const skillName = formData.get('skill_name').trim();
    const statusId = formData.get('status_id');
    
    // Get included items
    const includes = [];
    if (document.getElementById('includeTracks').checked) includes.push('tracks');
    if (document.getElementById('includeQuestions').checked) includes.push('questions');
    if (document.getElementById('includeVideos').checked) includes.push('videos');
    if (document.getElementById('includeImage').checked) includes.push('image');
    
    // Validation
    if (!skillName) {
        showToast('Please enter a skill name', 'warning');
        document.getElementById('duplicateSkillName').focus();
        return;
    }
    
    // Show loading state
    setButtonLoading(submitBtn, true);
    
    // Prepare request data
    const requestData = {
        skill_name: skillName,
        status_id: statusId,
        include: includes
    };
    
    // Send request
    fetch(`/admin/skills/<?php echo e($skill->id); ?>/copy`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.code === 201) {
            showToast('Skill duplicated successfully!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('duplicateModal'));
            modal.hide();
            
            // Ask if user wants to view the new skill
            setTimeout(() => {
                if (confirm('Skill duplicated successfully! Would you like to view the new skill?')) {
                    window.location.href = `/admin/skills/${data.skill.id}`;
                }
            }, 1000);
        } else {
            showToast(data.message || 'Error duplicating skill', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error duplicating skill', 'error');
    })
    .finally(() => {
        setButtonLoading(submitBtn, false);
    });
}

// Reset form when modal is shown
document.getElementById('duplicateModal').addEventListener('show.bs.modal', function() {
    document.getElementById('duplicateSkillForm').reset();
    document.getElementById('duplicateSkillName').value = '<?php echo e($skill->skill); ?> (Copy)';
    document.getElementById('duplicateStatus').value = '4';
    
    // Check all include options by default
    document.getElementById('includeTracks').checked = true;
    document.getElementById('includeQuestions').checked = true;
    document.getElementById('includeVideos').checked = true;
    document.getElementById('includeImage').checked = true;
});
</script><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\skills\modals\duplicate.blade.php ENDPATH**/ ?>