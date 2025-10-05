

<?php $__env->startSection('title', 'Questions Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    
    <?php echo $__env->make('admin.components.page-header', [
    'title' => isset($skill) ? 'Questions for: ' . $skill->skill : 'Questions Management',
    'subtitle' => isset($skill) ? 'Manage questions for this specific skill' : 'Manage all questions in the system',
    'breadcrumbs' => [
    ['title' => 'Dashboard', 'url' => url('/admin')],
    ['title' => 'Questions']
    ],
    'actions' => [
    [
    'text' => 'Create New Question',
    'url' => route('admin.questions.create') . (isset($skill) ? '?skill_id=' . $skill->id : ''),
    'icon' => 'plus',
    'class' => 'primary'
    ],
    [
    'type' => 'dropdown',
    'class' => 'secondary',
    'icon' => 'ellipsis-v',
    'text' => 'Actions',
    'items' => [
    ['icon' => 'download', 'text' => 'Export Questions', 'onclick' => 'exportQuestions()'],
    ['icon' => 'upload', 'text' => 'Import Questions', 'onclick' => 'importQuestions()'],
    ['icon' => 'copy', 'text' => 'Bulk Duplicate Selected', 'onclick' => 'bulkDuplicate()', 'id' => 'dropdownBulkDuplicate'],
    ['icon' => 'trash', 'text' => 'Bulk Delete Selected', 'onclick' => 'bulkDelete()', 'id' => 'dropdownBulkDelete'],
    ['icon' => 'sync', 'text' => 'Refresh', 'onclick' => 'refreshData()']
    ]
    ]
    ]
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php echo $__env->make('admin.components.stats-row', [
    'stats' => [
    [
    'value' => 'Loading...',
    'label' => 'Total Questions',
    'color' => 'primary',
    'icon' => 'question-circle',
    'id' => 'totalQuestionsCount'
    ],
    [
    'value' => '0',
    'label' => 'Approved',
    'color' => 'success',
    'icon' => 'check-circle',
    'id' => 'approvedCount'
    ],
    [
    'value' => '0',
    'label' => 'Pending Review',
    'color' => 'warning',
    'icon' => 'clock',
    'id' => 'pendingCount'
    ],
    [
    'value' => '0',
    'label' => 'Flagged',
    'color' => 'danger',
    'icon' => 'flag',
    'id' => 'flaggedCount'
    ]
    ]
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php $__env->startComponent('admin.components.filters-card', ['items' => []]); ?>
    <div class="col-md-2">
        <select class="form-select" id="fieldFilter" data-populate="fields">
            <option value="">All Fields</option>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="skillFilter" data-populate="skills">
            <option value="">All Skills</option>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="difficultyFilter" data-populate="difficulties">
            <option value="">All Difficulties</option>
        </select>
    </div>
    <div class="col-md-2">
       <select class="form-select" id="statusFilter" data-populate="statuses" name="status_id">
        <option value="">All Status</option>
    </select>
</div>
<div class="col-md-2">
    <select class="form-select" id="qaStatusFilter" data-populate="qa-statuses">
        <option value="">All QA Status</option>
    </select>
</div>
<div class="col-md-2">
    <select class="form-select" id="typeFilter" data-populate="types">
        <option value="">All Types</option>
    </select>
</div>
<div class="col-md-4">
    <input type="search" class="form-control" id="searchInput" placeholder="Search questions...">
</div>
<?php echo $__env->renderComponent(); ?>


<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-question-circle me-2"></i>Questions List
                    </h5>

                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll()">
                            <label class="form-check-label" for="selectAll">
                                <small>Select All</small>
                            </label>
                        </div>

                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="bulkDuplicate()" disabled id="bulkDuplicateBtn">
                                <i class="fas fa-copy me-1"></i>Duplicate Selected
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="bulkDelete()" disabled id="bulkDeleteBtn">
                                <i class="fas fa-trash me-1"></i>Delete Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="selectAllHeader">
                                    </div>
                                </th>
                                <th style="cursor: pointer;" onclick="sortTable('question')">
                                    <div class="d-flex align-items-center">
                                        Question Details
                                        <i class="fas fa-sort ms-1 text-muted sort-icon" id="sort-question"></i>
                                    </div>
                                </th>
                                <th style="cursor: pointer;" onclick="sortTable('skill_id')">
                                    <div class="d-flex align-items-center">
                                        Skill & Difficulty
                                        <i class="fas fa-sort ms-1 text-muted sort-icon" id="sort-skill_id"></i>
                                    </div>
                                </th>
                                <th style="cursor: pointer;" onclick="sortTable('qa_status')">
                                    <div class="d-flex align-items-center">
                                        QA Status
                                        <i class="fas fa-sort ms-1 text-muted sort-icon" id="sort-qa_status"></i>
                                    </div>
                                </th>
                                <th style="cursor: pointer;" onclick="sortTable('source')">
                                    <div class="d-flex align-items-center">
                                        Source
                                        <i class="fas fa-sort ms-1 text-muted sort-icon" id="sort-source"></i>
                                    </div>
                                </th>
                                <th style="cursor: pointer;" onclick="sortTable('created_at')">
                                    <div class="d-flex align-items-center">
                                        Created
                                        <i class="fas fa-sort ms-1 text-muted sort-icon" id="sort-created_at"></i>
                                    </div>
                                </th>
                                <th width="150" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="questionsTableBody">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="mt-2">Loading questions...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Showing <span id="showing-start">0</span> to <span id="showing-end">0</span> 
                        of <span id="total-records">0</span> entries
                    </div>

                    <nav aria-label="Questions pagination">
                        <ul class="pagination pagination-sm mb-0" id="pagination">
                            
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
let questionsData = [];
let currentPage = 1;
let totalPages = 1;
let totalQuestions = 0;
let currentSortField = '';
let currentSortDirection = 'asc';

// Sort functionality for Source column
function sortTable(field) {
    if (!field) return;
    
    // Toggle direction if same field, otherwise start with asc
    if (currentSortField === field) {
        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortDirection = 'asc';
    }
    
    currentSortField = field;
    
    // Update sort icons
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.className = 'fas fa-sort ms-1 text-muted sort-icon';
    });
    
    const currentIcon = document.getElementById(`sort-${field}`);
    if (currentIcon) {
        currentIcon.className = `fas fa-sort-${currentSortDirection === 'asc' ? 'up' : 'down'} ms-1 text-primary sort-icon`;
    }
    
    // Reload questions with sort
    loadQuestions(1);
}

document.addEventListener('DOMContentLoaded', function() {
    loadQuestions();
    setupQuestionsFilters();
    
    // Populate global dropdowns after a small delay to ensure DOM is ready
    setTimeout(function() {
        if (typeof populateGlobalDropdowns === 'function') {
            populateGlobalDropdowns();
        }
    }, 100);
});

function loadQuestions(page = 1) {
    const tbody = document.getElementById('questionsTableBody');
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><div class="mt-2">Loading questions...</div></td></tr>';
    
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    
    // Add filter parameters
    const filters = getActiveFilters();
    Object.entries(filters).forEach(([key, value]) => {
        if (value) url.searchParams.set(key, value);
    });
    
    // Add sort parameters if sorting is active
    if (currentSortField) {
        url.searchParams.set('sort', currentSortField);
        url.searchParams.set('direction', currentSortDirection);
    }
    
    fetch(url.toString(), {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        questionsData = data.questions;
        currentPage = page;
        totalPages = data.num_pages || 1;
        totalQuestions = data.totals ? data.totals.total : data.questions.length;
        
        renderQuestionsTable(questionsData);
        updatePagination();
        updateStatistics(data.totals);
    })
    .catch(error => {
        console.error('Error loading questions:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading questions. Please refresh the page.</td></tr>';
    });
}

function getActiveFilters() {
    return {
        field_id: document.getElementById('fieldFilter')?.value || '',
        skill_id: document.getElementById('skillFilter')?.value || '',
        difficulty_id: document.getElementById('difficultyFilter')?.value || '',
        status_id: document.getElementById('statusFilter')?.value || '',
        qa_status: document.getElementById('qaStatusFilter')?.value || '',
        type_id: document.getElementById('typeFilter')?.value || '',
        search: document.getElementById('searchInput')?.value || ''
    };
}

function setupQuestionsFilters() {
    // Add filter event listeners
    ['fieldFilter', 'skillFilter', 'difficultyFilter', 'statusFilter', 'qaStatusFilter', 'typeFilter', 'searchInput'].forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('change', () => loadQuestions(1));
            if (filterId === 'searchInput') {
                let searchTimeout;
                element.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => loadQuestions(1), 500);
                });
            }
        }
    });
}

