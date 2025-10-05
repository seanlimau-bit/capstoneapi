


<div class="modal fade" id="questionVariationModal" tabindex="-1" aria-labelledby="questionVariationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="questionVariationModalLabel">
                    <i class="fas fa-sparkles text-primary me-2"></i>
                    Generate Question Variations
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Original Question:</strong>
                            <div id="originalQuestionText" class="mt-2 p-2 bg-light rounded"></div>
                        </div>
                    </div>
                </div>

                <form id="questionVariationForm">
                    <input type="hidden" id="originalQuestionId" name="question_id">
                    <input type="hidden" id="originalSkillId" name="skill_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="variationCount" class="form-label">Number of Variations</label>
                            <select class="form-select" id="variationCount" name="question_count" required>
                                <option value="3" selected>3 variations</option>
                                <option value="5">5 variations</option>
                                <option value="8">8 variations</option>
                                <option value="10">10 variations</option>
                                <option value="15">15 variations</option>
                                <option value="20">20 variations</option>
                            </select>
                            <small class="form-text text-muted">How many question variations to generate</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="variationDifficulty" class="form-label">Target Difficulty</label>
                            <select class="form-select" id="variationDifficulty" name="difficulty_distribution" required>
                                <option value="same">Same as Original</option>
                                <option value="mixed" selected>Mixed Difficulties</option>
                                <option value="easy">Easy</option>
                                <option value="medium">Medium</option>
                                <option value="hard">Hard</option>
                                <option value="progressive">Progressive (Easy to Hard)</option>
                            </select>
                            <small class="form-text text-muted">Difficulty level for the variations</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="includeVariationExplanations" class="form-label">Include Explanations</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="includeVariationExplanations" name="include_explanations" checked>
                                <label class="form-check-label" for="includeVariationExplanations">
                                    Generate explanations for answers
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="variationFocusAreas" class="form-label">Additional Instructions to AI (Optional)</label>
                        <textarea class="form-control" id="variationFocusAreas" name="focus_areas" rows="3" placeholder="Provide specific instructions for how the AI should generate variations (e.g., 'focus on real-world applications', 'include edge cases', 'make them more challenging')"></textarea>
                        <small class="form-text text-muted">Leave blank to let AI decide the variation approach</small>
                    </div>

                    <div class="variation-preview d-none">
                        <div class="alert alert-warning">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Estimated Time:</strong> <span id="estimatedTime">30-60 seconds</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateQuestionVariations()" id="generateVariationsBtn">
                    <i class="fas fa-sparkles me-2"></i>
                    Generate Variations
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="variationResultsModal" tabindex="-1" aria-labelledby="variationResultsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="variationResultsModalLabel">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Generated Question Variations
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="variationResultsContent">
                    <!-- Results will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="acceptAllVariations()">
                    <i class="fas fa-check me-2"></i>
                    Accept All Variations
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables for question variation
let currentQuestionForVariation = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeQuestionVariationModal();
});

function initializeQuestionVariationModal() {
    // Update estimated time based on question count
    const variationCountSelect = document.getElementById('variationCount');
    if (variationCountSelect) {
        variationCountSelect.addEventListener('change', function() {
            const count = parseInt(this.value);
            let estimatedTime;
            
            if (count <= 3) {
                estimatedTime = '30-45 seconds';
            } else if (count <= 5) {
                estimatedTime = '45-60 seconds';
            } else if (count <= 10) {
                estimatedTime = '1-2 minutes';
            } else {
                estimatedTime = '2-3 minutes';
            }
            
            const timeElement = document.getElementById('estimatedTime');
            if (timeElement) {
                timeElement.textContent = estimatedTime;
            }
            
            // Show the preview area
            const previewArea = document.querySelector('.variation-preview');
            if (previewArea) {
                previewArea.classList.remove('d-none');
            }
        });
    }
}

