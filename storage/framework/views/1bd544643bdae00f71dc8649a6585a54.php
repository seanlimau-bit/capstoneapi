
<?php $__env->startSection('title','Asset Manager'); ?>

<?php $__env->startPush('styles'); ?>
<style>
/* Compact, drop‑in styles */
.toolbar{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;background:var(--surface-color);padding:.75rem;border-radius:8px;border:1px solid var(--outline-variant);margin-bottom:1rem}
.btn-chip{padding:.35rem .6rem;border:1px solid var(--outline);background:transparent;border-radius:6px;cursor:pointer;font-size:.85rem}
.btn-chip.active{background:var(--primary-color);color:var(--on-primary);border-color:var(--primary-color)}
.view-toggle{margin-left:auto;border:1px solid var(--outline);border-radius:6px;overflow:hidden}
.view-toggle button{background:none;border:0;padding:.35rem .6rem}
.view-toggle .active{background:var(--primary-color);color:var(--on-primary)}
.dropzone{border:2px dashed var(--outline);border-radius:10px;padding:2rem;text-align:center;background:var(--surface-container);cursor:pointer;margin-bottom:1rem}
.dropzone.drag{border-color:var(--primary-color);background:rgba(150,0,0,.04)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.75rem}
.item{background:var(--surface-color);border:1px solid var(--outline-variant);border-radius:10px;overflow:hidden;position:relative}
.item:hover{box-shadow:var(--shadow-sm)}
.preview{height:150px;display:flex;align-items:center;justify-content:center;background:var(--surface-container)}
.preview img,.preview video{width:100%;height:100%;object-fit:cover;display:block}
.meta{padding:.6rem .7rem}
.name{font-weight:600;font-size:.9rem;line-height:1.2;word-break:break-word}
.sub{font-size:.75rem;color:var(--on-surface-variant);display:flex;justify-content:space-between}
.actions{position:absolute;top:.5rem;right:.5rem;display:flex;gap:.35rem}
.grid.list{grid-template-columns:1fr}
.grid.list .item{display:flex;align-items:center}
.grid.list .preview{width:84px;height:60px;flex:0 0 84px}
.empty{text-align:center;padding:3rem;color:var(--on-surface-variant)}
.toast{position:fixed;top:1rem;right:1rem;z-index:1060}
/* Spinner */
#spinner{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(255,255,255,.6);z-index:1055}
#spinner.show{display:flex}
#spinner .wheel{width:3rem;height:3rem;border:.35rem solid var(--outline-variant,#ddd);border-top-color:var(--primary-color,#960000);border-radius:50%;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h5 mb-1">Asset Manager</h1>
      <div class="text-muted">Files from <code>public/</code> and <code>storage/app/public/assets</code></div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary" data-action="folder"><i class="fas fa-folder-plus me-1"></i>New Folder</button>
      <button class="btn btn-primary" data-action="pick"><i class="fas fa-upload me-1"></i>Add Asset</button>
    </div>
  </div>

  <div class="dropzone" id="dz" tabindex="0">
    <i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block"></i>
    <div class="fw-semibold">Drop files here or click to upload</div>
    <small class="text-muted">Images, videos, docs, audio, archives</small>
  </div>

  <div class="toolbar">
    <div id="filters" class="d-flex flex-wrap gap-2">
      <button class="btn-chip active" data-type="all">All</button>
      <button class="btn-chip" data-type="image">Images</button>
      <button class="btn-chip" data-type="video">Videos</button>
      <button class="btn-chip" data-type="document">Docs</button>
      <button class="btn-chip" data-type="audio">Audio</button>
      <button class="btn-chip" data-type="archive">Archives</button>
      <button class="btn-chip" data-type="animation">Animations</button>
    </div>
    <div class="view-toggle" id="view">
      <button class="active" data-view="grid"><i class="fas fa-th"></i></button>
      <button data-view="list"><i class="fas fa-list"></i></button>
    </div>
  </div>

  <div class="grid" id="grid"></div>
  <div class="text-center my-3 d-none" id="pager"><button class="btn btn-outline-secondary" id="more">Load more</button></div>
  <div class="empty d-none" id="empty"><i class="fas fa-folder-open fa-3x mb-3"></i><div>No files found</div></div>

  <input type="file" id="picker" class="d-none" multiple>
