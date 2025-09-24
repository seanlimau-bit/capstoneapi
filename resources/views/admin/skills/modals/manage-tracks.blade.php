{{-- resources/views/admin/skills/modals/manage-tracks.blade.php --}}
<div class="modal fade" id="manageTracksModal" tabindex="-1" aria-labelledby="manageTracksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageTracksModalLabel">
                    <i class="fas fa-route me-2 text-primary"></i>Manage Skill Tracks
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Current Tracks -->
                <div class="mb-4">
                    <h6 class="fw-bold text-muted mb-3">
                        <i class="fas fa-link me-1"></i>CURRENTLY ASSOCIATED TRACKS ({{ $skill->tracks->count() }})
                    </h6>
                    @if($skill->tracks->count() > 0)
                        <div id="currentTracks" class="row">
                            @foreach($skill->tracks as $track)
                            <div class="col-md-6 mb-2" data-track-id="{{ $track->id }}">
                                <div class="card border-primary">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title mb-1">{{ $track->track }}</h6>
                                                <p class="card-text small text-muted mb-1">{{ strlen($track->description) > 50 ? substr($track->description, 0, 50) . '...' : $track->description }}</p>
                                                @if($track->level)
                                                    <span class="badge bg-info">Level {{ $track->level->level }}</span>
                                                @endif
                                            </div>
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="removeTrackFromModal({{ $track->id }})"
                                                    title="Remove track">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div id="noCurrentTracks" class="text-center py-3 text-muted">
                            <i class="fas fa-info-circle me-1"></i>No tracks currently associated with this skill
                        </div>
                    @endif
                </div>
                
                <hr>
                
                <!-- Add New Tracks -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-muted mb-0">
                            <i class="fas fa-plus me-1"></i>ADD TRACKS
                        </h6>
                        <div class="d-flex gap-2">
                            <input type="text" id="trackSearch" class="form-control form-control-sm" 
                                   placeholder="Search tracks..." style="width: 200px;">
                            <select id="levelFilter" class="form-select form-select-sm" style="width: 150px;">
                                <option value="">All Levels</option>
                                <option value="1">Level 1</option>
                                <option value="2">Level 2</option>
                                <option value="3">Level 3</option>
                                <option value="4">Level 4</option>
                                <option value="5">Level 5</option>
                                <option value="6">Level 6</option>
                                <option value="7">Level 7</option>
                                <option value="8">Level 8</option>
                                <option value="9">Level 9</option>
                                <option value="10">Level 10</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="availableTracks" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading tracks...</span>
                            </div>
                            <div class="mt-2 text-muted">Loading available tracks...</div>
                        </div>
                    </div>
                </div>
                
                <!-- Bulk Actions -->
                <div class="mb-3">
                    <h6 class="fw-bold text-muted mb-3">
                        <i class="fas fa-tasks me-1"></i>BULK ACTIONS
                    </h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-success btn-sm" onclick="addTracksByLevel()">
                            <i class="fas fa-layer-group me-1"></i>Add by Level
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="addTracksBySubject()">
                            <i class="fas fa-tags me-1"></i>Add by Subject
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="removeAllTracks()">
                            <i class="fas fa-times-circle me-1"></i>Remove All
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Changes are saved automatically
                    </small>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let availableTracksData = [];
let currentSkillTracks = @json($skill->tracks->pluck('id')->toArray());

// Load available tracks when modal is shown
document.getElementById('manageTracksModal').addEventListener('show.bs.modal', function() {
    loadAvailableTracks();
    setupTrackSearch();
});

function loadAvailableTracks() {
    fetch('/admin/tracks/available', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        availableTracksData = data.tracks || [];
        renderAvailableTracks();
    })
    .catch(error => {
        console.error('Error loading tracks:', error);
        document.getElementById('availableTracks').innerHTML = `
            <div class="text-center py-3 text-danger">
                <i class="fas fa-exclamation-triangle me-1"></i>
                Error loading tracks. Please try again.
            </div>
        `;
    });
}

