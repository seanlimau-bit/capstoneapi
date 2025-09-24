@extends('layouts.admin')
@section('title', 'Asset Manager')

@push('styles')
<style>
  .asset-toolbar{display:flex;gap:var(--spacing-md);align-items:center;flex-wrap:wrap;background:var(--surface-color);padding:var(--spacing-md);border-radius:var(--border-radius);box-shadow:var(--shadow-sm);margin-bottom:var(--spacing-lg)}
  .filter-btn{padding:var(--spacing-sm) var(--spacing-md);border:1px solid var(--outline);background:transparent;color:var(--on-surface-variant);border-radius:var(--border-radius-sm);cursor:pointer;transition:all var(--transition-fast);font-size:var(--font-size-sm)}
  .filter-btn:hover{color:var(--primary-color);border-color:var(--primary-color)}
  .filter-btn.active{background:var(--primary-color);color:var(--on-primary);border-color:var(--primary-color)}
  .view-toggle{display:flex;border:1px solid var(--outline);border-radius:var(--border-radius-sm);overflow:hidden}
  .view-toggle button{background:none;border:0;padding:var(--spacing-sm) var(--spacing-md);color:var(--on-surface-variant)}
  .view-toggle button.active{background:var(--primary-color);color:var(--on-primary)}

  .upload-zone{border:2px dashed var(--outline);border-radius:var(--border-radius);padding:var(--spacing-2xl);text-align:center;background:var(--surface-container);transition:all var(--transition);cursor:pointer;margin-bottom:var(--spacing-lg)}
  .upload-zone:hover,.upload-zone.dragover{border-color:var(--primary-color);background-color:rgba(150,0,0,.04)}
  .upload-zone i{font-size:3rem;color:var(--on-surface-variant);margin-bottom:var(--spacing-md)}

  .asset-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:var(--spacing-md)}
  .asset-item{background:var(--surface-color);border-radius:var(--border-radius);overflow:hidden;box-shadow:var(--shadow-sm);transition:all var(--transition);position:relative;border:1px solid var(--outline-variant)}
  .asset-item:hover{box-shadow:var(--shadow-md);transform:translateY(-1px)}
  .asset-preview{height:150px;background:var(--surface-container);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
  .asset-preview img,.asset-preview video{width:100%;height:100%;object-fit:cover;display:block}
  .asset-preview .play-badge{position:absolute;right:6px;bottom:6px;background:rgba(0,0,0,.55);color:#fff;padding:2px 6px;font-size:.7rem;border-radius:6px}
  .asset-info{padding:var(--spacing-md)}
  .asset-name{font-weight:500;color:var(--on-surface);font-size:var(--font-size-sm);margin-bottom:4px;word-break:break-word;line-height:1.3}
  .asset-meta{font-size:var(--font-size-xs);color:var(--on-surface-variant);display:flex;justify-content:space-between;align-items:center;gap:8px}
  .asset-actions{position:absolute;top:8px;right:8px;display:flex;gap:6px}

  .asset-grid.list-view{grid-template-columns:1fr;gap:var(--spacing-sm)}
  .asset-grid.list-view .asset-item{display:flex;align-items:center;padding:var(--spacing-sm)}
  .asset-grid.list-view .asset-preview{height:60px;width:84px;flex-shrink:0;margin-right:var(--spacing-md)}
  .asset-grid.list-view .asset-info{flex:1;padding:0}

  .empty-state{text-align:center;padding:var(--spacing-2xl);color:var(--on-surface-variant)}
  .empty-state i{font-size:4rem;margin-bottom:var(--spacing-lg);opacity:.5}

  .upload-progress{position:fixed;bottom:20px;right:20px;background:var(--surface-color);padding:var(--spacing-md);border-radius:var(--border-radius);box-shadow:var(--shadow-lg);min-width:300px;z-index:1050}
  .progress-item{display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--spacing-sm)}
  .progress-item:last-child{margin-bottom:0}
</style>
@endpush

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h4 mb-1">Asset Manager</h1>
      <div class="text-muted">Files from <code>public/</code> and <code>storage/app/public/assets</code></div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary" onclick="createFolder()">
        <i class="fas fa-folder-plus me-1"></i> New Folder
      </button>
      <button class="btn btn-primary" onclick="document.getElementById('fileUpload').click()">
        <i class="fas fa-upload me-1"></i> Upload Files
      </button>
    </div>
  </div>

  <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileUpload').click()">
    <i class="fas fa-cloud-upload-alt"></i>
    <div class="h5 mb-2">Drop files here or click to upload</div>
    <p class="text-muted mb-0">Images, videos, documents, audio, archives, animations</p>
  </div>

  <div class="asset-toolbar">
    <div class="d-flex gap-2 flex-wrap" id="typeFilters">
      <button class="filter-btn active" data-type="all" onclick="setType('all')">All</button>
      <button class="filter-btn" data-type="image" onclick="setType('image')">Images</button>
      <button class="filter-btn" data-type="video" onclick="setType('video')">Videos</button>
      <button class="filter-btn" data-type="document" onclick="setType('document')">Docs</button>
      <button class="filter-btn" data-type="audio" onclick="setType('audio')">Audio</button>
      <button class="filter-btn" data-type="archive" onclick="setType('archive')">Archives</button>
      <button class="filter-btn" data-type="animation" onclick="setType('animation')">Animations</button>
    </div>
    <div class="ms-auto view-toggle" id="viewToggle">
      <button class="active" data-view="grid" onclick="setView(this)"><i class="fas fa-th"></i></button>
      <button data-view="list" onclick="setView(this)"><i class="fas fa-list"></i></button>
    </div>
  </div>

  <div class="asset-grid" id="assetGrid"></div>

  <div class="text-center my-3" id="pagerWrap" style="display:none;">
    <button class="btn btn-outline-secondary" id="loadMoreBtn" onclick="loadMore()">Load more</button>
  </div>

  <div class="empty-state d-none" id="emptyState">
    <i class="fas fa-folder-open"></i>
    <h5>No files found</h5>
    <p class="text-muted">Try another type filter or upload new files</p>
    <button class="btn btn-primary" onclick="document.getElementById('fileUpload').click()">
      <i class="fas fa-upload me-1"></i> Upload Files
    </button>
  </div>

  <input type="file" id="fileUpload" multiple class="d-none" onchange="handleFileUpload(this)">
</div>

{{-- Upload progress --}}
<div class="upload-progress d-none" id="uploadProgress">
  <h6 class="mb-2"><i class="fas fa-upload me-2"></i>Uploading Files</h6>
  <div id="progressList"></div>
</div>

{{-- Preview modal --}}
<div class="modal fade" id="assetModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="p-3 bg-light rounded text-center">
              <img id="previewImage" class="img-fluid rounded d-none" alt="">
              <video id="previewVideo" class="w-100 rounded d-none" controls preload="metadata" playsinline style="max-height:360px"></video>
              <div id="previewIcon" class="d-none"><i class="fas fa-file fa-4x text-muted"></i></div>
            </div>
          </div>
          <div class="col-md-6">
            <table class="table table-borderless mb-3">
              <tr><td class="fw-semibold">Name:</td><td id="modalName">-</td></tr>
              <tr><td class="fw-semibold">Type:</td><td><span id="modalType" class="badge bg-secondary">-</span></td></tr>
              <tr><td class="fw-semibold">Size:</td><td id="modalSize">-</td></tr>
              <tr><td class="fw-semibold">Modified:</td><td id="modalDate">-</td></tr>
              <tr><td class="fw-semibold">Dimensions:</td><td id="modalDimensions">-</td></tr>
              <tr><td class="fw-semibold">Path:</td><td id="modalPath" class="text-break">-</td></tr>
            </table>
            <div class="input-group">
              <input type="text" id="assetUrl" class="form-control form-control-sm" readonly>
              <button class="btn btn-outline-secondary btn-sm" onclick="copyUrl()" title="Copy URL"><i class="fas fa-copy"></i></button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button id="btnMoveVideo" type="button" class="btn btn-outline-warning d-none" onclick="promptMoveCurrent()">
          <i class="fas fa-folder-open me-1"></i> Move (hide)
        </button>
        <button id="btnDeleteImage" type="button" class="btn btn-outline-danger d-none" onclick="deleteCurrent()">
          <i class="fas fa-trash me-1"></i> Delete
        </button>
        <a id="downloadLink" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
          <i class="fas fa-download me-1"></i> Download
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
/* ======== Config / Routes ======== */
const ROUTE = {
  list:  (page, per, type) => `/admin/assets/list?page=${page}&per_page=${per}&type=${encodeURIComponent(type||'all')}`,
  info:  (idEnc) => `/admin/assets/info/${idEnc}`,
  del:   (idEnc) => `/admin/assets/${idEnc}`,
  upload:`/admin/assets/upload`,
  folder:`/admin/assets/folder`,
  move:  `/admin/assets/move`, // POST {id, target}
};
const DEFAULT_MOVE_TARGET = 'videos/_review'; // change if you prefer

/* ======== State ======== */
const state = { all: [], page: 1, per: 60, hasMore: true, loading: false, type: 'all', view: 'grid', current: null };
let assetModal;

/* ======== Init ======== */
document.addEventListener('DOMContentLoaded', () => {
  setupDnD();
  const modalEl = document.getElementById('assetModal');
  assetModal = new bootstrap.Modal(modalEl);
  modalEl.addEventListener('hide.bs.modal', stopVideoPlayback);
  modalEl.addEventListener('hidden.bs.modal', stopVideoPlayback);
  loadAssets({ reset:true });
});

/* ======== Fetch / Pagination ======== */
function loadAssets({reset=false} = {}){
  if (state.loading) return;
  if (reset) { state.page = 1; state.all = []; state.hasMore = true; }
  if (!state.hasMore) return;

  state.loading = true;
  document.getElementById('loadMoreBtn')?.setAttribute('disabled','disabled');

  fetch(ROUTE.list(state.page, state.per, state.type))
    .then(r=>r.json())
    .then(d=>{
      const items = (d.assets||[]).map(normalizeAsset);
      state.all.push(...items);
      state.hasMore = !!(d.pagination && d.pagination.has_more);
      state.page = (d.pagination?.next_page) || (state.page+1);
      render();
    })
    .catch(()=> toast('Failed to load assets','error'))
    .finally(()=>{
      state.loading = false;
      document.getElementById('loadMoreBtn')?.removeAttribute('disabled');
    });
}
function loadMore(){ loadAssets(); }

/* ======== Normalize ======== */
function normalizeAsset(a){
  const path = a.path || '';
  const name = a.name || (path.split('/').pop() || '');
  const extension = (a.extension || name.split('.').pop() || '').toLowerCase();
  const type = a.type || inferType(extension, path);
  const url = a.url || (a.type === 'folder' ? null : (a.web_url || a.public_url || null));
  const idRaw = a.id || (`webroot|${path}`);
  return {
    id: idRaw, idEnc: btoa(idRaw),
    path, name, url, type,
    size: a.size ?? 0,
    modified: a.modified ?? Date.now(),
    extension,
    dimensions: a.dimensions || null
  };
}
function inferType(ext){
  const map = {
    image: ['jpg','jpeg','png','gif','svg','webp','bmp'],
    video: ['mp4','mov','m4v','avi','mkv','webm','3gp','wmv'],
    document: ['pdf','doc','docx','txt','rtf','ppt','pptx','xls','xlsx','csv'],
    archive: ['zip','rar','7z','tar','gz'],
    audio: ['mp3','wav','ogg','aac','flac'],
    animation: ['json']
  };
  for (const [t, list] of Object.entries(map)) if (list.includes(ext)) return t;
  return 'unknown';
}

/* ======== Filters / View ======== */
function setType(t){
  state.type = t;
  document.querySelectorAll('#typeFilters .filter-btn').forEach(b=>b.classList.remove('active'));
  document.querySelector(`#typeFilters .filter-btn[data-type="${t}"]`)?.classList.add('active');
  loadAssets({reset:true});
}
function setView(btn){
  state.view = btn.dataset.view;
  document.querySelectorAll('#viewToggle button').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('assetGrid').classList.toggle('list-view', state.view==='list');
}

/* ======== Render ======== */
function render(){
  const grid = document.getElementById('assetGrid');
  const empty = document.getElementById('emptyState');
  const pager = document.getElementById('pagerWrap');

  const files = state.all.filter(a=>a.type!=='folder');
  if (!files.length){
    grid.innerHTML = '';
    empty.classList.remove('d-none');
    pager.style.display = 'none';
    return;
  }
  empty.classList.add('d-none');

  grid.innerHTML = files.map(a=>{
    const isVideo = a.type==='video';
    const isImage = a.type==='image';
    const preview = isImage && a.url
      ? `<img src="${a.url}" alt="${escapeHtml(a.name)}" loading="lazy">`
      : isVideo && a.url
        ? `<video src="${a.url}#t=0.1" muted playsinline preload="metadata"></video><span class="play-badge"><i class="fas fa-play me-1"></i>Video</span>`
        : `<i class="fas fa-file fa-3x text-muted"></i>`;

    const actions = isVideo
      ? `<button class="btn btn-sm btn-light" onclick="event.stopPropagation(); view('${a.idEnc}')"><i class="fas fa-eye"></i></button>
         <button class="btn btn-sm btn-warning" title="Move (hide)" onclick="event.stopPropagation(); promptMove('${a.idEnc}')"><i class="fas fa-folder-open"></i></button>`
      : isImage
      ? `<button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); removeAsset('${a.idEnc}')"><i class="fas fa-trash"></i></button>`
      : `<button class="btn btn-sm btn-light" onclick="event.stopPropagation(); view('${a.idEnc}')"><i class="fas fa-eye"></i></button>`;

    return `
      <div class="asset-item" onclick="view('${a.idEnc}')">
        <div class="asset-preview">${preview}</div>
        <div class="asset-info">
          <div class="asset-name" title="${escapeHtml(a.path)}">${escapeHtml(a.name)}</div>
          <div class="asset-meta">
            <span class="text-uppercase small">${a.type}</span>
            <span>${formatSize(a.size)}</span>
          </div>
        </div>
        <div class="asset-actions">${actions}</div>
      </div>`;
  }).join('');

  pager.style.display = state.hasMore ? '' : 'none';
}

/* ======== Preview / Modal ======== */
function view(idEnc){
  stopVideoPlayback();
  fetch(ROUTE.info(idEnc))
    .then(r=>r.json())
    .then(d=>{
      if (!d?.success) return toast('Failed to load info','error');
      const a = normalizeAsset(d.file || {});
      state.current = a;

      document.getElementById('modalTitle').textContent = a.name || 'Asset';
      document.getElementById('modalName').textContent = a.name || '-';
      document.getElementById('modalType').textContent = (a.type||'unknown').toUpperCase();
      document.getElementById('modalType').className = `badge bg-secondary`;
      document.getElementById('modalSize').textContent = formatSize(a.size);
      const ms = a.modified > 1e12 ? a.modified : a.modified*1000;
      document.getElementById('modalDate').textContent = a.modified ? new Date(ms).toLocaleString() : '-';
      document.getElementById('modalDimensions').textContent = a.dimensions ? `${a.dimensions.width} × ${a.dimensions.height}` : '-';
      document.getElementById('modalPath').textContent = a.path || '-';
      document.getElementById('assetUrl').value = a.url || '';

      const img = document.getElementById('previewImage');
      const vid = document.getElementById('previewVideo');
      const ico = document.getElementById('previewIcon');
      [img,vid,ico].forEach(el=>{
        el.classList.add('d-none');
        if (el.tagName==='VIDEO'){ el.removeAttribute('src'); el.load(); }
      });

      document.getElementById('btnMoveVideo').classList.toggle('d-none', a.type!=='video');
      document.getElementById('btnDeleteImage').classList.toggle('d-none', a.type!=='image');

      if (a.type==='image' && a.url){
        img.src = a.url; img.classList.remove('d-none');
      } else if (a.type==='video' && a.url){
        vid.src = a.url + '#t=0.1'; vid.classList.remove('d-none');
      } else {
        ico.classList.remove('d-none');
      }

      const dl = document.getElementById('downloadLink');
      dl.href = a.url || '#';
      dl.classList.toggle('disabled', !a.url);

      assetModal.show();
    })
    .catch(()=> toast('Failed to open preview','error'));
}
function stopVideoPlayback(){
  const vid = document.getElementById('previewVideo');
  if (!vid) return;
  try { vid.pause(); } catch(e){}
  vid.currentTime = 0;
  vid.removeAttribute('src');
  vid.load();
}

/* ======== Delete / Move ======== */
function deleteCurrent(){
  if (!state.current) return;
  if (!confirm('Delete this image?')) return;
  removeAsset(state.current.idEnc, { after: () => assetModal?.hide() });
}
function removeAsset(idEnc, {after} = {}){
  fetch(ROUTE.del(idEnc), { method:'DELETE', headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }})
    .then(r=>r.json())
    .then(d=>{
      if (!d?.success) return toast(d?.message||'Delete failed','error');
      toast('Deleted','success');
      after && after();
      loadAssets({reset:true});
    })
    .catch(()=> toast('Delete error','error'));
}
function promptMoveCurrent(){
  if (!state.current) return;
  promptMove(state.current.idEnc);
}
function promptMove(idEnc){
  const target = prompt('Move to folder (relative to webroot or storage assets):', DEFAULT_MOVE_TARGET);
  if (!target) return;
  fetch(ROUTE.move, {
    method:'POST',
    headers:{
      'Content-Type':'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ id: atob(idEnc), target })
  })
    .then(r=>r.json())
    .then(d=>{
      if (!d?.success) return toast(d?.message||'Move failed','error');
      toast('Moved','success');
      assetModal?.hide();
      loadAssets({reset:true});
    })
    .catch(()=> toast('Move error','error'));
}

/* ======== Upload ======== */
function handleFileUpload(input){
  const files = Array.from(input.files||[]);
  if (!files.length) return;
  showUploadPanel();
  files.forEach(uploadOne);
}
function uploadOne(file){
  const fd = new FormData();
  fd.append('files[]', file);
  const row = addProgressRow(file.name);
  fetch(ROUTE.upload, { method:'POST', headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: fd })
    .then(r=>r.json())
    .then(d=>{
      if (d?.success){ setProgressRow(row,'success','Uploaded'); loadAssets({reset:true}); }
      else setProgressRow(row,'error', d?.message||'Failed');
    })
    .catch(()=> setProgressRow(row,'error','Network error'));
}
function showUploadPanel(){ document.getElementById('uploadProgress').classList.remove('d-none'); }
function addProgressRow(name){
  const wrap = document.getElementById('progressList');
  const div = document.createElement('div');
  div.className='progress-item';
  div.innerHTML = `<span class="text-truncate" style="max-width:220px">${escapeHtml(name)}</span><span class="badge bg-secondary">Uploading…</span>`;
  wrap.appendChild(div); return div;
}
function setProgressRow(div,status,msg){
  const b = div.querySelector('.badge');
  b.className = `badge ${status==='success'?'bg-success':'bg-danger'}`; b.textContent = msg;
  setTimeout(()=>{ div.remove(); if (!document.getElementById('progressList').children.length){ setTimeout(()=> document.getElementById('uploadProgress').classList.add('d-none'), 700); }}, 1400);
}

/* ======== DnD ======== */
function setupDnD(){
  const zone = document.getElementById('uploadZone');
  const stop = e => { e.preventDefault(); e.stopPropagation(); };
  ['dragenter','dragover','dragleave','drop'].forEach(ev => {
    zone.addEventListener(ev, stop, false);
    document.body.addEventListener(ev, stop, false);
  });
  ['dragenter','dragover'].forEach(ev => zone.addEventListener(ev, () => zone.classList.add('dragover'), false));
  ['dragleave','drop'].forEach(ev => zone.addEventListener(ev, () => zone.classList.remove('dragover'), false));
  zone.addEventListener('drop', e => {
    const dt = e.dataTransfer;
    if (!dt?.files?.length) return;
    const input = document.getElementById('fileUpload');
    input.files = dt.files;
    handleFileUpload(input);
  }, false);
}

/* ======== Folder ======== */
function createFolder(){
  const name = prompt('Enter folder name:');
  if (!name) return;
  fetch(ROUTE.folder, {
    method:'POST',
    headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    body: JSON.stringify({ name })
  })
    .then(r=>r.json())
    .then(d=>{ if (d?.success){ toast('Folder created','success'); } else { toast(d?.message||'Failed to create folder','error'); } })
    .catch(()=> toast('Network error','error'));
}

/* ======== Utils ======== */
function formatSize(bytes){
  if (!bytes) return '—';
  const u=['B','KB','MB','GB','TB']; let i=0; let v=bytes;
  while(v>=1024 && i<u.length-1){ v/=1024;i++; }
  return `${v.toFixed(v<10 && i>0?1:0)} ${u[i]}`;
}
function copyUrl(){
  const el=document.getElementById('assetUrl'); el.select(); el.setSelectionRange(0,99999);
  navigator.clipboard.writeText(el.value||'').then(()=> toast('URL copied','success'));
}
function escapeHtml(s=''){ return s.replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
function toast(message,type='info'){
  const map={success:'alert-success',error:'alert-danger',warning:'alert-warning',info:'alert-info'};
  const n=document.createElement('div'); n.className=`alert ${map[type]||map.info} position-fixed top-0 end-0 m-3 shadow`;
  n.innerHTML=message; document.body.appendChild(n); setTimeout(()=> n.remove(),3000);
}
</script>
@endpush