function renderQuestionsTable(questions) {
    const tbody = document.getElementById('questionsTableBody');
    
    if (!questions || questions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><i class="fas fa-search me-2"></i>No questions found</td></tr>';
        return;
    }
    
    tbody.innerHTML = questions.map(question => `
        <tr class="question-row" data-question-id="${question.id}">
        <td>
        <div class="form-check">
        <input type="checkbox" value="${question.id}" class="form-check-input question-checkbox">
        </div>
        </td>
        <td>
        <div class="question-details">
        <div class="fw-semibold mb-1">${truncateText(question.question || '', 60)}</div>
        ${question.correct_answer ? `<small class="text-success">Answer: ${truncateText(question.correct_answer, 40)}</small>` : ''}
        <div class="mt-1">
        <small class="text-muted">ID: ${question.id}</small>
        ${question.type ? `<span class="badge bg-info ms-2">${question.type.description || question.type.type}</span>` : ''}
        </div>
        </div>
        </td>
        <td>
        ${renderQuestionSkillAndDifficulty(question)}
        </td>
        <td>
        ${renderQAStatus(question.qa_status)}
        </td>
        <td>
        <small class="text-muted">${question.source || 'Unknown'}</small>
        </td>
        <td>
        ${formatDate(question.created_at)}
        </td>
        <td>
        <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-info" onclick="viewQuestion(${question.id})" title="View">
        <i class="fas fa-eye"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="copyQuestion(${question.id})" title="Duplicate">
        <i class="fas fa-copy"></i>
        </button>
        <button type="button" class="btn btn-outline-danger" onclick="deleteQuestion(${question.id})" title="Delete">
        <i class="fas fa-trash"></i>
        </button>
        </div>
        </td>
        </tr>
        `).join('');
}