</div>

<!-- Spinner overlay -->
<div id="spinner"><div class="wheel"></div></div>


<div class="modal fade" id="assetModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Asset</h5><button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <div class="row g-3">
        <div class="col-md-6"><div class="p-3 bg-light rounded text-center">
          <img id="mImg" class="img-fluid rounded d-none" alt="">
          <video id="mVid" class="w-100 rounded d-none" controls preload="metadata" playsinline style="max-height:360px"></video>
          <div id="mIco" class="d-none"><i class="fas fa-file fa-4x text-muted"></i></div>
        </div></div>
        <div class="col-md-6">
          <table class="table table-borderless table-sm mb-3">
            <tr><td class="fw-semibold">Name</td><td id="mName">-</td></tr>
            <tr><td class="fw-semibold">Type</td><td><span id="mType" class="badge bg-secondary">-</span></td></tr>
            <tr><td class="fw-semibold">Size</td><td id="mSize">-</td></tr>
            <tr><td class="fw-semibold">Modified</td><td id="mDate">-</td></tr>
            <tr><td class="fw-semibold">Dimensions</td><td id="mDim">-</td></tr>
            <tr><td class="fw-semibold">Path</td><td id="mPath" class="text-break">-</td></tr>
          </table>
          <div class="input-group input-group-sm"><input id="mUrl" class="form-control" readonly><button class="btn btn-outline-secondary" id="copy"><i class="fas fa-copy"></i></button></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline-warning d-none" id="btnMove"><i class="fas fa-folder-open me-1"></i>Move</button>
      <button class="btn btn-outline-danger d-none" id="btnDel"><i class="fas fa-trash me-1"></i>Delete</button>
      <a id="dl" class="btn btn-primary" target="_blank" rel="noopener"><i class="fas fa-download me-1"></i>Download</a>
      <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
// ===== Config =====
const ROUTE={list:(p,per,t)=>`/admin/assets/list?page=${p}&per_page=${per}&type=${encodeURIComponent(t||'all')}`,
             info:id=>`/admin/assets/info/${id}`, del:id=>`/admin/assets/${id}`,
             upload:'/admin/assets/upload', folder:'/admin/assets/folder', move:'/admin/assets/move'};
const MOVE_DEFAULT='videos/_review';
const csrf=()=>document.querySelector('meta[name="csrf-token"]').content;

// ===== State =====
const S={items:[],page:1,per:60,more:true,loading:false,type:'all',view:'grid',current:null};
let modal;

