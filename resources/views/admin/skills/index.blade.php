@extends('layouts.admin')
@section('title', 'Skills Management')

@push('styles')
    <style>
        .img-picker{position:relative;display:inline-block}
        .img-picker img,.img-picker video{cursor:pointer;border:2px solid #dee2e6;border-radius:6px;transition:border-color .15s;object-fit:cover}
        .img-picker img:hover,.img-picker video:hover{border-color:var(--primary-color,#0d6efd)}
        .img-picker .btn-remove{position:absolute;top:-8px;right:-8px;width:24px;height:24px;padding:0;border-radius:50%;background:#dc3545;color:#fff;border:2px solid #fff;display:none}
        .img-picker:hover .btn-remove{display:block}

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

        /* stacked tracks */
        .track-stack .item{display:block;margin:0 0 4px 0}

        .loading-wrap{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2.5rem 0}
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h5 mb-1">Skills Management</h1>
                <div class="text-muted">Images from <code>/images/skills</code> & videos from <code>/videos</code></div>
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
                    <input type="search" class="form-control" id="searchInput" placeholder="Search skills, ids, tracks, levels...">
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
                        <div class="input-group">
                            <input type="text" class="form-control" name="image" id="createImage" placeholder="images/skills/xxx.png">
                            <button class="btn btn-outline-secondary" type="button" id="btnPickCreateImage">Pick</button>
                        </div>
                        <div class="form-text">Images must live under <code>public/images/skills</code>.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit" id="btnCreate">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Picker -->
    <div class="pick-modal" id="imgPicker" aria-modal="true" role="dialog">
        <div class="content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Select Skill Image</h5>
                <button class="btn-close" type="button" id="imgClose"></button>
            </div>
            <div class="row g-2">
                <div class="col-md-8"><input type="search" class="form-control" id="imgSearch" placeholder="Search images..."></div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-outline-danger" id="imgRemove"><i class="fas fa-trash me-1"></i>Remove Image</button>
                </div>
            </div>
            <div class="pick-grid mt-3" id="imgGrid"></div>
            <div class="mt-3 d-flex justify-content-end gap-2">
                <button class="btn btn-secondary" id="imgCancel">Cancel</button>
                <button class="btn btn-primary" id="imgSelect">Select</button>
            </div>
        </div>
    </div>

    <!-- Video Picker -->
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
@endsection

@push('scripts')
    {{-- Hydrate data from controller --}}
    <script>
        window.__skills = @json($skills);
        window.__maps   = @json($maps);
    </script>

    <script>
        (() => {
            // ---------- Constants ----------
            const IMG_PREFIX = 'images/skills/';
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
            const webUrl = p => p ? `/${p}` : '/images/site-logo.svg';

            // ---------- API ----------
            const api = {
                create: (body) => req('/admin/skills', 'POST', body),
                show: (id) => req(`/admin/skills/${id}/data`),
                patch: (id, body) => req(`/admin/skills/${id}`, 'PATCH', body),
                del: (id) => req(`/admin/skills/${id}`, 'DELETE'),
                gen: (body) => req('/admin/questions/generate', 'POST', body),
                assets: (p) => req(new URL('/admin/assets/list', location.origin).toString() + '?' + new URLSearchParams(p)),
                linkVideo: (skillId, video_link) => req(`/admin/skills/${skillId}/link-video`, 'POST', { video_link }),
                deleteVideo: (skillId, videoId) => req(`/admin/skills/${skillId}/videos/${videoId}`, 'DELETE'),
            };

            // ---------- State ----------
            const S = {
                all: [], filtered: [], rows: [],
                page: 1, pages: 1, per: 20, total: 0,
                sort: 'id', dir: 'asc',
                maps: { tracks: {}, levels: {}, statuses: {} },
                images: [], videos: [],
                currentImgId: null, currentVidId: null,
                genModal: null, videoModal: null
            };

            // ---------- Elements ----------
            const el = {
                tbody: $('#tbody'), pag: $('#pagination'),
                info: { s: $('#info-start'), e: $('#info-end'), t: $('#info-total') },
                filters: { track: $('#trackFilter'), level: $('#levelFilter'), status: $('#statusFilter'), search: $('#searchInput') },
                btnClear: $('#btnClear'), head: document.querySelector('#grid thead'),
                loading: $('#loadingSkills'),
                // pickers
                img: { modal: $('#imgPicker'), grid: $('#imgGrid'), search: $('#imgSearch'), select: $('#imgSelect'), remove: $('#imgRemove'), close: $('#imgClose'), cancel: $('#imgCancel') },
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

                // fill filters (track/status from maps; level from descriptions)
                fillSelect(el.filters.track,  S.maps.tracks);
                fillSelect(el.filters.status, S.maps.statuses);
                populateLevelDescriptions();

                bind();
                showLoading(true);
                await loadPickers();
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

                // inline edit (skill, description)
                el.tbody.addEventListener('focusin', e => { const ce = e.target.closest('[contenteditable]'); if (ce) ce.dataset.prev = ce.textContent.trim(); });
                el.tbody.addEventListener('keydown', e => { const ce = e.target.closest('[contenteditable]'); if (!ce) return; if (e.key === 'Enter') { e.preventDefault(); ce.blur() } if (e.key === 'Escape') { ce.textContent = ce.dataset.prev || ce.textContent; ce.blur() } });
                el.tbody.addEventListener('focusout', onCellSave);

                // table actions
                el.tbody.addEventListener('click', e => {
                    const act = e.target.closest('[data-action]'); if (!act) return;
                    const tr = act.closest('tr'); const id = +tr.dataset.id;

                    if (act.dataset.action === 'view') location.href = `/admin/skills/${id}`;
                    if (act.dataset.action === 'delete') delSkill(id);
                    if (act.dataset.action === 'pick-image') openImgPicker(id);
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

                // image picker
                const closeImg = () => { el.img.modal.style.display = 'none'; S.currentImgId = null; $$('#imgGrid .selected').forEach(x => x.classList.remove('selected')); el.img.search.value = ''; };
                el.img.close.addEventListener('click', closeImg);
                el.img.cancel.addEventListener('click', closeImg);
                el.img.search.addEventListener('input', debounce(() => filterGrid(el.img.grid, el.img.search.value), 200));
                el.img.grid.addEventListener('click', e => { const cell = e.target.closest('[data-path]'); if (!cell) return; $$('#imgGrid .selected').forEach(x => x.classList.remove('selected')); cell.classList.add('selected'); });
                el.img.select.addEventListener('click', async () => {
                    const sel = el.img.grid.querySelector('.selected'); if (!sel || !S.currentImgId) return;
                    try { await saveField(S.currentImgId, 'image', sel.dataset.path); toast('Image updated', 'success'); }
                    catch (e) { toast(e || 'Update failed', 'error'); }
                    finally { closeImg(); }
                });
                el.img.remove.addEventListener('click', async () => {
                    if (!S.currentImgId) return;
                    try { await saveField(S.currentImgId, 'image', null); toast('Image removed', 'success'); }
                    catch(e){ toast(e||'Remove failed','error'); }
                    finally { closeImg(); }
                });

                // video picker
                const closeVid = () => { el.vid.modal.style.display = 'none'; S.currentVidId = null; $$('#vidGrid .selected').forEach(x => x.classList.remove('selected')); el.vid.search.value = ''; };
                el.vid.close.addEventListener('click', closeVid);
                el.vid.cancel.addEventListener('click', closeVid);
                el.vid.search.addEventListener('input', debounce(() => filterGrid(el.vid.grid, el.vid.search.value), 200));
                el.vid.grid.addEventListener('click', e => { const cell = e.target.closest('[data-path]'); if (!cell) return; $$('#vidGrid .selected').forEach(x => x.classList.remove('selected')); cell.classList.add('selected'); });

                // attach video: toast -> close -> refresh row via AJAX -> redraw
                el.vid.select.addEventListener('click', async () => {
                    const sel = el.vid.grid.querySelector('.selected'); if (!sel || !S.currentVidId) return;
                    const skillId = S.currentVidId, filename = sel.dataset.path;
                    try {
                        const res = await api.linkVideo(skillId, filename);
                        const vidObj = res?.video || res;
                        const videoId = vidObj?.id || Date.now();
                        const videoLink = vidObj?.video_link || filename;
                        toast('Video attached', 'success');

                        // refresh row
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

                // picker remove button disabled (detach happens from list)
                el.vid.remove.addEventListener('click', () => toast('Select a video below to remove', 'info'));

                // generate
                $('#btnGenerate').addEventListener('click', onGenerate);

                // create
                const createForm = $('#createForm');
                if (createForm) {
                    fillSelect($('#createStatus'), S.maps.statuses);
                    $('#btnPickCreateImage')?.addEventListener('click', () => { S.currentImgId = '__create__'; openImgPicker('__create__'); });
                    createForm.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        const fd = new FormData(createForm);
                        const body = Object.fromEntries(fd.entries());
                        try {
                            const res = await api.create(body);
                            const newSkill = res?.skill || res;
                            if (!newSkill?.id) throw new Error('Create failed');

                            // fetch fresh row & put on top; set sort to created_at desc
                            try {
                                const fresh = await api.show(newSkill.id);
                                const row = fresh?.skill || newSkill;
                                S.all = S.all.filter(x => x.id !== row.id);
                                S.all.unshift(Object.assign({ questions_count: 0, videos: [], tracks: [] }, row));
                            } catch {
                                S.all = S.all.filter(x => x.id !== newSkill.id);
                                S.all.unshift(Object.assign({ questions_count: 0, videos: [], tracks: [] }, newSkill));
                            }
                            // repopulate Level dropdown (descriptions may change)
                            populateLevelDescriptions(true);

                            S.sort = 'created_at'; S.dir = 'desc';
                            applyAndRender(1);

                            bootstrap.Modal.getInstance($('#createModal'))?.hide();
                            createForm.reset();
                            toast('Skill created', 'success');
                        } catch (err) {
                            toast(err || 'Create failed', 'error');
                        }
                    });
                }
            }

            // ---------- Level dropdown = level.description ----------
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

            // ---------- Client filtering/sorting/paging ----------
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
                        default: return r[k]; // id, created_at
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

            // ---------- Pickers data ----------
            async function loadPickers() {
                const per = 1000;
                try {
                    const di = await api.assets({ per_page: per, type: 'image' });
                    const imgs = (di.assets || []).filter(a => (a.path || '').startsWith(IMG_PREFIX));
                    S.images = imgs.map(a => ({ path: a.path, url: a.url || a.web_url || a.public_url || ('/' + a.path), name: a.name || a.path.split('/').pop() }));
                } catch { S.images = []; }
                try {
                    const dv = await api.assets({ per_page: per, type: 'video' });
                    const vids = (dv.assets || []).filter(a => (a.path || '').startsWith(VID_PREFIX));
                    S.videos = vids.map(a => ({ path: a.path, url: a.url || a.web_url || a.public_url || ('/' + a.path), name: a.name || a.path.split('/').pop() }));
                } catch { S.videos = []; }
            }

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
                      <div class="img-picker" title="Click to change image" data-action="pick-image">
                        <img src="${imgU}" width="60" height="46" alt="" onerror="this.src='/images/site-logo.svg'">
                        <button class="btn-remove" data-action="pick-image" title="Change/Remove image">×</button>
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

            // ---------- Video actions ----------
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

            // ---------- Delete skill ----------
            async function delSkill(id) {
                if (!confirm('Delete this skill?')) return;
                try { await api.del(id); S.all = S.all.filter(x => x.id !== id); reflow(); toast('Skill deleted', 'success'); }
                catch (e) { toast(e || 'Delete failed', 'error'); }
            }

            // ---------- Helpers ----------
            function filterGrid(container, q) {
                const s = (q || '').toLowerCase();
                container.querySelectorAll('[data-path]').forEach(el => {
                    el.style.display = el.dataset.path.toLowerCase().includes(s) ? '' : 'none';
                });
            }
            async function saveField(id, field, value) {
                if (id === '__create__') { $('#createImage').value = value || ''; return; }
                await api.patch(id, { [field]: value });
                const tr = document.querySelector(`tr[data-id="${id}"]`);
                if (field === 'image') {
                    const img = tr.querySelector('[data-action="pick-image"] img');
                    img.src = webUrl(value);
                }
                const rec = S.all.find(x => x.id === id);
                if (rec) rec[field] = value;
            }
            function openImgPicker(id) {
                S.currentImgId = id;
                el.img.grid.innerHTML = S.images.map(f => `
                  <div data-path="${esc(f.path)}" title="${esc(f.name)}">
                    <img src="${esc(f.url)}" alt="${esc(f.name)}">
                    <div class="small text-truncate mt-1">${esc(f.name)}</div>
                  </div>`).join('');
                el.img.modal.style.display = 'block';
            }
            function openVidPicker(id) {
                S.currentVidId = id;
                el.vid.grid.innerHTML = S.videos.map(f => `
                  <div data-path="${esc(f.path)}" title="${esc(f.name)}">
                    <video src="${esc(f.url)}#t=0.1" muted playsinline preload="metadata"></video>
                    <div class="small text-truncate mt-1">${esc(f.name)}</div>
                  </div>`).join('');
                el.vid.modal.style.display = 'block';
            }
        })();
    </script>
@endpush