function renderQuestionSkillAndDifficulty(question) {
    let html = '<div class="d-flex flex-column gap-1">';
    
    if (question.skill) {
        html += `<div><strong>Skill:</strong> ${truncateText(question.skill.skill || 'Unknown', 25)}</div>`;
        
        if (question.skill.tracks && question.skill.tracks.length > 0) {
            html += `<div class="d-flex flex-wrap gap-1">`;
            question.skill.tracks.slice(0, 2).forEach(track => {
                html += `<span class="badge bg-secondary" title="${track.track || ''}">
                ${truncateText(track.track || '', 15)}
                ${track.level ? ` (L${track.level.level})` : ''}
                </span>`;
            });
            if (question.skill.tracks.length > 2) {
                html += `<span class="badge bg-light text-dark">+${question.skill.tracks.length - 2}</span>`;
            }
            html += `</div>`;
        }
    } else {
        html += '<span class="text-muted">No skill assigned</span>';
    }
    
    if (question.difficulty) {
        const difficultyText = question.difficulty.short_description || question.difficulty.description || 'Unknown';
        const badgeClass = difficultyText.toLowerCase().includes('easy') ? 'bg-success' : 
        (difficultyText.toLowerCase().includes('medium') ? 'bg-warning' : 'bg-danger');
        html += `<div><span class="badge ${badgeClass}">${difficultyText}</span></div>`;
    } else {
        html += '<div><span class="badge bg-secondary">No difficulty set</span></div>';
    }
    
    html += '</div>';
    return html;
}