// ===== Helpers =====
const $=s=>document.querySelector(s); const $$=s=>[...document.querySelectorAll(s)];
const j=async(r)=>{if(!r.ok)throw new Error('HTTP '+r.status);return r.json()};
const req=(url,opt={})=>fetch(url,{headers:{'X-CSRF-TOKEN':csrf(),...(opt.headers||{})},...opt});
const fmt=(b)=>!b?'—':(()=>{const u=['B','KB','MB','GB','TB'];let i=0,v=b;while(v>=1024&&i<u.length-1){v/=1024;i++}return `${v.toFixed(v<10&&i?1:0)} ${u[i]}`})();
const esc=s=>String(s||'').replace(/[&<>"']/g,m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[m]));
const toast=(msg,type='info')=>{const map={success:'success',error:'danger'};const n=document.createElement('div');n.className=`toast alert alert-${(map[type]||'info')}`;n.textContent=msg;document.body.appendChild(n);setTimeout(()=>n.remove(),2500)};
const spin={show:()=>$('#spinner').classList.add('show'),hide:()=>$('#spinner').classList.remove('show')};
const infer=(ext)=>{const m={image:['jpg','jpeg','png','gif','svg','webp','bmp'],video:['mp4','mov','m4v','avi','mkv','webm','3gp','wmv'],document:['pdf','doc','docx','txt','rtf','ppt','pptx','xls','xlsx','csv'],archive:['zip','rar','7z','tar','gz'],audio:['mp3','wav','ogg','aac','flac'],animation:['json']};for(const[k,v]of Object.entries(m))if(v.includes(ext))return k;return 'unknown'};
const norm=a=>{const p=a.path||'',n=a.name||(p.split('/').pop()||''),e=(a.extension||n.split('.').pop()||'').toLowerCase(),t=a.type||infer(e);return{ id:(a.id||`webroot|${p}`), idEnc:btoa(a.id||`webroot|${p}`), path:p,name:n,type:t, url:a.url||(a.type==='folder'?null:(a.web_url||a.public_url||null)), size:a.size??0, modified:a.modified??Date.now(), ext:e, dim:a.dimensions||null }};

// ===== Init =====
document.addEventListener('DOMContentLoaded',()=>{
  modal=new bootstrap.Modal($('#assetModal'));
  $('#assetModal').addEventListener('hidden.bs.modal',stopVid);
  load(true);
  bindUI();
});

function bindUI(){
  // Filters
  $('#filters').addEventListener('click',e=>{const b=e.target.closest('[data-type]');if(!b) return; $$('#filters .btn-chip').forEach(x=>x.classList.remove('active')); b.classList.add('active'); S.type=b.dataset.type; load(true)});
  // View
  $('#view').addEventListener('click',e=>{const b=e.target.closest('[data-view]');if(!b)return; $$('#view button').forEach(x=>x.classList.remove('active')); b.classList.add('active'); S.view=b.dataset.view; $('#grid').classList.toggle('list',S.view==='list')});
  // Actions
  document.body.addEventListener('click',e=>{const a=e.target.closest('[data-action]'); if(!a) return; if(a.dataset.action==='pick') return $('#picker').click(); if(a.dataset.action==='folder') return newFolder()});
  $('#picker').addEventListener('change',e=>upload([...e.target.files]));
  // Dropzone
  const dz=$('#dz'); const on=(ev,fn)=>dz.addEventListener(ev,fn);
  ['dragenter','dragover'].forEach(ev=>on(ev,e=>{e.preventDefault();dz.classList.add('drag')}));
  ['dragleave','drop'].forEach(ev=>on(ev,e=>{e.preventDefault();dz.classList.remove('drag')}));
  on('drop',e=>upload([...e.dataTransfer.files]));
  on('click',()=>$('#picker').click());
  // Pager
  $('#more').addEventListener('click',()=>load());
  // Modal buttons
  $('#copy').addEventListener('click',()=>navigator.clipboard.writeText($('#mUrl').value||'').then(()=>toast('URL copied','success')));
  $('#btnDel').addEventListener('click',delCurrent);
  $('#btnMove').addEventListener('click',()=>movePrompt(S.current?.idEnc));
}

// ===== Data =====
async function load(reset=false){
  if(S.loading||(!S.more&&!reset)) return; S.loading=true; if(reset){S.page=1;S.items=[];S.more=true}
  $('#more').disabled=true; spin.show();
  try{
    const d=await j(await req(ROUTE.list(S.page,S.per,S.type)));
    const items=(d.assets||[]).map(norm); S.items.push(...items);
    S.more=!!(d.pagination&&d.pagination.has_more); S.page=d.pagination?.next_page||S.page+1;
    render();
  }catch{ toast('Load failed','error') }
  finally{ S.loading=false; $('#more').disabled=false; spin.hide() }
}

// ===== Render =====
function render(){
  const files=S.items.filter(x=>x.type!=='folder'); const g=$('#grid');
  $('#empty').classList.toggle('d-none',!!files.length); if(!files.length){g.innerHTML='';$('#pager').classList.add('d-none');return}
  g.innerHTML=files.map(a=>{
    const isV=a.type==='video',isI=a.type==='image';
    const prev=isI&&a.url?`<img src="${a.url}" alt="${esc(a.name)}" loading="lazy">`:(isV&&a.url?`<video src="${a.url}#t=0.1" muted playsinline preload="metadata"></video>`:`<i class='fas fa-file fa-2x text-muted'></i>`);
    const act=isV?`<button class='btn btn-sm btn-warning' data-move='${a.idEnc}' title='Move'><i class='fas fa-folder-open'></i></button>`:(isI?`<button class='btn btn-sm btn-danger' data-del='${a.idEnc}'><i class='fas fa-trash'></i></button>`:'');
    return `<div class='item' data-view='${a.idEnc}'>
      <div class='preview'>${prev}</div>
      <div class='meta'><div class='name text-truncate' title='${esc(a.path)}'>${esc(a.name)}</div>
      <div class='sub'><span class='text-uppercase'>${a.type}</span><span>${fmt(a.size)}</span></div></div>
      <div class='actions'>${act}<button class='btn btn-sm btn-light' data-view='${a.idEnc}'><i class='fas fa-eye'></i></button></div>
    </div>`
  }).join('');
  $('#pager').classList.toggle('d-none',!S.more);
  // Delegate actions
  g.onclick=e=>{
    const id=e.target.closest('[data-view]')?.dataset.view; if(id) return view(id);
    const mv=e.target.closest('[data-move]')?.dataset.move; if(mv) return movePrompt(mv);
    const del=e.target.closest('[data-del]')?.dataset.del; if(del) return remove(del);
  };
}

// ===== Preview =====
async function view(id){
  stopVid(); spin.show();
  try{
    const d=await j(await req(ROUTE.info(id))); if(!d?.success) throw 0; const a=norm(d.file||{}); S.current=a;
    $('.modal-title').textContent=a.name||'Asset'; $('#mName').textContent=a.name||'-';
    $('#mType').textContent=(a.type||'unknown').toUpperCase(); $('#mSize').textContent=fmt(a.size);
    const ms=a.modified>1e12?a.modified:a.modified*1000; $('#mDate').textContent=a.modified?new Date(ms).toLocaleString():"-";
    $('#mDim').textContent=a.dim?`${a.dim.width} × ${a.dim.height}`:'-'; $('#mPath').textContent=a.path||'-';
    $('#mUrl').value=a.url||''; $('#dl').href=a.url||'#'; $('#dl').classList.toggle('disabled',!a.url);
    ['mImg','mVid','mIco'].forEach(id=>$('#'+id).classList.add('d-none'));
    if(a.type==='image'&&a.url) { $('#mImg').src=a.url; $('#mImg').classList.remove('d-none') }
    else if(a.type==='video'&&a.url){ $('#mVid').src=a.url+'#t=0.1'; $('#mVid').classList.remove('d-none') }
    else $('#mIco').classList.remove('d-none');
    $('#btnMove').classList.toggle('d-none',a.type!=='video');
    $('#btnDel').classList.toggle('d-none',a.type!=='image');
    modal.show();
  }catch{ toast('Open failed','error') }
  finally{ spin.hide() }
}
function stopVid(){ const v=$('#mVid'); try{v.pause()}catch{} v.removeAttribute('src'); v.load() }

// ===== Delete / Move =====
function delCurrent(){ if(!S.current) return; if(!confirm('Delete this image?')) return; return remove(S.current.idEnc,true) }
async function remove(idEnc,close){
  spin.show();
  try{const d=await j(await req(ROUTE.del(idEnc),{method:'DELETE'})); if(!d?.success) throw 0; toast('Deleted','success'); if(close) modal.hide(); load(true)}catch{toast('Delete failed','error')}
  finally{spin.hide()}
}
function movePrompt(idEnc){ const target=prompt('Move to folder:',MOVE_DEFAULT); if(!target) return; move(idEnc,target) }
async function move(idEnc,target){
  spin.show();
  try{const d=await j(await req(ROUTE.move,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:atob(idEnc),target})})); if(!d?.success) throw 0; toast('Moved','success'); modal.hide(); load(true)}catch{toast('Move failed','error')}
  finally{spin.hide()}
}

// ===== Upload =====
async function upload(files){ if(!files?.length) return; for(const f of files){ await upOne(f) } }
async function upOne(file){
  const fd=new FormData(); fd.append('files[]',file);
  spin.show();
  try{const d=await j(await req(ROUTE.upload,{method:'POST',body:fd})); if(!d?.success) throw 0; toast(`${file.name} uploaded`,'success'); load(true)}catch{toast(`${file.name}: failed`,'error')}
  finally{spin.hide()}
}

// ===== Folder =====
async function newFolder(){ const name=prompt('Folder name:'); if(!name) return; spin.show(); try{const d=await j(await req(ROUTE.folder,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name})})); if(!d?.success) throw 0; toast('Folder created','success')}catch{toast('Create failed','error')} finally{spin.hide()}}
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\assets\index.blade.php ENDPATH**/ ?>