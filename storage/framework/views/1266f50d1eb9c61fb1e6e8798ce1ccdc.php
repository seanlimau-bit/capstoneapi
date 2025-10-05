
<?php $__env->startSection('title', 'Skills Management'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .img-picker{position:relative;display:inline-block}
    .img-picker img,.img-picker video{cursor:pointer;border:2px solid #dee2e6;border-radius:6px;transition:border-color .15s;object-fit:cover}
    .img-picker img:hover,.img-picker video:hover{border-color:var(--primary-color,#0d6efd)}
    .img-picker .btn-remove{position:absolute;top:-8px;right:-8px;width:24px;height:24px;padding:0;border-radius:50%;background:#dc3545;color:#fff;border:2px solid #fff;display:none}
    .img-picker:hover .btn-remove{display:block}
    .img-upload-zone{border:2px dashed #dee2e6;border-radius:8px;padding:1rem;text-align:center;cursor:pointer;transition:all .2s}
    .img-upload-zone:hover{border-color:var(--primary-color,#0d6efd);background:#f8f9fa}
    .img-upload-zone.dragging{border-color:var(--primary-color,#0d6efd);background:#e7f1ff}

    .pick-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1055;overflow:auto}
    .pick-modal .content{background:#fff;margin:5% auto;padding:20px;width:92%;max-width:1000px;border-radius:10px}
    .pick-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;max-height:460px;overflow:auto;padding:10px;border:1px solid #dee2e6;border-radius:6px}
    .pick-grid img,.pick-grid video{width:100%;height:110px;object-fit:cover;cursor:pointer;border:2px solid transparent;border-radius:8px;transition:all .12s}
    .pick-grid img:hover,.pick-grid video:hover{border-color:var(--primary-color,#0d6efd);transform:scale(1.02)}
    .pick-grid .selected{border-color:var(--primary-color,#0d6efd);box-shadow:0 0 8px rgba(13,110,253,.45)}
    
    th.sortable{cursor:pointer;user-select:none}
    td [contenteditable]{outline:0}
    .ce-dirty{background:rgba(255,193,7,.15)}
    .nowrap{white-space:nowrap}
    .track-stack .item{display:block;margin:0 0 4px 0}
    .loading-wrap{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2.5rem 0}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h5 mb-1">Skills Management</h1>
            <div class="text-muted">Upload images & attach videos</div>
        </div>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="fas fa-plus me-1"></i>Create New Skill
        </button>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <select class="form-select" id="trackFilter">
                <option value="">All Tracks</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="levelFilter">
                <option value="">All Levels</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="statusFilter">
                <option value="">All Status</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <input type="search" class="form-control" id="searchInput" placeholder="Search skills...">
                <button class="btn btn-outline-secondary" id="btnClear">Clear</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div id="loadingSkills" class="loading-wrap d-none">
                <div class="spinner-border text-primary"></div>
                <div class="mt-2 text-muted small">Loading skills…</div>
            </div>
            <table class="table table-hover align-middle mb-0" id="grid">
                <thead class="table-light">
                    <tr>
                        <th class="sortable nowrap" data-sort="id">ID</th>
                        <th class="nowrap">Image</th>
                        <th class="nowrap">Videos</th>
                        <th class="sortable" data-sort="skill">Skill</th>
                        <th class="sortable" data-sort="description">Description</th>
                        <th class="sortable nowrap" data-sort="tracks">Tracks</th>
                        <th class="sortable nowrap" data-sort="questions_count">Questions</th>
                        <th class="sortable nowrap" data-sort="status_id">Status</th>
                        <th class="sortable nowrap" data-sort="created_at">Created</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody id="tbody"></tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between small">
            <span>Showing <span id="info-start">0</span>–<span id="info-end">0</span> of <span id="info-total">0</span></span>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<!-- Create Skill Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="createForm">
            <div class="modal-header">
                <h5 class="modal-title">Create Skill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Skill <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="skill" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2"></textarea>
                </div>
                <div class="mt-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status_id" id="createStatus"></select>
                </div>
                <div class="mt-2">
                    <label class="form-label">Image (optional)</label>
                    <input type="file" class="form-control" id="createImageFile" accept="image/*">
                    <div class="form-text">Max 500KB. Recommended: 600x400px PNG or WebP</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit" id="btnCreate">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Image Upload Modal -->
<div class="pick-modal" id="imgUploadModal" aria-modal="true" role="dialog">
    <div class="content" style="max-width:600px">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Upload Skill Image</h5>
            <button class="btn-close" type="button" id="imgUploadClose"></button>
        </div>
        
        <div class="img-upload-zone" id="uploadZone">
            <input type="file" id="imgFileInput" accept="image/*" style="display:none">
            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
            <p class="mb-1">Click to browse or drag & drop</p>
            <small class="text-muted">PNG, JPG, WebP • Max 500KB • Recommended 600x400px</small>
        </div>
        
        <div id="uploadPreview" class="mt-3 d-none">
            <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height:200px">
            <div class="mt-2">
                <small class="text-muted" id="fileInfo"></small>
            </div>
        </div>
        
        <div class="progress mt-3 d-none" id="uploadProgress">
            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%" id="uploadProgressBar"></div>
        </div>
        
        <div class="mt-3 d-flex justify-content-between gap-2">
            <button class="btn btn-outline-danger" id="imgRemoveBtn">Remove Current Image</button>
            <div>
                <button class="btn btn-secondary" id="imgUploadCancel">Cancel</button>
                <button class="btn btn-primary" id="imgUploadBtn" disabled>Upload</button>
            </div>
        </div>
    </div>
</div>

<!-- Video Picker Modal -->
<div class="pick-modal" id="vidPicker" aria-modal="true" role="dialog">
    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Select Skill Video</h5>
            <button class="btn-close" type="button" id="vidClose"></button>
        </div>
        <div class="row g-2">
            <div class="col-md-8"><input type="search" class="form-control" id="vidSearch" placeholder="Search videos..."></div>
            <div class="col-md-4 text-end">
                <button class="btn btn-outline-danger" id="vidRemove" disabled title="Detach from list below"><i class="fas fa-trash me-1"></i>Remove Video</button>
            </div>
        </div>
        <div class="pick-grid mt-3" id="vidGrid"></div>
        <div class="mt-3 d-flex justify-content-end gap-2">
            <button class="btn btn-secondary" id="vidCancel">Cancel</button>
            <button class="btn btn-primary" id="vidSelect">Attach</button>
        </div>
    </div>
</div>

<!-- Generate Questions Modal -->
<div class="modal fade" id="genModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Questions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="genSkillId">
                <div class="alert alert-info">
                    <strong>Skill:</strong> <span id="genSkillName"></span><br>
                    <small>Current questions: <span id="genCurrentQ">0</span></small>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Number of questions</label>
                        <select class="form-select" id="genCount">
                            <option>5</option><option selected>10</option><option>15</option><option>20</option><option>25</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Difficulty</label>
                        <select class="form-select" id="genDiff">
                            <option value="auto">Auto (Mixed)</option>
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Additional instructions</label>
                    <textarea class="form-control" id="genInstr" rows="2" placeholder="Optional..."></textarea>
                </div>
                <div class="progress mt-3 d-none" id="genProgress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnGenerate">Generate</button>
            </div>
        </div>
    </div>
</div>

<!-- Video Player Modal -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <video id="playVideo" class="w-100 rounded" controls playsinline preload="metadata" style="max-height:70vh"></video>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    window.__skills = <?php echo json_encode($skills, 15, 512) ?>;
    window.__maps   = <?php echo json_encode($maps, 15, 512) ?>;
</script>

<script>
    (() => {
        // ---------- Constants ----------
        const VID_PREFIX = 'videos/';

        // ---------- Utilities ----------
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
        const $ = s => document.querySelector(s);
        const $$ = s => Array.from(document.querySelectorAll(s));
        const esc = s => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
        const enc = x => JSON.stringify(x || {});
        const fmtDate = v => v ? new Date(v).toLocaleString('en-GB', { year: 'numeric', month: 'short', day: '2-digit' }) : '';
        const debounce = (fn, ms = 300) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };
        const toast = (m, t = 'info') => (window.showToast ? window.showToast(m, t) : console.log(`${t}: ${m}`));
        const headers = (method = 'GET') => {
            const h = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
            if (method !== 'GET') { h['Content-Type'] = 'application/json'; if (CSRF) h['X-CSRF-TOKEN'] = CSRF; }
            return h;
        };
        const req = (url, method = 'GET', body = null) =>
            fetch(url, { method, headers: headers(method), body: body && enc(body) })
            .then(async r => r.ok ? r.json() : Promise.reject((await r.json().catch(() => ({}))).message || `HTTP ${r.status}`));
        const webUrl = p => {
            if (!p) return '/images/site-logo.svg';
            
            // If it's already a full URL, use it
            if (p.startsWith('http://') || p.startsWith('https://')) return p;
            
            // If it starts with /, use as-is
            if (p.startsWith('/')) return p;
            
            // Storage paths (skills, questions, profiles, etc.)
            if (p.startsWith('skills/') || p.startsWith('questions/') || 
                p.startsWith('profiles/') || p.startsWith('logos/') || 
                p.startsWith('backgrounds/') || p.startsWith('favicons/')) {
                const segments = p.split('/').map(seg => encodeURIComponent(seg));
                return '/storage/' + segments.join('/');
            }
            
            // Legacy paths (images/, videos/)
            const segments = p.split('/').map(seg => encodeURIComponent(seg));
            return '/' + segments.join('/');
        };
        
        // ---------- API ----------
        const api = {
            create: (body) => req('/admin/skills', 'POST', body),
            show: (id) => req(`/admin/skills/${id}/data`),
            patch: (id, body) => req(`/admin/skills/${id}`, 'PATCH', body),
            del: (id) => req(`/admin/skills/${id}`, 'DELETE'),
            gen: (body) => req('/admin/questions/generate', 'POST', body),
            assets: (p) => req(new URL('/admin/assets/list', location.origin).toString() + '?' + new URLSearchParams(p)),
            uploadImage: (file, skillId) => {
                const fd = new FormData();
                fd.append('image', file);
                fd.append('type', 'skill_image');
                fd.append('skill_id', skillId);
                return fetch('/admin/upload/image', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                }).then(r => r.ok ? r.json() : Promise.reject('Upload failed'));
            },
            linkVideo: (skillId, video_link) => req(`/admin/skills/${skillId}/link-video`, 'POST', { video_link }),
            deleteVideo: (skillId, videoId) => req(`/admin/skills/${skillId}/videos/${videoId}`, 'DELETE'),
        };

        // ---------- State ----------
        const S = {
            all: [], filtered: [], rows: [],
            page: 1, pages: 1, per: 20, total: 0,
            sort: 'id', dir: 'asc',
            maps: { tracks: {}, levels: {}, statuses: {} },
            videos: [],
            currentSkillId: null,
            currentVidId: null,
            selectedFile: null,
            genModal: null,
            videoModal: null
        };

        // ---------- Elements ----------
        const el = {
            tbody: $('#tbody'), pag: $('#pagination'),
            info: { s: $('#info-start'), e: $('#info-end'), t: $('#info-total') },
            filters: { track: $('#trackFilter'), level: $('#levelFilter'), status: $('#statusFilter'), search: $('#searchInput') },
            btnClear: $('#btnClear'), head: document.querySelector('#grid thead'),
            loading: $('#loadingSkills'),
            // upload modal
            uploadModal: $('#imgUploadModal'),
            uploadZone: $('#uploadZone'),
            fileInput: $('#imgFileInput'),
            uploadPreview: $('#uploadPreview'),
            previewImg: $('#previewImg'),
            fileInfo: $('#fileInfo'),
            uploadProgress: $('#uploadProgress'),
            uploadProgressBar: $('#uploadProgressBar'),
            uploadBtn: $('#imgUploadBtn'),
            uploadClose: $('#imgUploadClose'),
            uploadCancel: $('#imgUploadCancel'),
            imgRemoveBtn: $('#imgRemoveBtn'),
            // video picker
            vid: { modal: $('#vidPicker'), grid: $('#vidGrid'), search: $('#vidSearch'), select: $('#vidSelect'), remove: $('#vidRemove'), close: $('#vidClose'), cancel: $('#vidCancel') },
            // generate modal
            gen: { wrap: $('#genModal'), id: $('#genSkillId'), name: $('#genSkillName'), currentQ: $('#genCurrentQ'), count: $('#genCount'), diff: $('#genDiff'), instr: $('#genInstr'), prog: $('#genProgress'), bar: $('#genProgress .progress-bar'), btn: $('#btnGenerate') },
            // video player
            videoWrap: $('#videoModal'), playVideo: $('#playVideo'),
        };

        document.addEventListener('DOMContentLoaded', init);

        async function init() {
            S.genModal = new bootstrap.Modal(el.gen.wrap);
            S.videoModal = new bootstrap.Modal(el.videoWrap);
            S.all  = Array.isArray(window.__skills) ? window.__skills : [];
            S.maps = Object.assign(S.maps, window.__maps || {});

            fillSelect(el.filters.track, S.maps.tracks);
            fillSelect(el.filters.status, S.maps.statuses);
            populateLevelDescriptions();

            bind();
            showLoading(true);
            await loadVideos();
            applyAndRender(1);
            showLoading(false);
        }

        // ---------- Bind ----------
        function bind() {
            // sort
            el.head.addEventListener('click', e => {
                const th = e.target.closest('.sortable'); if (!th) return;
                const k = th.dataset.sort;
                S.dir = (S.sort === k && S.dir === 'asc') ? 'desc' : 'asc';
                S.sort = k; drawSortIcons(); reflow();
            });

            // filters
            ['track', 'status'].forEach(k => el.filters[k].addEventListener('change', () => { showLoading(true); reflow(); showLoading(false); }));
            el.filters.level.addEventListener('change', () => { showLoading(true); reflow(); showLoading(false); });
            el.filters.search.addEventListener('input', debounce(() => { showLoading(true); reflow(); showLoading(false); }, 250));

            // clear
            el.btnClear.addEventListener('click', () => {
                el.filters.track.value = el.filters.level.value = el.filters.status.value = '';
                el.filters.search.value = ''; S.sort = 'id'; S.dir = 'asc'; drawSortIcons();
                showLoading(true); applyAndRender(1); showLoading(false);
            });

            // inline edit
            el.tbody.addEventListener('focusin', e => { const ce = e.target.closest('[contenteditable]'); if (ce) ce.dataset.prev = ce.textContent.trim(); });
            el.tbody.addEventListener('keydown', e => { const ce = e.target.closest('[contenteditable]'); if (!ce) return; if (e.key === 'Enter') { e.preventDefault(); ce.blur() } if (e.key === 'Escape') { ce.textContent = ce.dataset.prev || ce.textContent; ce.blur() } });
            el.tbody.addEventListener('focusout', onCellSave);

            // table actions
            el.tbody.addEventListener('click', e => {
                const act = e.target.closest('[data-action]'); if (!act) return;
                const tr = act.closest('tr'); const id = +tr.dataset.id;

                if (act.dataset.action === 'view') location.href = `/admin/skills/${id}`;
                if (act.dataset.action === 'delete') delSkill(id);
                if (act.dataset.action === 'upload-image') openUploadModal(id);
                if (act.dataset.action === 'pick-video') openVidPicker(id);
                if (act.dataset.action === 'generate') openGenModal(id);
                if (act.dataset.action === 'play-video') playVideo(act.dataset.url);
                if (act.dataset.action === 'delete-video') {
                    const videoId = act.dataset.videoId;
                    if (videoId) delVideo(id, videoId, act.closest('[data-video-item]'));
                }
            });

            // paginate
            el.pag.addEventListener('click', e => {
                const a = e.target.closest('a[data-page]'); if (!a) return; e.preventDefault();
                const p = +a.dataset.page; if (p && p !== S.page) { showLoading(true); applyAndRender(p); showLoading(false); }
            });

            // upload modal
            el.uploadZone.addEventListener('click', () => el.fileInput.click());
            el.fileInput.addEventListener('change', handleFileSelect);
            
            // drag and drop
            el.uploadZone.addEventListener('dragover', e => { e.preventDefault(); el.uploadZone.classList.add('dragging'); });
            el.uploadZone.addEventListener('dragleave', () => el.uploadZone.classList.remove('dragging'));
            el.uploadZone.addEventListener('drop', e => {
                e.preventDefault();
                el.uploadZone.classList.remove('dragging');
                if (e.dataTransfer.files.length) {
                    el.fileInput.files = e.dataTransfer.files;
                    handleFileSelect();
                }
            });
            
            el.uploadBtn.addEventListener('click', handleUpload);
            el.uploadClose.addEventListener('click', closeUploadModal);
            el.uploadCancel.addEventListener('click', closeUploadModal);
            el.imgRemoveBtn.addEventListener('click', handleRemoveImage);

            // video picker
            const closeVid = () => { el.vid.modal.style.display = 'none'; S.currentVidId = null; $$('#vidGrid .selected').forEach(x => x.classList.remove('selected')); el.vid.search.value = ''; };
            el.vid.close.addEventListener('click', closeVid);
            el.vid.cancel.addEventListener('click', closeVid);
            el.vid.search.addEventListener('input', debounce(() => filterGrid(el.vid.grid, el.vid.search.value), 200));
            el.vid.grid.addEventListener('click', e => { const cell = e.target.closest('[data-path]'); if (!cell) return; $$('#vidGrid .selected').forEach(x => x.classList.remove('selected')); cell.classList.add('selected'); });

            el.vid.select.addEventListener('click', async () => {
                const sel = el.vid.grid.querySelector('.selected'); if (!sel || !S.currentVidId) return;
                const skillId = S.currentVidId, filename = sel.dataset.path;
                try {
                    const res = await api.linkVideo(skillId, filename);
                    const vidObj = res?.video || res;
                    const videoId = vidObj?.id || Date.now();
                    const videoLink = vidObj?.video_link || filename;
                    toast('Video attached', 'success');

                    try {
                        const d = await api.show(skillId);
                        const fresh = d?.skill;
                        if (fresh) {
                            const idx = S.all.findIndex(x => x.id === skillId);
                            if (idx > -1) S.all[idx] = Object.assign({}, S.all[idx], fresh);
                        } else {
                            const tr = document.querySelector(`tr[data-id="${skillId}"]`);
                            tr?.querySelector('[data-role="video-list"]')?.insertAdjacentHTML('beforeend', renderVideoItemHtml(videoId, videoLink));
                        }
                    } catch {}

                } catch (err) {
                    toast(err || 'Attach failed', 'error');
                } finally {
                    closeVid();
                    reflow();
                }
            });

            el.vid.remove.addEventListener('click', () => toast('Select a video below to remove', 'info'));

            // generate
            el.gen.btn.addEventListener('click', onGenerate);

            // create form
            const createForm = $('#createForm');
            if (createForm) {
                fillSelect($('#createStatus'), S.maps.statuses);
                createForm.addEventListener('submit', handleCreate);
            }
        }

        // ---------- Level dropdown ----------
        function populateLevelDescriptions(preserve=false){
            const sel = el.filters.level;
            const prev = preserve ? sel.value : '';
            const set = new Set();
            (S.all || []).forEach(s => (s.tracks || []).forEach(t => {
                const d = t.level?.description?.trim();
                if (d) set.add(d);
            }));
            const opts = Array.from(set).sort((a,b)=>a.localeCompare(b));
            sel.innerHTML = '<option value="">All Levels</option>';
            opts.forEach(desc => {
                const o = document.createElement('option');
                o.value = desc;
                o.textContent = desc;
                sel.appendChild(o);
            });
            if (preserve && prev && opts.includes(prev)) sel.value = prev;
        }

        // ---------- Load Videos ----------
        async function loadVideos() {
            const per = 1000;
            try {
                const dv = await api.assets({ per_page: per, type: 'video' });
                const vids = (dv.assets || []).filter(a => (a.path || '').startsWith(VID_PREFIX));
                S.videos = vids.map(a => ({ path: a.path, url: a.url || a.web_url || a.public_url || ('/' + a.path), name: a.name || a.path.split('/').pop() }));
            } catch { S.videos = []; }
        }

        // ---------- Upload Modal ----------
        function openUploadModal(skillId) {
            S.currentSkillId = skillId;
            S.selectedFile = null;
            el.fileInput.value = '';
            el.uploadPreview.classList.add('d-none');
            el.uploadProgress.classList.add('d-none');
            el.uploadBtn.disabled = true;
            el.uploadModal.style.display = 'block';
        }

        function closeUploadModal() {
            el.uploadModal.style.display = 'none';
            S.currentSkillId = null;
            S.selectedFile = null;
        }

        function handleFileSelect() {
            const file = el.fileInput.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                toast('Please select an image file', 'error');
                el.fileInput.value = '';
                return;
            }

            const maxSize = 500 * 1024;
            if (file.size > maxSize) {
                toast(`File too large. Maximum size: ${maxSize / 1024}KB`, 'error');
                el.fileInput.value = '';
                return;
            }

            S.selectedFile = file;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                el.previewImg.src = e.target.result;
                el.uploadPreview.classList.remove('d-none');
                el.fileInfo.textContent = `${file.name} (${(file.size / 1024).toFixed(1)}KB)`;
                el.uploadBtn.disabled = false;
            };
            reader.readAsDataURL(file);
        }

        async function handleUpload() {
            if (!S.selectedFile || !S.currentSkillId) return;

            el.uploadBtn.disabled = true;
            el.uploadProgress.classList.remove('d-none');
            
            let progress = 0;
            const interval = setInterval(() => {
                progress = Math.min(90, progress + 10);
                el.uploadProgressBar.style.width = progress + '%';
            }, 200);

            try {
                const result = await api.uploadImage(S.selectedFile, S.currentSkillId);
                clearInterval(interval);
                el.uploadProgressBar.style.width = '100%';
                
                const skill = S.all.find(s => s.id === S.currentSkillId);
                if (skill) skill.image = result.path || result.url;
                
                toast('Image uploaded successfully', 'success');
                setTimeout(() => {
                    closeUploadModal();
                    reflow();
                }, 500);
            } catch (err) {
                clearInterval(interval);
                toast(err || 'Upload failed', 'error');
                el.uploadBtn.disabled = false;
            }
        }

        async function handleRemoveImage() {
            if (!S.currentSkillId) return;
            if (!confirm('Remove the current image?')) return;

            try {
                await api.patch(S.currentSkillId, { image: null });
                const skill = S.all.find(s => s.id === S.currentSkillId);
                if (skill) skill.image = null;
                toast('Image removed', 'success');
                closeUploadModal();
                reflow();
            } catch (err) {
                toast(err || 'Failed to remove image', 'error');
            }
        }

        // ---------- Video Picker ----------
        function openVidPicker(id) {
            S.currentVidId = id;
            el.vid.grid.innerHTML = S.videos.map(f => `
              <div data-path="${esc(f.path)}" title="${esc(f.name)}">
              <video src="${esc(f.url)}#t=0.1" muted playsinline preload="metadata"></video>
              <div class="small text-truncate mt-1">${esc(f.name)}</div>
              </div>`).join('');
            el.vid.modal.style.display = 'block';
        }

        function filterGrid(container, q) {
            const s = (q || '').toLowerCase();
            container.querySelectorAll('[data-path]').forEach(el => {
                el.style.display = el.dataset.path.toLowerCase().includes(s) ? '' : 'none';
            });
        }

        function playVideo(url) {
            if (!url) { toast('No video URL', 'warning'); return; }
            el.playVideo.src = url + (url.includes('#') ? '' : '#t=0.1');
            S.videoModal.show();
            el.videoWrap.addEventListener('hidden.bs.modal', () => { try { el.playVideo.pause(); } catch { } el.playVideo.removeAttribute('src'); el.playVideo.load(); }, { once: true });
        }

        async function delVideo(skillId, videoId, node) {
            if (!confirm('Detach this video from the skill?')) return;
            try {
                await api.deleteVideo(skillId, videoId);
                node?.remove();
                const rec = S.all.find(x => x.id === skillId);
                if (rec && Array.isArray(rec.videos)) rec.videos = rec.videos.filter(v => String(v.id) !== String(videoId));
                toast('Video detached', 'success');
            } catch (err) {
                toast(err || 'Detach failed', 'error');
            }
        }

        // ---------- Create Form ----------
        async function handleCreate(e) {
            e.preventDefault();
            const fd = new FormData(e.target);
            const body = Object.fromEntries(fd.entries());
            
            const btn = $('#btnCreate');
            btn.disabled = true;
            const restore = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creating...';

            try {
                const res = await api.create(body);
                const newSkill = res?.skill || res;
                if (!newSkill?.id) throw new Error('Create failed');

                const imageFile = $('#createImageFile').files[0];
                if (imageFile) {
                    try {
                        const imgResult = await api.uploadImage(imageFile, newSkill.id);
                        newSkill.image = imgResult.path || imgResult.url;
                    } catch (err) {
                        toast('Skill created but image upload failed', 'warning');
                    }
                }

                try {
                    const fresh = await api.show(newSkill.id);
                    const row = fresh?.skill || newSkill;
                    S.all = S.all.filter(x => x.id !== row.id);
                    S.all.unshift(Object.assign({ questions_count: 0, videos: [], tracks: [] }, row));
                } catch {
                    S.all = S.all.filter(x => x.id !== newSkill.id);
                    S.all.unshift(Object.assign({ questions_count: 0, videos: [], tracks: [] }, newSkill));
                }

                populateLevelDescriptions(true);
                S.sort = 'created_at'; S.dir = 'desc';
                applyAndRender(1);

                bootstrap.Modal.getInstance($('#createModal'))?.hide();
                e.target.reset();
                toast('Skill created', 'success');
            } catch (err) {
                toast(err || 'Create failed', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = restore;
            }
        }

        // ---------- Filter/Sort/Paginate ----------
        function normalizeStr(v){ return (v ?? '').toString().toLowerCase(); }
        function skillTrackIds(r){ return Array.isArray(r.tracks) ? r.tracks.map(t => t.id) : []; }

        function passesSearch(r, q){
            if(!q) return true;
            const s = q.toLowerCase();
            const trackNames = (r.tracks || []).map(t => (t.track || '')).join(' ');
            const levelDescs = (r.tracks || []).map(t => (t.level?.description || '')).join(' ');
            const statusName = r.status?.status || '';
            return (
                (r.id+'').includes(s) ||
                normalizeStr(r.skill).includes(s) ||
                normalizeStr(r.description).includes(s) ||
                normalizeStr(trackNames).includes(s) ||
                normalizeStr(levelDescs).includes(s) ||
                normalizeStr(statusName).includes(s)
            );
        }

        function applyFilters(){
            const tId   = el.filters.track.value;
            const lDesc = el.filters.level.value.trim().toLowerCase();
            const stId  = el.filters.status.value;
            const q     = el.filters.search.value.trim();

            S.filtered = S.all.filter(r => {
                const tids = skillTrackIds(r).map(String);
                const trackOk  = !tId  || tids.includes(String(tId));
                const levelOk  = !lDesc || (r.tracks || []).some(t => (t.level?.description || '').toLowerCase() === lDesc);
                const statusOk = !stId || String(r.status_id) === String(stId);
                const searchOk = passesSearch(r, q);
                return trackOk && levelOk && statusOk && searchOk;
            });
        }

        function applySort(){
            const k = S.sort, asc = S.dir === 'asc' ? 1 : -1;
            const getVal = (r) => {
                switch(k){
                    case 'skill': case 'description': return normalizeStr(r[k]);
                    case 'tracks': {
                        const first = (r.tracks || []).map(t => t.track || '').sort()[0] || '';
                        return first.toLowerCase();
                    }
                    case 'status_id': return (r.status?.status ?? r.status_id ?? '').toString().toLowerCase();
                    case 'questions_count': return r.questions_count ?? 0;
                    default: return r[k];
                }
            };
            S.filtered.sort((a,b)=>{
                const A = getVal(a), B = getVal(b);
                if (A == null && B == null) return 0;
                if (A == null) return -asc;
                if (B == null) return  asc;
                if (A < B) return -asc;
                if (A > B) return  asc;
                return (a.id < b.id ? -1 : 1);
            });
        }

        function applyPagination(page=1){
            S.page = page;
            S.total = S.filtered.length;
            S.pages = Math.max(1, Math.ceil(S.total / S.per));
            const start = (S.page - 1) * S.per;
            const end   = start + S.per;
            S.rows = S.filtered.slice(start, end);
        }

        function applyAndRender(page=1){
            renderLoading();
            applyFilters();
            applySort();
            applyPagination(page);
            render();
        }

        function reflow(){ applyAndRender(1); }

        // ---------- Render ----------
        function showLoading(yes){ el.loading.classList.toggle('d-none', !yes); }
        function renderLoading() {
            showLoading(true);
            el.tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4">
            <div class="spinner-border text-primary"></div><div class="mt-2">Loading...</div></td></tr>`;
        }

        function render() {
            if (!S.rows.length) {
                el.tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4">No skills found</td></tr>`;
                paginate(); drawSortIcons(); showLoading(false); return;
            }
            el.tbody.innerHTML = S.rows.map(r => {
                const id = r.id;
                const imgU = webUrl(r.image || '');
                const statusName = r.status?.status || S.maps.statuses?.[r.status_id] || '';
                const statusColor = { active: 'success', draft: 'warning', inactive: 'secondary' }[(statusName || '').toLowerCase()] || 'secondary';
                const vids = Array.isArray(r.videos) ? r.videos : [];
                const trackHtml = (r.tracks || []).map(t => {
                    const lvl = t.level?.description ? ` <small class="text-muted">(${esc(t.level.description)})</small>` : '';
                    const label = esc(t.track || S.maps.tracks?.[t.id] || ('#'+t.id));
                    return `<span class="item">${label}${lvl}</span>`;
                }).join('');

                return `<tr data-id="${id}">
                <td class="nowrap">${esc(String(id))}</td>
                <td>
                <div class="img-picker" title="Click to upload image" data-action="upload-image">
                <img src="${imgU}" width="60" height="46" alt="" onerror="this.src='/images/site-logo.svg'">
                <button class="btn-remove" data-action="upload-image" title="Upload/Change image">↑</button>
                </div>
                </td>
                <td style="min-width:220px">
                <div class="img-picker mb-2" title="Click to attach video" data-action="pick-video" style="width:90px;height:46px">
                <div class="d-flex align-items-center justify-content-center bg-light rounded px-2" style="width:90px;height:46px">
                <span class="small text-muted">+ Video</span>
                </div>
                <button class="btn-remove" data-action="pick-video" title="Attach video">+</button>
                </div>
                <div class="d-flex flex-column gap-1" data-role="video-list">
                ${vids.map(v => renderVideoItemHtml(v.id, v.video_link)).join('')}
                </div>
                </td>
                <td contenteditable="true" data-field="skill">${esc(r.skill)}</td>
                <td contenteditable="true" data-field="description">${esc(r.description || '')}</td>
                <td class="nowrap" style="max-width:340px"><div class="track-stack">${trackHtml || '<span class="text-muted">—</span>'}</div></td>
                <td class="nowrap">${esc(String(r.questions_count || 0))}</td>
                <td class="nowrap"><span class="badge bg-${statusColor}">${esc(statusName || String(r.status_id || ''))}</span></td>
                <td class="nowrap">${esc(fmtDate(r.created_at))}</td>
                <td>
                <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-info" data-action="view" title="View"><i class="fas fa-eye"></i></button>
                <button class="btn btn-outline-success" data-action="generate" title="Generate Questions"><i class="fas fa-wand-magic-sparkles"></i></button>
                <button class="btn btn-outline-danger" data-action="delete" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
                </td>
                </tr>`;
            }).join('');
            paginate(); drawSortIcons(); showLoading(false);
        }

        function renderVideoItemHtml(videoId, videoLink) {
            const url = webUrl(videoLink);
            const short = esc(videoLink);
            return `<div class="d-flex align-items-center gap-2" data-video-item>
            <button class="btn btn-sm btn-outline-primary" data-action="play-video" data-url="${esc(url)}"><i class="fas fa-play me-1"></i>Play</button>
            <small class="text-muted text-truncate" style="max-width:160px">${short}</small>
            <button class="btn btn-sm btn-outline-danger" data-action="delete-video" data-video-id="${esc(String(videoId))}" title="Detach"><i class="fas fa-trash"></i></button>
            </div>`;
        }

        function paginate() {
            const make = (label, page, disabled = false, active = false) =>
            `<li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}">
            <a class="page-link" href="#" data-page="${page || ''}">${label}</a>
            </li>`;
            const p = S.page, n = S.pages, items = [];
            items.push(make('«', p - 1, p <= 1));
            const s = Math.max(1, p - 2), e = Math.min(n, p + 2);
            if (s > 1) { items.push(make('1', 1)); if (s > 2) items.push(make('…', null, true)); }
            for (let i = s; i <= e; i++) items.push(make(String(i), i, false, i === p));
            if (e < n) { if (e < n - 1) items.push(make('…', null, true)); items.push(make(String(n), n)); }
            items.push(make('»', p + 1, p >= n));
            el.pag.innerHTML = items.join('');
            const start = S.total ? (p - 1) * S.per + 1 : 0, end = S.total ? Math.min(p * S.per, S.total) : 0;
            el.info.s.textContent = start; el.info.e.textContent = end; el.info.t.textContent = S.total;
        }

        function drawSortIcons() {
            $$('#grid thead th.sortable i').forEach(i => i.remove());
            $$('#grid thead th.sortable').forEach(th => {
                const i = document.createElement('i');
                i.className = 'fas ms-1 ' + (th.dataset.sort === S.sort ? (S.dir === 'asc' ? 'fa-sort-up text-primary' : 'fa-sort-down text-primary') : 'fa-sort text-muted');
                th.appendChild(i);
            });
        }

        function fillSelect(sel, map) {
            Object.entries(map || {}).forEach(([v, l]) => { const o = document.createElement('option'); o.value = String(v); o.textContent = l; sel.appendChild(o); });
        }

        // ---------- Inline Save ----------
        async function onCellSave(e) {
            const ce = e.target.closest('[contenteditable]'); if (!ce) return;
            const allowed = new Set(['skill', 'description']);
            const field = ce.dataset.field; if (!allowed.has(field)) return;
            const tr = ce.closest('tr'); const id = +tr.dataset.id; const val = ce.textContent.trim();
            if (val === (ce.dataset.prev || '')) return;
            ce.classList.add('ce-dirty');
            try {
                await api.patch(id, { [field]: val });
                const rec = S.all.find(x => x.id === id);
                if (rec) rec[field] = val;
                toast('Saved', 'success');
            } catch (err) {
                toast(err || 'Save failed', 'error'); ce.textContent = ce.dataset.prev || ce.textContent;
            } finally { ce.classList.remove('ce-dirty'); }
        }

        // ---------- Generate ----------
        async function openGenModal(id) {
            try {
                const d = await api.show(id);
                const skill = d?.skill || d;
                if (!skill?.id) return toast('Failed to load skill', 'error');
                el.gen.id.value = skill.id;
                el.gen.name.textContent = skill.skill;
                el.gen.currentQ.textContent = skill.questions_count || 0;
                el.gen.count.value = '10';
                el.gen.diff.value = 'auto';
                el.gen.instr.value = '';
                el.gen.bar.style.width = '0%';
                el.gen.prog.classList.add('d-none');
                S.genModal.show();
            } catch (e) { toast(e || 'Failed to load skill', 'error'); }
        }
        
        async function onGenerate() {
            const btn = el.gen.btn;
            btn.disabled = true;
            const restore = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

            el.gen.prog.classList.remove('d-none');
            let pct = 0;
            const iv = setInterval(() => { pct = Math.min(90, pct + 8); el.gen.bar.style.width = pct + '%'; }, 250);

            try {
                const body = {
                    skill_id: el.gen.id.value,
                    question_count: el.gen.count.value,
                    difficulty_distribution: el.gen.diff.value,
                    focus_areas: el.gen.instr.value,
                    generation_method: 'ai',
                    include_explanations: true
                };
                const res = await api.gen(body);
                if (!res.success) throw new Error(res.message || 'Generation failed');
                el.gen.bar.style.width = '100%';
                toast(`${res.generated_count || el.gen.count.value} questions generated!`, 'success');

                const id = +el.gen.id.value;
                try {
                    const d = await api.show(id);
                    const idx = S.all.findIndex(x => x.id === id);
                    if (idx > -1 && d?.skill) S.all[idx] = Object.assign({}, S.all[idx], d.skill);
                } catch {}

                setTimeout(() => { S.genModal.hide(); reflow(); }, 400);
            } catch (e) {
                toast(e.message || 'Generation failed', 'error');
            } finally {
                clearInterval(iv);
                btn.disabled = false;
                btn.innerHTML = restore;
                setTimeout(() => { el.gen.prog.classList.add('d-none'); el.gen.bar.style.width = '0%'; }, 400);
            }
        }

        // ---------- Delete skill ----------
        async function delSkill(id) {
            if (!confirm('Delete this skill?')) return;
            try { await api.del(id); S.all = S.all.filter(x => x.id !== id); reflow(); toast('Skill deleted', 'success'); }
            catch (e) { toast(e || 'Delete failed', 'error'); }
        }
    })();
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\skills\index.blade.php ENDPATH**/ ?>