function renderQAStatus(status) {
    if (!status) return '<span class="badge bg-secondary">Unknown</span>';
    
    const statusConfig = {
        'approved': { class: 'success', icon: 'check-circle' },
        'flagged': { class: 'danger', icon: 'flag' },
        'needs_revision': { class: 'warning', icon: 'edit' },
        'unreviewed': { class: 'info', icon: 'clock' },
        'ai_generated': { class: 'primary', icon: 'robot' }
    };
    
    const config = statusConfig[status] || { class: 'secondary', icon: 'question' };
    const label = status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
    
    return `<span class="badge bg-${config.class}"><i class="fas fa-${config.icon} me-1"></i>${label}</span>`;
}

function renderQuestionAuthor(author, createdAt) {
    const authorName = author && author.name ? author.name : 'Unknown';
    const authorInitial = authorName.charAt(0);
    
    return `
    <div class="d-flex align-items-center">
    ${author && author.image ? 
        `<img src="${author.image}" alt="${authorName}" class="rounded-circle me-2" width="32" height="32">` :
        `<div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 12px;">
        ${authorInitial}
        </div>`
    }
    <div>
    <div class="fw-bold small">${authorName}</div>
    <div class="text-muted small">${formatDate(createdAt)}</div>
    </div>
    </div>
    `;
}

function updatePagination() {
    const paginationContainer = document.getElementById('pagination');
    if (!paginationContainer) return;
    
    let paginationHtml = '';
    
    if (currentPage > 1) {
        paginationHtml += `
        <li class="page-item">
        <a class="page-link" href="#" onclick="loadQuestions(${currentPage - 1}); return false;">
        <i class="fas fa-chevron-left"></i> Previous
        </a>
        </li>
        `;
    } else {
        paginationHtml += `
        <li class="page-item disabled">
        <span class="page-link">
        <i class="fas fa-chevron-left"></i> Previous
        </span>
        </li>
        `;
    }
    
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        paginationHtml += `
        <li class="page-item">
        <a class="page-link" href="#" onclick="loadQuestions(1); return false;">1</a>
        </li>
        `;
        if (startPage > 2) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
        <li class="page-item ${i === currentPage ? 'active' : ''}">
        <a class="page-link" href="#" onclick="loadQuestions(${i}); return false;">${i}</a>
        </li>
        `;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHtml += `
        <li class="page-item">
        <a class="page-link" href="#" onclick="loadQuestions(${totalPages}); return false;">${totalPages}</a>
        </li>
        `;
    }
    
    if (currentPage < totalPages) {
        paginationHtml += `
        <li class="page-item">
        <a class="page-link" href="#" onclick="loadQuestions(${currentPage + 1}); return false;">
        Next <i class="fas fa-chevron-right"></i>
        </a>
        </li>
        `;
    } else {
        paginationHtml += `
        <li class="page-item disabled">
        <span class="page-link">
        Next <i class="fas fa-chevron-right"></i>
        </span>
        </li>
        `;
    }
    
    paginationContainer.innerHTML = paginationHtml;
    
    const start = (currentPage - 1) * 50 + 1;
    const end = Math.min(currentPage * 50, start + questionsData.length - 1);
    
    document.getElementById('showing-start').textContent = start;
    document.getElementById('showing-end').textContent = end;
    document.getElementById('total-records').textContent = totalQuestions;
}

function updateStatistics(totals) {
    if (totals) {
        document.getElementById('totalQuestionsCount').textContent = totals.total || 0;
        document.getElementById('approvedCount').textContent = totals.approved || 0;
        document.getElementById('pendingCount').textContent = totals.pending || 0;
        document.getElementById('flaggedCount').textContent = totals.flagged || 0;
    } else {
        const approved = questionsData.filter(q => q.qa_status === 'approved').length;
        const pending = questionsData.filter(q => q.qa_status === 'unreviewed').length;
        const flagged = questionsData.filter(q => q.qa_status === 'flagged').length;
        
        document.getElementById('totalQuestionsCount').textContent = totalQuestions;
        document.getElementById('approvedCount').textContent = approved;
        document.getElementById('pendingCount').textContent = pending;
        document.getElementById('flaggedCount').textContent = flagged;
    }
}

function truncateText(text, length) {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
}

function formatDate(dateString) {
    if (!dateString) return 'Unknown';
    return new Date(dateString).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.question-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkActionButtons();
}

function updateBulkActionButtons() {
    const selectedCheckboxes = document.querySelectorAll('.question-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkDuplicateBtn = document.getElementById('bulkDuplicateBtn');
    
    const hasSelected = selectedCheckboxes.length > 0;
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.disabled = !hasSelected;
    }
    
    if (bulkDuplicateBtn) {
        bulkDuplicateBtn.disabled = !hasSelected;
    }
}

function bulkDuplicate() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        showToast('Please select questions to duplicate', 'warning');
        return;
    }
    
    if (confirm(`Are you sure you want to duplicate ${selectedIds.length} selected questions? Copies will be created with "[COPY]" prefix.`)) {
        const bulkDuplicateBtn = document.getElementById('bulkDuplicateBtn');
        const originalText = bulkDuplicateBtn.innerHTML;
        bulkDuplicateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Duplicating...';
        bulkDuplicateBtn.disabled = true;
        
        fetch('/admin/questions/bulk-duplicate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                question_ids: selectedIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(`${selectedIds.length} questions duplicated successfully!`, 'success');
                document.getElementById('selectAll').checked = false;
                document.querySelectorAll('.question-checkbox').forEach(cb => cb.checked = false);
                loadQuestions(currentPage);
            } else {
                showToast(data.message || 'Error duplicating questions', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error duplicating questions', 'error');
        })
        .finally(() => {
            bulkDuplicateBtn.innerHTML = originalText;
            updateBulkActionButtons();
        });
    }
}

function bulkDelete() {
    const selectedIds = getSelectedIds();
    
    if (selectedIds.length === 0) {
        showToast('Please select questions to delete', 'warning');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${selectedIds.length} selected questions? This action cannot be undone.`)) {
        fetch('/admin/questions/bulk-delete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                question_ids: selectedIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Selected questions deleted successfully!', 'success');
                document.getElementById('selectAll').checked = false;
                document.querySelectorAll('.question-checkbox').forEach(cb => cb.checked = false);
                loadQuestions(currentPage);
            } else {
                showToast(data.message || 'Error deleting questions', 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showToast('Error deleting questions', 'error');
        });
    }
}