function renderAvailableTracks(filteredTracks = null) {
    const tracks = filteredTracks || availableTracksData;
    const container = document.getElementById('availableTracks');
    
    if (tracks.length === 0) {
        container.innerHTML = `
            <div class="text-center py-3 text-muted">
                <i class="fas fa-search me-1"></i>
                No tracks found matching your criteria
            </div>
        `;
        return;
    }
    
    const tracksHtml = tracks.map(track => {
        const isAssociated = currentSkillTracks.includes(track.id);
        const buttonClass = isAssociated ? 'btn-success' : 'btn-outline-primary';
        const buttonIcon = isAssociated ? 'check' : 'plus';
        const buttonText = isAssociated ? 'Added' : 'Add';
        const disabled = isAssociated ? 'disabled' : '';
        
        return `
            <div class="d-flex align-items-center justify-content-between p-2 border-bottom" data-track-id="${track.id}">
                <div class="flex-grow-1">
                    <div class="fw-semibold">${track.track}</div>
                    <div class="small text-muted">${track.description || 'No description'}</div>
                    <div class="mt-1">
                        ${track.level ? `<span class="badge bg-info">Level ${track.level.level}</span>` : ''}
                        ${track.subject ? `<span class="badge bg-secondary">${track.subject}</span>` : ''}
                    </div>
                </div>
                <button class="btn ${buttonClass} btn-sm ms-3" 
                        onclick="toggleTrack(${track.id}, '${track.track}')" 
                        ${disabled}>
                    <i class="fas fa-${buttonIcon} me-1"></i>${buttonText}
                </button>
            </div>
        `;
    }).join('');
    
    container.innerHTML = tracksHtml;
}

function setupTrackSearch() {
    const searchInput = document.getElementById('trackSearch');
    const levelFilter = document.getElementById('levelFilter');
    
    function filterTracks() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedLevel = levelFilter.value;
        
        const filtered = availableTracksData.filter(track => {
            const matchesSearch = !searchTerm || 
                track.track.toLowerCase().includes(searchTerm) || 
                (track.description && track.description.toLowerCase().includes(searchTerm));
            
            const matchesLevel = !selectedLevel || 
                (track.level && track.level.level.toString() === selectedLevel);
            
            return matchesSearch && matchesLevel;
        });
        
        renderAvailableTracks(filtered);
    }
    
    searchInput.addEventListener('input', filterTracks);
    levelFilter.addEventListener('change', filterTracks);
}

function toggleTrack(trackId, trackName) {
    const isCurrentlyAssociated = currentSkillTracks.includes(trackId);
    
    if (isCurrentlyAssociated) {
        // Remove track
        removeTrackFromSkill(trackId);
    } else {
        // Add track
        addTrackToSkill(trackId, trackName);
    }
}

function addTrackToSkill(trackId, trackName) {
    fetch(`/admin/skills/{{ $skill->id }}/tracks`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ track_id: trackId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update local state
            currentSkillTracks.push(trackId);
            
            // Update UI
            const trackElement = document.querySelector(`[data-track-id="${trackId}"]`);
            const button = trackElement.querySelector('button');
            button.className = 'btn btn-success btn-sm ms-3';
            button.innerHTML = '<i class="fas fa-check me-1"></i>Added';
            button.disabled = true;
            
            // Add to current tracks section
            addTrackToCurrentList(trackId, trackName);
            
            showToast(`Track "${trackName}" added successfully`, 'success');
            updateTrackCount(1);
        } else {
            showToast(data.message || 'Error adding track', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error adding track', 'error');
    });
}