// Main function to initiate question variation generation
function generateSimilar(questionId, skillId = null, questionText = null) {
    console.log('generateSimilar called:', { questionId, skillId, questionText });
    
    // Try to find question text if not provided
    if (!questionText) {
        const questionRow = document.querySelector(`button[onclick*="generateSimilar(${questionId})"]`);
        if (questionRow) {
            const row = questionRow.closest('tr');
            if (row) {
                const textCell = row.querySelector('td:nth-child(2) .fw-bold, td:nth-child(2)');
                if (textCell) {
                    questionText = textCell.textContent.trim();
                }
            }
        }
    }
    
    if (!questionText) {
        questionText = 'Question #' + questionId;
    }
    
    // Try to get skill ID from global variable if not provided
    if (!skillId && typeof window.skillId !== 'undefined') {
        skillId = window.skillId;
    }
    
    // Store the question data
    currentQuestionForVariation = {
        id: questionId,
        text: questionText,
        skillId: skillId
    };
    
    // Populate the modal with question data
    document.getElementById('originalQuestionId').value = questionId;
    document.getElementById('originalSkillId').value = skillId || '';
    document.getElementById('originalQuestionText').textContent = questionText;
    
    // Show the variation modal
    const modal = new bootstrap.Modal(document.getElementById('questionVariationModal'));
    modal.show();
    
    console.log('Question variation modal opened');
}

