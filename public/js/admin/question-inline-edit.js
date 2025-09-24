(function () {
  // ---------------------------
  // Config
  // ---------------------------
  const MAX_FILE_MB = 6;
  const ACCEPTED_TYPES = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'];
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const endpoints = {
    questionUpload: (questionId) => `/admin/questions/${questionId}/image`,
    questionDelete: (questionId) => `/admin/questions/${questionId}/image`,
    answerUpload:   (questionId, option) => `/admin/questions/${questionId}/answers/${option}/image`,
    answerDelete:   (questionId, option) => `/admin/questions/${questionId}/answers/${option}/image`,
    updateField:    (questionId) => `/admin/questions/${questionId}/update-field`,
  };

  // ---------------------------
  // Utils
  // ---------------------------
  function getQuestionId() {
    if (window.QUESTION_ID) return window.QUESTION_ID;
    if (window.currentQuestionId) return window.currentQuestionId;
    
    const fromDataAttr = document.querySelector('[data-question-id]')?.dataset.questionId;
    if (fromDataAttr) return fromDataAttr;
    
    const urlMatch = window.location.pathname.match(/\/questions\/(\d+)/);
    if (urlMatch) return urlMatch[1];
    
    console.warn('Could not determine question ID');
    return null;
  }

  function toast(msg, type = 'info') {
    console.log(`${type.toUpperCase()}: ${msg}`);
  }

  function confirmBox(msg) {
    if (typeof confirmDelete === 'function') return confirmDelete(msg);
    return confirm(msg);
  }

  function validateFile(file) {
    if (!file) return 'No file selected.';
    if (!ACCEPTED_TYPES.includes(file.type)) {
      return `Unsupported file type. Allowed: ${ACCEPTED_TYPES.join(', ')}`;
    }
    const sizeMb = file.size / (1024 * 1024);
    if (sizeMb > MAX_FILE_MB) {
      return `File too large (${sizeMb.toFixed(1)} MB). Max ${MAX_FILE_MB} MB.`;
    }
    return null;
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  function createImagePreviewModal(file, onConfirm, onCancel, title) {
    return new Promise((resolve) => {
      const modal = document.createElement('div');
      modal.className = 'modal fade show';
      modal.style.display = 'block';
      modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
      
      const reader = new FileReader();
      reader.onload = function(e) {
        modal.innerHTML = `
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">
                  <i class="fas fa-image me-2"></i>Preview ${title}
                </h5>
                <button type="button" class="btn-close" data-dismiss="modal"></button>
              </div>
              <div class="modal-body text-center">
                <div class="mb-3">
                  <img src="${e.target.result}" class="img-fluid rounded" style="max-height: 400px; max-width: 100%; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                </div>
                <div class="row text-center">
                  <div class="col-md-4">
                    <small class="text-muted">File Name</small>
                    <div class="fw-semibold">${file.name}</div>
                  </div>
                  <div class="col-md-4">
                    <small class="text-muted">File Size</small>
                    <div class="fw-semibold">${formatFileSize(file.size)}</div>
                  </div>
                  <div class="col-md-4">
                    <small class="text-muted">File Type</small>
                    <div class="fw-semibold">${file.type}</div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-action="cancel">
                  <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" data-action="upload">
                  <i class="fas fa-upload me-1"></i>Upload Image
                </button>
              </div>
            </div>
          </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.querySelector('[data-action="cancel"]').onclick = () => {
          modal.remove();
          resolve(false);
        };
        
        modal.querySelector('[data-action="upload"]').onclick = () => {
          modal.remove();
          resolve(true);
        };
        
        modal.querySelector('.btn-close').onclick = () => {
          modal.remove();
          resolve(false);
        };
        
        modal.onclick = (e) => {
          if (e.target === modal) {
            modal.remove();
            resolve(false);
          }
        };
      };
      
      reader.readAsDataURL(file);
    });
  }

  function pickImageFile() {
    return new Promise((resolve) => {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = ACCEPTED_TYPES.join(',');
      input.style.display = 'none';
      document.body.appendChild(input);
      input.addEventListener('change', () => {
        const file = input.files?.[0] || null;
        input.remove();
        resolve(file);
      });
      input.click();
    });
  }

  async function uploadImage(url, file) {
    const err = validateFile(file);
    if (err) {
      console.error(err);
      throw new Error(err);
    }
    
    const form = new FormData();
    form.append('image', file);

    const res = await fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
      body: form
    });

    if (!res.ok) {
      const text = await res.text().catch(() => '');
      throw new Error(text || `Upload failed with ${res.status}`);
    }
    
    const data = await res.json().catch(() => ({}));
    if (!data?.success || !data?.image_url) {
      throw new Error(data?.message || 'Upload failed.');
    }
    return data.image_url;
  }

  async function deleteImage(url, confirmMessage) {
    const ok = confirmBox(confirmMessage || 'Remove this image?');
    if (!ok) return false;

    const res = await fetch(url, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    });
    
    if (!res.ok) {
      const text = await res.text().catch(() => '');
      throw new Error(text || `Delete failed with ${res.status}`);
    }
    
    const data = await res.json().catch(() => ({}));
    if (!data?.success) {
      throw new Error(data?.message || 'Delete failed.');
    }
    return true;
  }

  // ---------------------------
  // Generic Field Update Function
  // ---------------------------
  function updateQuestionField(questionId, fieldName, value) {
    console.log(`Updating ${fieldName} for question:`, questionId, 'to value:', value);
    
    fetch(endpoints.updateField(questionId), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        field: fieldName,
        value: value
      })
    })
    .then(response => {
      console.log('Response status:', response.status);
      return response.json();
    })
    .then(data => {
      console.log('Response data:', data);
      if (data.success) {
        console.log(`${fieldName} updated successfully, reloading page`);
        location.reload();
      } else {
        console.error(`Error updating ${fieldName}:`, data.message);
        const selectorClass = fieldName.replace('_', '-') + '-selector';
        const selector = document.querySelector(`.${selectorClass}`);
        if (selector) {
          selector.value = selector.getAttribute('data-current');
        }
      }
    })
    .catch(error => {
      console.error('Fetch error:', error);
      const selectorClass = fieldName.replace('_', '-') + '-selector';
      const selector = document.querySelector(`.${selectorClass}`);
      if (selector) {
        selector.value = selector.getAttribute('data-current');
      }
    });
  }

  function updateQuestionSkill(questionId, skillId) {
    updateQuestionField(questionId, 'skill_id', skillId);
  }

  // ---------------------------
  // Inline Text Editing
  // ---------------------------
  function bindInlineEditing() {
    document.querySelectorAll('.editable-field').forEach(field => {
      if (field.dataset.bound) return; // Avoid double binding
      field.dataset.bound = 'true';
      
      field.addEventListener('click', function() {
        if (this.classList.contains('editing')) return;
        
        const fieldName = this.getAttribute('data-field');
        const questionId = this.getAttribute('data-id');
        const fieldType = this.getAttribute('data-type') || 'text';
        
        makeFieldEditable(this, fieldName, questionId, fieldType);
      });
    });
  }

  function makeFieldEditable(element, fieldName, questionId, fieldType) {
    element.classList.add('editing');
    
    // Get original text (strip HTML for editing)
    const originalText = element.textContent.trim();
    const originalHtml = element.innerHTML;
    
    let inputElement;
    
    if (fieldType === 'textarea' || fieldType === 'html') {
      inputElement = document.createElement('textarea');
      inputElement.className = 'form-control';
      inputElement.rows = fieldType === 'html' ? 6 : 4;
      inputElement.value = originalText;
    } else {
      inputElement = document.createElement('input');
      inputElement.type = 'text';
      inputElement.className = 'form-control';
      inputElement.value = originalText;
    }
    
    // Create save and cancel buttons
    const buttonContainer = document.createElement('div');
    buttonContainer.className = 'mt-2 d-flex gap-2';
    
    const saveBtn = document.createElement('button');
    saveBtn.className = 'btn btn-success btn-sm';
    saveBtn.innerHTML = '<i class="fas fa-check"></i> Save';
    
    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'btn btn-secondary btn-sm';
    cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
    
    buttonContainer.appendChild(saveBtn);
    buttonContainer.appendChild(cancelBtn);
    
    // Replace content with input
    element.innerHTML = '';
    element.appendChild(inputElement);
    element.appendChild(buttonContainer);
    
    inputElement.focus();
    inputElement.select();
    
    // Save functionality
    saveBtn.addEventListener('click', function() {
      const newValue = inputElement.value.trim();
      
      if (newValue === originalText) {
        cancelEdit();
        return;
      }
      
      saveFieldValue(questionId, fieldName, newValue, element, newValue);
    });
    
    // Cancel functionality
    cancelBtn.addEventListener('click', cancelEdit);
    
    // Cancel on Escape key
    inputElement.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        cancelEdit();
      } else if (e.key === 'Enter' && !e.shiftKey && fieldType !== 'textarea' && fieldType !== 'html') {
        e.preventDefault();
        saveBtn.click();
      }
    });
    
    function cancelEdit() {
      element.innerHTML = originalHtml;
      element.classList.remove('editing');
    }
  }

  function saveFieldValue(questionId, fieldName, value, element, displayValue) {
    const saveData = {
      field: fieldName,
      value: value
    };
    
    fetch(endpoints.updateField(questionId), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(saveData)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update the element with new value, keeping the edit icon
        if (fieldName === 'question' && element.querySelector('.fib-content')) {
          // Special handling for HTML content
          element.innerHTML = `
            <div class="fib-content">${displayValue}</div>
            <i class="fas fa-code text-muted ms-2 edit-icon"></i>
          `;
        } else {
          element.innerHTML = `${displayValue} <i class="fas fa-edit text-muted ms-2 edit-icon"></i>`;
        }
        element.classList.remove('editing');
        console.log(`${fieldName} updated successfully`);
      } else {
        console.error('Error updating field:', data.message);
        element.classList.remove('editing');
        alert('Error: ' + (data.message || 'Failed to update field'));
        // Reload to get fresh data
        location.reload();
      }
    })
    .catch(error => {
      console.error('Error:', error);
      element.classList.remove('editing');
      alert('Error updating field');
      location.reload();
    });
  }

  // ---------------------------
  // DOM helpers for updating UI
  // ---------------------------
  function setQuestionImage(src) {
    const wrapper = document.querySelector('#question-image-section');
    if (!wrapper) {
      console.warn('Question image section not found');
      return;
    }

    wrapper.innerHTML = `
      <label class="form-label text-muted small">QUESTION IMAGE</label>
      <div class="image-container">
        <div class="image-wrapper">
          <img src="${src}" alt="Question Image" class="question-image">
          <div class="image-overlay">
            <button class="btn btn-light btn-sm" data-action="change-question-image" title="Change Image">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-danger btn-sm" data-action="remove-question-image" title="Remove Image">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
    `;
    rebindButtons();
  }

  function clearQuestionImage() {
    const wrapper = document.querySelector('#question-image-section');
    if (!wrapper) return;

    wrapper.innerHTML = `
      <label class="form-label text-muted small">QUESTION IMAGE</label>
      <div class="upload-area" data-action="add-question-image">
        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
        <h5 class="text-muted mb-2">Upload Question Image</h5>
        <p class="text-muted small mb-3">Click to browse or drag and drop</p>
        <p class="text-muted small">Supports: JPG, PNG, GIF, WebP (Max 6MB)</p>
      </div>
    `;
    rebindButtons();
  }

  function setAnswerImage(optionIndex, src) {
    const block = document.querySelector(`[data-option-index="${optionIndex}"]`);
    if (!block) {
      console.warn(`Answer option block ${optionIndex} not found`);
      return;
    }

    const flex = block.querySelector('.flex-grow-1');
    if (!flex) return;

    const existingImageContainer = flex.querySelector('.answer-image-container');
    const existingUploadArea = flex.querySelector('.upload-area-small');
    
    if (existingImageContainer) existingImageContainer.remove();
    if (existingUploadArea) existingUploadArea.remove();

    const html = `
      <div class="answer-image-container">
        <div class="image-wrapper-small">
          <img src="${src}" alt="Option Image" class="answer-image" style="max-height: 100px;">
          <div class="image-overlay-small">
            <button class="btn btn-light btn-sm" data-action="change-answer-image" data-option="${optionIndex}" title="Change">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-danger btn-sm" data-action="remove-answer-image" data-option="${optionIndex}" title="Remove">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
    `;
    flex.insertAdjacentHTML('beforeend', html);
    rebindButtons();
  }

  function clearAnswerImage(optionIndex) {
    const block = document.querySelector(`[data-option-index="${optionIndex}"]`);
    if (!block) return;

    const flex = block.querySelector('.flex-grow-1');
    if (!flex) return;

    const imgContainer = flex.querySelector('.answer-image-container');
    if (imgContainer) {
      imgContainer.remove();
    }

    if (!flex.querySelector('.upload-area-small')) {
      const html = `
        <div class="upload-area-small" data-action="add-answer-image" data-option="${optionIndex}">
          <i class="fas fa-plus me-2"></i>Add Image
        </div>
      `;
      flex.insertAdjacentHTML('beforeend', html);
    }
    rebindButtons();
  }

  // ---------------------------
  // Public handler functions
  // ---------------------------
  window.changeQuestionImage = async function (questionId) {
    const qid = questionId || getQuestionId();
    if (!qid) {
      console.error('Unable to determine question ID');
      return;
    }

    try {
      const file = await pickImageFile();
      if (!file) return;

      const shouldUpload = await createImagePreviewModal(file, null, null, 'Question Image');
      if (!shouldUpload) return;

      console.log('Uploading question image...');
      const url = endpoints.questionUpload(qid);
      const imageUrl = await uploadImage(url, file);
      setQuestionImage(imageUrl);
      console.log('Question image updated successfully');
    } catch (err) {
      console.error('Failed to upload question image:', err);
    }
  };

  window.addQuestionImage = window.changeQuestionImage;

  window.removeQuestionImage = async function (questionId) {
    const qid = questionId || getQuestionId();
    if (!qid) {
      console.error('Unable to determine question ID');
      return;
    }

    try {
      const ok = await deleteImage(endpoints.questionDelete(qid), 'Remove the question image?');
      if (!ok) return;
      clearQuestionImage();
      console.log('Question image removed successfully');
    } catch (err) {
      console.error('Failed to remove question image:', err);
    }
  };

  window.changeAnswerImage = async function (questionId, option) {
    const qid = questionId || getQuestionId();
    if (!qid) {
      console.error('Unable to determine question ID');
      return;
    }

    try {
      const file = await pickImageFile();
      if (!file) return;

      const shouldUpload = await createImagePreviewModal(file, null, null, `Option ${indexToLetter(option)} Image`);
      if (!shouldUpload) return;

      console.log(`Uploading image for option ${indexToLetter(option)}...`);
      const url = endpoints.answerUpload(qid, option);
      const imageUrl = await uploadImage(url, file);
      setAnswerImage(option, imageUrl);
      console.log(`Image for option ${indexToLetter(option)} updated successfully`);
    } catch (err) {
      console.error('Failed to upload answer image:', err);
    }
  };

  window.addAnswerImage = window.changeAnswerImage;

  window.removeAnswerImage = async function (questionId, option) {
    const qid = questionId || getQuestionId();
    if (!qid) {
      console.error('Unable to determine question ID');
      return;
    }

    try {
      const ok = await deleteImage(
        endpoints.answerDelete(qid, option),
        `Remove the image for option ${indexToLetter(option)}?`
      );
      if (!ok) return;
      clearAnswerImage(option);
      console.log(`Image removed for option ${indexToLetter(option)} successfully`);
    } catch (err) {
      console.error('Failed to remove answer image:', err);
    }
  };

  // ---------------------------
  // Helpers
  // ---------------------------
  function indexToLetter(i) {
    return ['A', 'B', 'C', 'D'][Number(i)] ?? `${i}`;
  }

  function rebindButtons() {
    // Image management buttons
    document.querySelectorAll('[data-action="change-question-image"]').forEach(btn => {
      btn.onclick = (e) => {
        e.preventDefault();
        window.changeQuestionImage();
      };
    });
    
    document.querySelectorAll('[data-action="remove-question-image"]').forEach(btn => {
      btn.onclick = (e) => {
        e.preventDefault();
        window.removeQuestionImage();
      };
    });
    
    document.querySelectorAll('[data-action="add-question-image"]').forEach(btn => {
      btn.onclick = (e) => {
        e.preventDefault();
        window.addQuestionImage();
      };
    });

    document.querySelectorAll('[data-action="change-answer-image"]').forEach(btn => {
      btn.onclick = (e) => {
        e.preventDefault();
        const opt = btn.getAttribute('data-option');
        window.changeAnswerImage(null, opt);
      };
    });
    
    document.querySelectorAll('[data-action="remove-answer-image"]').forEach(btn => {
      btn.onclick = (e) => {
        e.preventDefault();
        const opt = btn.getAttribute('data-option');
        window.removeAnswerImage(null, opt);
      };
    });
    
    document.querySelectorAll('[data-action="add-answer-image"]').forEach(btn => {
      btn.onclick = (e) => {
        e.preventDefault();
        const opt = btn.getAttribute('data-option');
        window.addAnswerImage(null, opt);
      };
    });

    // Rebind inline editing after any DOM changes
    bindInlineEditing();
  }

  // ---------------------------
  // Initialization
  // ---------------------------
  document.addEventListener('DOMContentLoaded', function() {
    console.log('Question inline edit script loaded');
    
    rebindButtons();
    
    const questionId = getQuestionId();
    console.log('Question ID detected:', questionId);
    
    if (questionId && !document.querySelector('[data-question-id]')) {
      const container = document.querySelector('.container-fluid') || document.body;
      container.setAttribute('data-question-id', questionId);
    }

    // Handle skill selector changes
    const skillSelector = document.querySelector('.skill-selector');
    if (skillSelector) {
      console.log('Skill selector found, adding event listener');
      skillSelector.addEventListener('change', function() {
        console.log('Skill changed to:', this.value);
        const questionId = this.getAttribute('data-id');
        const newSkillId = this.value;
        
        if (newSkillId && questionId) {
          updateQuestionField(questionId, 'skill_id', newSkillId);
        }
      });
    } else {
      console.log('Skill selector not found on this page');
    }

    // Handle difficulty selector changes
    const difficultySelector = document.querySelector('.difficulty-selector');
    if (difficultySelector) {
      console.log('Difficulty selector found, adding event listener');
      difficultySelector.addEventListener('change', function() {
        console.log('Difficulty changed to:', this.value);
        const questionId = this.getAttribute('data-id');
        const newDifficultyId = this.value;
        
        if (newDifficultyId && questionId) {
          updateQuestionField(questionId, 'difficulty_id', newDifficultyId);
        }
      });
    } else {
      console.log('Difficulty selector not found on this page');
    }

    // Handle QA status selector changes
    const qaStatusSelector = document.querySelector('.qa-status-selector');
    if (qaStatusSelector) {
      console.log('QA Status selector found, adding event listener');
      qaStatusSelector.addEventListener('change', function() {
        console.log('QA Status changed to:', this.value);
        const questionId = this.getAttribute('data-id');
        const newStatus = this.value;
        
        if (newStatus && questionId) {
          updateQuestionField(questionId, 'qa_status', newStatus);
        }
      });
    } else {
      console.log('QA Status selector not found on this page');
    }

    // Handle type selector changes
    const typeSelector = document.querySelector('.type-selector');
    if (typeSelector) {
      console.log('Type selector found, adding event listener');
      typeSelector.addEventListener('change', function() {
        console.log('Type changed to:', this.value);
        const questionId = this.getAttribute('data-id');
        const newTypeId = this.value;
        
        if (newTypeId && questionId) {
          updateQuestionField(questionId, 'type_id', newTypeId);
        }
      });
    } else {
      console.log('Type selector not found on this page');
    }

    // Handle correct answer selector changes
    const correctAnswerSelector = document.querySelector('.correct-answer-selector');
    if (correctAnswerSelector) {
      console.log('Correct answer selector found, adding event listener');
      correctAnswerSelector.addEventListener('change', function() {
        console.log('Correct answer changed to:', this.value);
        const questionId = this.getAttribute('data-id');
        const newAnswer = this.value;
        
        if (newAnswer !== '' && questionId) {
          updateQuestionField(questionId, 'correct_answer', newAnswer);
        }
      });
    } else {
      console.log('Correct answer selector not found on this page');
    }

    // Initialize inline editing
    bindInlineEditing();
  });

  // Expose functions for external use
  window.updateQuestionField = updateQuestionField;
  window.updateQuestionSkill = updateQuestionSkill;

})();