function removeTrackFromSkill(trackId) {
    fetch(`/admin/skills/{{ $skill->id }}/tracks/${trackId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update local state
            currentSkillTracks = currentSkillTracks.filter(id => id !== trackId);
            
            // Remove from current tracks list
            const currentTrackElement = document.querySelector(`#currentTracks [data-track-id="${trackId}"]`);
            if (currentTrackElement) {
                currentTrackElement.remove();
            }
            
            // Update available tracks button
            const availableTrackElement = document.querySelector(`#availableTracks [data-track-id="${trackId}"]`);
            if (availableTrackElement) {
                const button = availableTrackElement.querySelector('button');
                button.className = 'btn btn-outline-primary btn-sm ms-3';
                button.innerHTML = '<i class="fas fa-plus me-1"></i>Add';
                button.disabled = false;
            }
            
            showToast('Track removed successfully', 'success');
            updateTrackCount(-1);
            
            // Show/hide empty state
            if (currentSkillTracks.length === 0) {
                document.getElementById('currentTracks').style.display = 'none';
                document.getElementById('noCurrentTracks').style.display = 'block';
            }
        } else {
            showToast(data.message || 'Error removing track', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error removing track', 'error');
    });
}

function removeTrackFromModal(trackId) {
    if (confirm('Remove this track from the skill?')) {
        removeTrackFromSkill(trackId);
    }
}

function addTrackToCurrentList(trackId, trackName) {
    const track = availableTracksData.find(t => t.id === trackId);
    if (!track) return;
    
    const currentTracksContainer = document.getElementById('currentTracks');
    const noTracksMessage = document.getElementById('noCurrentTracks');
    
    // Hide empty message
    noTracksMessage.style.display = 'none';
    currentTracksContainer.style.display = 'block';
    
    // Create new track element
    const trackElement = document.createElement('div');
    trackElement.className = 'col-md-6 mb-2';
    trackElement.setAttribute('data-track-id', trackId);
    trackElement.innerHTML = `
        <div class="card border-primary">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="card-title mb-1">${track.track}</h6>
                        <p class="card-text small text-muted mb-1">${track.description ? track.description.substring(0, 50) + (track.description.length > 50 ? '...' : '') : ''}</p>
                        ${track.level ? `<span class="badge bg-info">Level ${track.level.level}</span>` : ''}
                    </div>
                    <button class="btn btn-outline-danger btn-sm" 
                            onclick="removeTrackFromModal(${trackId})"
                            title="Remove track">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    currentTracksContainer.appendChild(trackElement);
}

function addTracksByLevel() {
    const level = prompt('Enter level number (1-10):');
    if (level && level >= 1 && level <= 10) {
        const tracksToAdd = availableTracksData.filter(track => 
            track.level && track.level.level.toString() === level && 
            !currentSkillTracks.includes(track.id)
        );
        
        if (tracksToAdd.length === 0) {
            showToast(`No available tracks found for Level ${level}`, 'info');
            return;
        }
        
        if (confirm(`Add ${tracksToAdd.length} tracks from Level ${level}?`)) {
            tracksToAdd.forEach(track => {
                addTrackToSkill(track.id, track.track);
            });
        }
    }
}

function addTracksBySubject() {
    const subjects = [...new Set(availableTracksData.map(track => track.subject).filter(Boolean))];
    
    if (subjects.length === 0) {
        showToast('No subjects found in available tracks', 'info');
        return;
    }
    
    const subject = prompt(`Enter subject (${subjects.join(', ')}):`);
    if (subject) {
        const tracksToAdd = availableTracksData.filter(track => 
            track.subject && track.subject.toLowerCase() === subject.toLowerCase() && 
            !currentSkillTracks.includes(track.id)
        );
        
        if (tracksToAdd.length === 0) {
            showToast(`No available tracks found for subject "${subject}"`, 'info');
            return;
        }
        
        if (confirm(`Add ${tracksToAdd.length} tracks from "${subject}" subject?`)) {
            tracksToAdd.forEach(track => {
                addTrackToSkill(track.id, track.track);
            });
        }
    }
}

function removeAllTracks() {
    if (currentSkillTracks.length === 0) {
        showToast('No tracks to remove', 'info');
        return;
    }
    
    if (confirm(`Remove all ${currentSkillTracks.length} tracks from this skill?`)) {
        const tracksToRemove = [...currentSkillTracks];
        tracksToRemove.forEach(trackId => {
            removeTrackFromSkill(trackId);
        });
    }
}
</script>