function getSelectedIds() {
    const selectedCheckboxes = document.querySelectorAll('.question-checkbox:checked');
    return Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
}

function viewQuestion(questionId) {
    window.location.href = `/admin/questions/${questionId}`;
}

function copyQuestion(questionId) {
    if (confirm('Are you sure you want to duplicate this question? A copy will be created with "[COPY]" prefix.')) {
        fetch(`/admin/questions/${questionId}/duplicate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Question duplicated successfully!', 'success');
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    loadQuestions(currentPage);
                }
            } else {
                showToast(data.message || 'Error duplicating question', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error duplicating question', 'error');
        });
    }
}

function deleteQuestion(questionId) {
    if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
        fetch(`/admin/questions/${questionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                showToast('Question deleted successfully!', 'success');
                loadQuestions(currentPage);
            } else {
                response.json().then(data => {
                    showToast(data.message || 'Error deleting question', 'error');
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error deleting question', 'error');
        });
    }
}

function exportQuestions() {
    window.location.href = '/admin/questions/export';
}

function importQuestions() {
    showToast('Import functionality coming soon', 'info');
}

function refreshData() {
    loadQuestions(currentPage);
}

function showToast(message, type = 'info') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        alert(`${type.toUpperCase()}: ${message}`);
    }
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('question-checkbox')) {
        updateBulkActionButtons();
    }
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\skills\questions-duplicate.blade.php ENDPATH**/ ?>