// Function to generate variations - renamed to avoid conflicts
function generateQuestionVariations() {
    console.log('generateQuestionVariations called');
    
    // Get form values
    // Instead of trying to get skillId from the field (which is empty)
    const skillId = document.getElementById('originalSkillId').value;const questionId = document.getElementById('originalQuestionId').value;
    const skillId = document.getElementById('originalSkillId').value;
    const questionCount = document.getElementById('variationCount').value;
    const difficulty = document.getElementById('variationDifficulty').value;
    const focusAreas = document.getElementById('variationFocusAreas').value;
    const includeExplanations = document.getElementById('includeVariationExplanations').checked;
    
    // Validate required fields
    if (!questionId) {
        showToast('Error: Question ID is required', 'error');
        return;
    }
    
    // Build the request data with all required fields
    const requestData = {
        question_id: parseInt(questionId),
        skill_id: skillId ? parseInt(skillId) : null,
        generation_method: 'ai_variation',
        question_count: parseInt(questionCount),
        difficulty_distribution: difficulty,
        question_types: 'same',
        focus_areas: focusAreas || '',
        include_explanations: includeExplanations ? 'on' : 'off'
    };
    
    console.log('Request data:', requestData);
    
    // Show loading state
    const generateBtn = document.getElementById('generateVariationsBtn');
    const originalText = generateBtn.innerHTML;
    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
    generateBtn.disabled = true;
    
    // Make the API request
    fetch('/admin/questions/generate', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            return response.json().then(err => {
                console.error('Server error:', err);
                throw new Error(err.message || 'Server error occurred');
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        // Restore button state
        generateBtn.innerHTML = originalText;
        generateBtn.disabled = false;
        
        if (data.success) {
            // Hide the generation modal
            const generationModal = bootstrap.Modal.getInstance(document.getElementById('questionVariationModal'));
            if (generationModal) {
                generationModal.hide();
            }
            
            // Show results modal
            displayVariationResults(data);
            
            showToast(`Successfully generated ${data.questions_created || data.count || 0} question variations!`, 'success');
        } else {
            showToast(data.message || 'Error generating variations', 'error');
        }
    })
    .catch(error => {
        console.error('Generation error:', error);
        
        // Restore button state
        generateBtn.innerHTML = originalText;
        generateBtn.disabled = false;
        
        showToast(`Error generating variations: ${error.message}`, 'error');
    });
}

// Function to display variation results
function displayVariationResults(data) {
    console.log('Displaying variation results:', data);
    
    const resultsContent = document.getElementById('variationResultsContent');
    if (!resultsContent) {
        console.error('Results content container not found');
        return;
    }
    
    let html = `
    <div class="row mb-4">
    <div class="col-12">
    <div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i>
    <strong>Generation Complete!</strong> Created ${data.questions_created || data.count || 0} variations of the original question.
    </div>
    </div>
    </div>
    
    <div class="row mb-3">
    <div class="col-12">
    <h6><i class="fas fa-question-circle me-2 text-primary"></i>Original Question:</h6>
    <div class="p-3 bg-light rounded mb-3">
    <strong>${data.original_question?.question || currentQuestionForVariation?.text || 'Original question'}</strong>
    <br><small class="text-muted">Skill: ${data.original_question?.skill_name || 'Unknown'}</small>
    </div>
    </div>
    </div>
    `;
    
    if (data.questions && data.questions.length > 0) {
        html += `<h6><i class="fas fa-sparkles me-2 text-success"></i>Generated Variations:</h6>`;
        
        data.questions.forEach((question, index) => {
            const difficultyBadgeClass = getDifficultyBadgeClass(question.difficulty);
            const typeBadgeClass = 'bg-info';
            
            html += `
            <div class="card mb-3 variation-card" data-question-id="${question.id || index}">
            <div class="card-header d-flex justify-content-between align-items-center">
            <div>
            <span class="fw-bold">Variation ${index + 1}</span>
            <span class="badge ${difficultyBadgeClass} ms-2">${question.difficulty || 'Medium'}</span>
            <span class="badge ${typeBadgeClass} ms-1">${question.type || 'MCQ'}</span>
            </div>
            <div class="form-check">
            <input class="form-check-input variation-accept" type="checkbox" 
            id="accept_${question.id || index}" checked>
            <label class="form-check-label text-success" for="accept_${question.id || index}">
            <i class="fas fa-check"></i> Accept
            </label>
            </div>
            </div>
            <div class="card-body">
            <div class="mb-3">
            <strong>Question:</strong>
            <div class="mt-1">${question.question || 'No question text'}</div>
            </div>
            
            <div class="mb-3">
            <strong>Answer Options:</strong>
            <div class="mt-1">
            <div class="row">
            <div class="col-md-6">
            <div class="form-check ${question.correct_answer === 0 ? 'text-success fw-bold' : ''}">
            <span class="badge ${question.correct_answer === 0 ? 'bg-success' : 'bg-secondary'} me-2">A</span>
            ${question.answer0 || 'Option A'}
            </div>
            <div class="form-check ${question.correct_answer === 1 ? 'text-success fw-bold' : ''}">
            <span class="badge ${question.correct_answer === 1 ? 'bg-success' : 'bg-secondary'} me-2">B</span>
            ${question.answer1 || 'Option B'}
            </div>
            </div>
            <div class="col-md-6">
            <div class="form-check ${question.correct_answer === 2 ? 'text-success fw-bold' : ''}">
            <span class="badge ${question.correct_answer === 2 ? 'bg-success' : 'bg-secondary'} me-2">C</span>
            ${question.answer2 || 'Option C'}
            </div>
            <div class="form-check ${question.correct_answer === 3 ? 'text-success fw-bold' : ''}">
            <span class="badge ${question.correct_answer === 3 ? 'bg-success' : 'bg-secondary'} me-2">D</span>
            ${question.answer3 || 'Option D'}
            </div>
            </div>
            </div>
            </div>
            </div>
            
            ${question.explanation ? `
                <div class="mb-2">
                <strong>Explanation:</strong>
                <div class="mt-1 text-muted">${question.explanation}</div>
                </div>
                ` : ''}
                </div>
                </div>
                `;
            });
    } else {
        html += `
        <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        No variations were generated. Please try again with different settings.
        </div>
        `;
    }
    
    resultsContent.innerHTML = html;
    
    // Show the results modal
    const resultsModal = new bootstrap.Modal(document.getElementById('variationResultsModal'));
    resultsModal.show();
}

// Function to get difficulty badge class
function getDifficultyBadgeClass(difficulty) {
    const difficultyLower = (difficulty || '').toLowerCase();
    switch(difficultyLower) {
        case 'easy': return 'bg-success';
        case 'medium': return 'bg-warning';
        case 'hard': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// Function to accept all variations
function acceptAllVariations() {
    const acceptedVariations = document.querySelectorAll('.variation-accept:checked');
    
    if (acceptedVariations.length === 0) {
        showToast('No variations selected to accept', 'warning');
        return;
    }
    
    // Hide the results modal
    const resultsModal = bootstrap.Modal.getInstance(document.getElementById('variationResultsModal'));
    if (resultsModal) {
        resultsModal.hide();
    }
    
    // Show success message
    showToast(`Accepted ${acceptedVariations.length} question variations`, 'success');
    
    // Reload the page to show the new questions after a brief delay
    setTimeout(() => {
        location.reload();
    }, 1500);
}

// Fallback toast function if showToast is not defined elsewhere
if (typeof showToast === 'undefined') {
    window.showToast = function(message, type = 'info') {
        console.log(`${type.toUpperCase()}: ${message}`);
        alert(`${type.toUpperCase()}: ${message}`);
    };
}
</script><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\questions\modals\question-variation-modal.blade.php ENDPATH**/ ?>