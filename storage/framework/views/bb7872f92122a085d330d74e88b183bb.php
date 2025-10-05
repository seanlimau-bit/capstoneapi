
<div class="modal fade" id="generateQuestionsModal" tabindex="-1" aria-labelledby="generateQuestionsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateQuestionsModalLabel">
                    <i class="fas fa-magic me-2 text-success"></i>Generate Questions with AI
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="generateQuestionsForm">
                    <div class="mb-4">
                        <label for="questionCount" class="form-label fs-5">How many questions would you like to generate?</label>
                        <select class="form-select form-select-lg" id="questionCount" name="count">
                            <option value="5">5 Questions</option>
                            <option value="10" selected>10 Questions</option>
                            <option value="15">15 Questions</option>
                            <option value="20">20 Questions</option>
                            <option value="25">25 Questions</option>
                            <option value="30">30 Questions</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="additionalContext" class="form-label">Additional instructions (Optional)</label>
                        <textarea class="form-control" id="additionalContext" name="additional_context" rows="3" 
                                  placeholder="Any specific requirements or focus areas for the questions..."></textarea>
                    </div>
                </form>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Our AI will analyze the skill "<strong><?php echo e($skill->skill); ?></strong>" and create appropriate questions with explanations.
                </div>
                
                <!-- Progress indicator (hidden by default) -->
                <div id="generationProgress" class="d-none">
                    <div class="d-flex align-items-center mb-3">
                        <div class="spinner-border text-success me-3" role="status">
                            <span class="visually-hidden">Generating...</span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold">Generating Questions...</span>
                                <span id="progressText">0%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" id="progressBar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                    <div id="progressMessages" class="small text-muted">
                        <div>AI analyzing skill requirements...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-lg" id="generateBtn" onclick="submitGenerateQuestions()">
                    <i class="fas fa-magic me-1"></i>Generate Questions
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function submitGenerateQuestions() {
    const form = document.getElementById('generateQuestionsForm');
    const submitBtn = document.getElementById('generateBtn');
    const progressDiv = document.getElementById('generationProgress');
    
    // Get form data
    const formData = new FormData(form);
    
    // Show progress and hide form
    form.style.display = 'none';
    progressDiv.classList.remove('d-none');
    submitBtn.disabled = true;
    
    // Prepare request data with sensible defaults
    const requestData = {
        skill_id: <?php echo e($skill->id); ?>,
        generation_method: 'ai',
        question_count: formData.get('count'),
        difficulty_distribution: 'auto',
        question_types: 'mixed',
        focus_areas: formData.get('additional_context') || '',
        include_explanations: 'on'
    };
    
    // Simulate progress updates
    simulateProgress();
    
    // Send request
    fetch('/admin/skills/generate', {
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
        if (data.success) {
            // Complete progress
            updateProgress(100, 'Complete!');
            addProgressMessage('Questions generated successfully!');
            
            setTimeout(() => {
                showToast(`${data.generated_count} questions generated successfully!`, 'success');
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('generateQuestionsModal'));
                modal.hide();
                
                // Redirect to review page
                setTimeout(() => {
                    window.location.href = `/admin/questions/review?batch_id=${data.batch_id}`;
                }, 1500);
            }, 1000);
        } else {
            showToast(data.message || 'Error generating questions', 'error');
            resetModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error generating questions. Please try again.', 'error');
        resetModal();
    });

function simulateProgress() {
    const messages = [
        'AI analyzing skill requirements...',
        'Creating mathematical content...',
        'Generating questions...',
        'Validating answers...',
        'Almost done...'
    ];
    
    let progress = 0;
    let messageIndex = 0;
    
    const interval = setInterval(() => {
        progress += Math.random() * 20;
        if (progress > 90) progress = 90; // Stop at 90% until real completion
        
        updateProgress(progress, `${Math.round(progress)}%`);
        
        if (messageIndex < messages.length && progress > (messageIndex + 1) * 18) {
            addProgressMessage(messages[messageIndex]);
            messageIndex++;
        }
    }, 1000);
    
    // Store interval to clear it later
    window.generationInterval = interval;
}

function updateProgress(percentage, text) {
    document.getElementById('progressBar').style.width = percentage + '%';
    document.getElementById('progressText').textContent = text;
}

function addProgressMessage(message) {
    const messagesDiv = document.getElementById('progressMessages');
    const messageElement = document.createElement('div');
    messageElement.textContent = message;
    messagesDiv.appendChild(messageElement);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function resetModal() {
    const form = document.getElementById('generateQuestionsForm');
    const progressDiv = document.getElementById('generationProgress');
    const submitBtn = document.getElementById('generateBtn');
    
    // Clear any running intervals
    if (window.generationInterval) {
        clearInterval(window.generationInterval);
    }
    
    // Reset UI
    form.style.display = 'block';
    progressDiv.classList.add('d-none');
    submitBtn.disabled = false;
    
    // Reset progress
    updateProgress(0, '0%');
    document.getElementById('progressMessages').innerHTML = '<div>AI analyzing skill requirements...</div>';
}

// Reset form when modal is shown
document.getElementById('generateQuestionsModal').addEventListener('show.bs.modal', function() {
    resetModal();
    document.getElementById('generateQuestionsForm').reset();
    
    // Set default
    document.getElementById('questionCount').value = '10';
});

// Clean up when modal is hidden
document.getElementById('generateQuestionsModal').addEventListener('hidden.bs.modal', function() {
    resetModal();
});
</script><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\skills\modals\generate-questions.blade.php ENDPATH**/ ?>