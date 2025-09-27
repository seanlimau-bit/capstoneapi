@extends('layouts.admin')
@section('title','Skills Management')

@push('styles')
<style>
  .img-picker{position:relative;display:inline-block}
  .img-picker img{width:60px;height:46px;object-fit:cover;border:2px solid #dee2e6;border-radius:6px;cursor:pointer;transition:border-color .15s}
  .img-picker img:hover{border-color:var(--primary-color,#0d6efd)}
  .img-picker .btn-remove{position:absolute;top:-8px;right:-8px;width:24px;height:24px;padding:0;border-radius:50%;background:#dc3545;color:#fff;border:2px solid #fff;display:none}
  .img-picker:hover .btn-remove{display:block}

  .pick-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1055;overflow:auto}
  .pick-modal .content{background:#fff;margin:5% auto;padding:20px;width:92%;max-width:1000px;border-radius:10px}
  .pick-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;max-height:460px;overflow:auto;padding:10px;border:1px solid #dee2e6;border-radius:6px}
  .pick-item{border:2px solid transparent;border-radius:8px;padding:4px;transition:all .12s;cursor:pointer;outline:0}
  .pick-item:hover{border-color:var(--primary-color,#0d6efd);transform:scale(1.02)}
  .pick-item:focus{box-shadow:0 0 0 3px rgba(13,110,253,.3)}
  .pick-item.selected{border-color:var(--primary-color,#0d6efd);box-shadow:0 0 8px rgba(13,110,253,.4)}
  .pick-item img,.pick-item video{width:100%;height:110px;object-fit:cover;border-radius:6px}

  th.sortable{cursor:pointer;user-select:none}
  td [contenteditable]{outline:0}
  .ce-dirty{background:rgba(255,193,7,.15)}
  .nowrap{white-space:nowrap}
  #pageSpin{position:fixed;inset:0;background:rgba(255,255,255,.6);display:none;align-items:center;justify-content:center;z-index:1050}
  #pageSpin.show{display:flex}
</style>
@endpush

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h5 mb-1">Skills Management</h1>
      <div class="text-muted">Images: <code>public/images/skills</code>, Videos: <code>public/videos</code></div>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.skills.create') }}"><i class="fas fa-plus me-1"></i>Create New Skill</a>
  </div>

  <div class="row g-2 mb-3">
    <div class="col-md-2"><select class="form-select" id="trackFilter"><option value="">All Tracks</option></select></div>
    <div class="col-md-2"><select class="form-select" id="levelFilter"><option value="">All Levels</option></select></div>
    <div class="col-md-2"><select class="form-select" id="statusFilter"><option value="">All Status</option></select></div>
    <div class="col-md-4"><input type="search" class="form-control" id="searchInput" placeholder="Search skills, ids..."></div>
    <div class="col-md-2 d-grid"><button class="btn btn-outline-secondary" id="btnClear">Clear</button></div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table table-hover align-middle mb-0" id="grid">
        <thead class="table-light">
          <tr>
            <th class="sortable nowrap" data-sort="id">ID</th>
            <th class="nowrap">Image</th>
            <th class="nowrap">Videos</th>
            <th class="sortable" data-sort="skill">Skill</th>
            <th class="sortable" data-sort="description">Description</th>
            <th class="sortable nowrap" data-sort="user_id">User</th>
            <th class="sortable nowrap" data-sort="status_id">Status</th>
            <th class="sortable nowrap" data-sort="created_at">Created</th>
            <th class="sortable nowrap" data-sort="updated_at">Updated</th>
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

<!-- Video Picker (filesystem) -->
<div class="pick-modal" id="vidPicker" aria-modal="true" role="dialog">
  <div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Choose a Video (from /public/videos)</h5>
      <button class="btn-close" type="button" id="vidClose"></button>
    </div>
    <div class="row g-2">
      <div class="col-md-8"><input type="search" class="form-control" id="vidSearch" placeholder="Filter by filename/path..."></div>
      <div class="col-md-4 text-end">
        <button class="btn btn-outline-secondary" id="vidRefresh"><i class="fas fa-rotate me-1"></i>Refresh</button>
      </div>
    </div>
    <div class="pick-grid mt-3" id="vidGrid"></div>
    <div class="mt-3 d-flex justify-content-end gap-2">
      <button class="btn btn-secondary" id="vidCancel">Cancel</button>
      <button class="btn btn-primary" id="vidSelect">Attach</button>
    </div>
  </div>
</div>

<!-- Video Player Modal -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Preview Video</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
      <video id="playVideo" class="w-100 rounded" controls playsinline preload="metadata" style="max-height:70vh"></video>
    </div>
  </div></div>
</div>

<div id="pageSpin"><div class="spinner-border text-primary"></div></div>
@endsection

@push('scripts')
<script>
(() => {
  const CSRF=document.querySelector('meta[name="csrf-token"]')?.content;
  const $ = s=>document.querySelector(s);
  const $$= s=>Array.from(document.querySelectorAll(s));
  const esc=s=>{const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML};
  const fmtDate=v=>v?new Date(v).toLocaleString('en-GB',{year:'numeric',month:'short',day:'2-digit'}):'';
  const debounce=(fn,ms=300)=>{let t; return (...a)=>{clearTimeout(t); t=setTimeout(()=>fn(...a),ms);} };
  const toast=(m,t='info')=>window.showToast?window.showToast(m,t):console.log(`${t}: ${m}`);
  const url=(base,p={})=>{const u=new URL(base,location.origin); Object.entries(p).forEach(([k,v])=>v!==''&&v!=null&&u.searchParams.set(k,v)); return u.toString()};
  const spin={cnt:0,show(){if(++this.cnt===1) $('#pageSpin').classList.add('show')},hide(){if(this.cnt>0&&--this.cnt===0) $('#pageSpin').classList.remove('show')}};

  const api={
    list:p=>fetch(url('/admin/skills',p),{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()),
    patch:(id,body)=>fetch(`/admin/skills/${id}`,{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF},
      body:new URLSearchParams({_method:'PATCH', ...Object.fromEntries(Object.entries(body).filter(([,v])=>v!==undefined))})
    }).then(async r=>r.ok?r.json():Promise.reject((await r.json().catch(()=>({}))).message||`HTTP ${r.status}`)),
    del:id=>fetch(`/admin/skills/${id}`,{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF},
      body:new URLSearchParams({_method:'DELETE'})
    }).then(r=>r.json()),
    maps:()=>fetch('/admin/skills/maps',{headers:{Accept:'application/json'}}).then(r=>r.json()),
    // filesystem browse
    videosBrowse:(p)=>fetch(url('/admin/videos/browse',p),{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()),
    // link by path (backend will find-or-create Video row)
    linkVideo:(skillId, body)=>fetch(`/admin/skills/${skillId}/link-video`,{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF},
      body:new URLSearchParams(body)
    }).then(r=>r.json()),
    detachVideo:(skillId, videoId)=>fetch(`/admin/skills/${skillId}/videos/${videoId}`,{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF},
      body:new URLSearchParams({_method:'DELETE'})
    }).then(r=>r.json()),
  };

  const S={rows:[],page:1,pages:1,per:20,total:0,sort:'id',dir:'asc',
           maps:{tracks:{},levels:{},statuses:{}},
           images:[], imgRow:null, vidRow:null, vidsCache:[], videoModal:null};

  const el={
    tbody:$('#tbody'), pag:$('#pagination'),
    info:{s:$('#info-start'),e:$('#info-end'),t:$('#info-total')},
    filters:{track:$('#trackFilter'),level:$('#levelFilter'),status:$('#statusFilter'),search:$('#searchInput')},
    btnClear:$('#btnClear'), head:document.querySelector('#grid thead'),
    // image picker
    img:{modal:$('#imgPicker'),grid:$('#imgGrid'),search:$('#imgSearch'),select:$('#imgSelect'),remove:$('#imgRemove'),close:$('#imgClose'),cancel:$('#imgCancel')},
    // video picker
    vid:{modal:$('#vidPicker'),grid:$('#vidGrid'),search:$('#vidSearch'),select:'#vidSelect',close:$('#vidClose'),cancel:$('#vidCancel'),refresh:$('#vidRefresh')},
    videoWrap:$('#videoModal'), playVideo:$('#playVideo')
  };

  document.addEventListener('DOMContentLoaded', init);

  async function init(){
    S.videoModal = new bootstrap.Modal(el.videoWrap);
    bind();
    spin.show();
    await loadMaps();
    await load(1);
    await loadImages(); // optional if you have /admin/assets/list for images
    spin.hide();
  }

  function bind(){
    el.head.addEventListener('click',e=>{
      const th=e.target.closest('.sortable'); if(!th) return;
      const k=th.dataset.sort; S.dir=(S.sort===k&&S.dir==='asc')?'desc':'asc'; S.sort=k; drawSortIcons(); load(1);
    });

    ['track','level','status'].forEach(k=>el.filters[k].addEventListener('change',()=>load(1)));
    el.filters.search.addEventListener('input',debounce(()=>load(1),250));
    el.btnClear.addEventListener('click',()=>{el.filters.track.value=el.filters.level.value=el.filters.status.value=''; el.filters.search.value=''; S.sort='id'; S.dir='asc'; drawSortIcons(); load(1);});

    el.tbody.addEventListener('focusin',e=>{const ce=e.target.closest('[contenteditable]'); if(ce) ce.dataset.prev=ce.textContent.trim();});
    el.tbody.addEventListener('keydown',e=>{const ce=e.target.closest('[contenteditable]'); if(!ce) return; if(e.key==='Enter'){e.preventDefault();ce.blur()} if(e.key==='Escape'){ce.textContent=ce.dataset.prev||ce.textContent; ce.blur()}});
    el.tbody.addEventListener('focusout', onCellSave);

    el.tbody.addEventListener('click',e=>{
      const b=e.target.closest('[data-action]'); if(!b) return;
      const tr=b.closest('tr'); const id=+tr.dataset.id; const act=b.dataset.action;
      if(act==='view') location.href=`/admin/skills/${id}`;
      if(act==='delete') return delSkill(id);
      if(act==='pick-image') return openImgPicker(id);
      if(act==='pick-video') return openVidPicker(id);
      if(act==='play-video') return playVideo(b.dataset.url);
      if(act==='delete-video'){ const vid = +b.dataset.vid; return detachVideo(id, vid); }
    });

    // video picker
    el.vid.refresh.addEventListener('click', ()=>refreshVideoList());
    el.vid.search.addEventListener('input', debounce(()=>filterGrid(el.vid.grid, el.vid.search.value), 200));
    document.querySelector(el.vid.select).addEventListener('click', onAttachVideo);

    // close modals
    el.img.close.addEventListener('click',()=>el.img.modal.style.display='none');
    el.img.cancel.addEventListener('click',()=>el.img.modal.style.display='none');
    el.vid.close.addEventListener('click',()=>el.vid.modal.style.display='none');
    el.vid.cancel.addEventListener('click',()=>el.vid.modal.style.display='none');
  }

  async function load(page=1){
    spin.show();
    try{
      const p={
        page, per_page:S.per, sort:S.sort, direction:S.dir,
        track_id:el.filters.track.value, level_id:el.filters.level.value,
        status_id:el.filters.status.value, search:el.filters.search.value
      };
      const d=await api.list(p);
      S.rows  = d.skills || [];
      S.page  = page; S.pages = d.num_pages || 1;
      S.total = d.totals?.total ?? S.rows.length;
      render();
    }catch(e){ toast(e||'Load failed','error'); S.rows=[]; render(); }
    finally{ spin.hide(); }
  }

  async function loadMaps(){
    try{
      const d=await api.maps();
      S.maps.tracks  = d.tracks  || {};
      S.maps.levels  = d.levels  || {};
      S.maps.statuses= d.statuses|| {};
      fillSelect(el.filters.track,  S.maps.tracks);
      fillSelect(el.filters.level,  S.maps.levels);
      fillSelect(el.filters.status, S.maps.statuses);
    }catch(e){ console.warn('maps failed',e); }
  }

  async function loadImages(){
    // If you already have an assets endpoint, you can wire it here (optional)
  }

  // ---------- render grid ----------
  function render(){
    const tbody = el.tbody;
    if(!S.rows.length){
      tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4">No skills found</td></tr>`;
      drawSortIcons(); return;
    }
    tbody.innerHTML = S.rows.map(r=>{
      const id=r.id;
      const imgU='/' + (r.image||'images/site-logo.svg');
      const statusName=r.status?.status || S.maps.statuses[r.status_id] || '';
      const statusColor={active:'success',draft:'warning',inactive:'secondary'}[(statusName||'').toLowerCase()]||'secondary';
      const videos = Array.isArray(r.videos) ? r.videos : [];

      const videosHtml = videos.length
        ? videos.map(v=>{
            const url = '/' + (v.video_link||'');
            const title = v.video_title || '(untitled)';
            return `
              <div class="d-flex align-items-center gap-2 mb-1" data-vid="${v.id}">
                <button class="btn btn-sm btn-outline-primary" data-action="play-video" data-url="${esc(url)}">
                  <i class="fas fa-play me-1"></i>${esc(title)}
                </button>
                <button class="btn btn-sm btn-outline-danger" data-action="delete-video" data-vid="${v.id}" title="Detach">
                  <i class="fas fa-unlink"></i>
                </button>
              </div>`;
          }).join('')
        : `<div class="text-muted small">No videos linked</div>`;

      return `<tr data-id="${id}">
        <td class="nowrap">${esc(String(id))}</td>
        <td>
          <div class="img-picker" title="Click to change image" data-action="pick-image">
            <img src="${imgU}" alt="" onerror="this.src='/images/site-logo.svg'">
            <button class="btn-remove" data-action="pick-image" title="Change/Remove image">×</button>
          </div>
        </td>
        <td>
          <div data-role="video-box">
            ${videosHtml}
            <button class="btn btn-sm btn-outline-secondary mt-1" data-action="pick-video">
              <i class="fas fa-plus me-1"></i>Add
            </button>
          </div>
        </td>
        <td contenteditable="true" data-field="skill">${esc(r.skill)}</td>
        <td contenteditable="true" data-field="description">${esc(r.description||'')}</td>
        <td class="nowrap">${esc(String(r.user_id))}</td>
        <td class="nowrap"><span class="badge bg-${statusColor}">${esc(statusName || String(r.status_id||''))}</span></td>
        <td class="nowrap">${esc(fmtDate(r.created_at))}</td>
        <td class="nowrap">${esc(fmtDate(r.updated_at))}</td>
        <td>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-info" data-action="view" title="View"><i class="fas fa-eye"></i></button>
            <button class="btn btn-outline-danger" data-action="delete" title="Delete"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      </tr>`;
    }).join('');
    drawSortIcons();
  }

  function drawSortIcons(){
    $$('#grid thead th.sortable i').forEach(i=>i.remove());
    $$('#grid thead th.sortable').forEach(th=>{
      const i=document.createElement('i');
      i.className='fas ms-1 '+(th.dataset.sort===S.sort?(S.dir==='asc'?'fa-sort-up text-primary':'fa-sort-down text-primary'):'fa-sort text-muted');
      th.appendChild(i);
    });
  }

  function fillSelect(sel,map){
    sel.innerHTML = `<option value="">${sel===el.filters.track?'All Tracks':sel===el.filters.level?'All Levels':'All Status'}</option>`;
    Object.entries(map).forEach(([v,l])=>{
      const o=document.createElement('option'); o.value=String(v); o.textContent=l; sel.appendChild(o);
    });
  }

  async function onCellSave(e){
    const ce=e.target.closest('[contenteditable]'); if(!ce) return;
    const allowed=new Set(['skill','description']);
    const field=ce.dataset.field; if(!allowed.has(field)) return;
    const tr=ce.closest('tr'); const id=+tr.dataset.id; const val=ce.textContent.trim();
    if(val===(ce.dataset.prev||'')) return;
    ce.classList.add('ce-dirty');
    try{ spin.show(); await api.patch(id,{[field]:val}); toast('Saved','success'); }
    catch(err){ toast(err||'Save failed','error'); ce.textContent=ce.dataset.prev||ce.textContent; }
    finally{ ce.classList.remove('ce-dirty'); spin.hide(); }
  }

  // ---------- image picker (optional if you already have one) ----------
  function openImgPicker(id){
    // wire to your existing image assets list if needed
    alert('Image picker hook — wire to your assets endpoint as before');
  }

  // ---------- video picker (filesystem) ----------
  async function openVidPicker(skillId){
    S.vidRow=skillId;
    el.vid.modal.style.display='block';
    $('#vidSearch').value='';
    await refreshVideoList();
  }

  async function refreshVideoList(){
    try{
      const res=await api.videosBrowse({ limit: 2000, exclude_skills: 0 });
      S.vidsCache = res.videos || [];
      el.vid.grid.innerHTML = S.vidsCache.length
        ? S.vidsCache.map(v=>`<div class="pick-item" tabindex="0" data-path="${esc(v.path)}" data-url="${esc(v.url)}" title="${esc(v.title)}">
              <video src="${esc(v.url)}#t=0.1" muted playsinline preload="metadata"></video>
              <div class="small text-truncate mt-1">${esc(v.title)}</div>
           </div>`).join('')
        : '<div class="text-muted">No videos found in /public/videos</div>';

      el.vid.grid.addEventListener('click',e=>{
        const cell=e.target.closest('.pick-item'); if(!cell) return;
        $$('#vidGrid .pick-item').forEach(x=>x.classList.remove('selected'));
        cell.classList.add('selected'); cell.focus();
      }, { once:true });
    }catch{ el.vid.grid.innerHTML='<div class="text-muted">Failed to load</div>'; }
  }

  function filterGrid(container,q){
    const s=(q||'').toLowerCase();
    container.querySelectorAll('.pick-item').forEach(el=>{
      const t=(el.getAttribute('title')||'')+' '+(el.dataset.path||'');
      el.style.display = t.toLowerCase().includes(s) ? '' : 'none';
    });
  }

  async function onAttachVideo(){
    const sel=$('#vidGrid .pick-item.selected'); if(!sel||!S.vidRow) return;
    try{
      spin.show();
      // link by path; backend finds/creates Video row and attaches
      await api.linkVideo(S.vidRow, { path: sel.dataset.path, status_id: 1 });
      toast('Video linked','success');
      el.vid.modal.style.display='none';
      await load(S.page);
    }catch(e){ toast(e||'Attach failed','error'); }
    finally{ spin.hide(); }
  }

  async function detachVideo(skillId, videoId){
    if(!confirm('Detach this video from the skill?')) return;
    try{ spin.show(); await api.detachVideo(skillId, videoId); toast('Video detached','success'); await load(S.page); }
    catch(e){ toast(e||'Detach failed','error'); }
    finally{ spin.hide(); }
  }

  function playVideo(url){
    if(!url){ toast('No video URL','warning'); return; }
    el.playVideo.src=url+(url.includes('#')?'':'#t=0.1');
    S.videoModal.show();
    el.videoWrap.addEventListener('hidden.bs.modal',()=>{try{el.playVideo.pause()}catch{} el.playVideo.removeAttribute('src'); el.playVideo.load();},{once:true});
  }
})();
</script>
@endpush
