@extends('layouts.admin')
@section('title','Fields Management')

@section('content')
<div class="container-fluid">
  @include('admin.components.page-header',[
    'title'=>'Fields Management',
    'subtitle'=>'Manage all fields in the math learning system',
    'breadcrumbs'=>[['title'=>'Dashboard','url'=>url('/admin')],['title'=>'Fields']],
    'actions'=>[
      ['text'=>'Create New Field','url'=>route('admin.fields.create'),'icon'=>'plus','class'=>'primary'],
      ['type'=>'dropdown','class'=>'secondary','icon'=>'ellipsis-v','text'=>'Actions','items'=>[
        ['icon'=>'download','text'=>'Export Fields','data-action'=>'export'],
        ['icon'=>'upload','text'=>'Import Fields','data-action'=>'import'],
        ['icon'=>'copy','text'=>'Bulk Duplicate Selected','data-action'=>'bulk-duplicate','data-bulk'=>'true'],
        ['icon'=>'trash','text'=>'Bulk Delete Selected','data-action'=>'bulk-delete','data-bulk'=>'true'],
        ['icon'=>'sync','text'=>'Refresh','data-action'=>'refresh']
      ]]
    ]
  ])

  @include('admin.components.stats-row',[
    'stats'=>[
      ['value'=>'0','label'=>'Total Fields','color'=>'primary','icon'=>'tags','data-stat'=>'total'],
      ['value'=>'0','label'=>'Public','color'=>'success','icon'=>'globe','data-stat'=>'public'],
      ['value'=>'0','label'=>'Draft','color'=>'warning','icon'=>'edit','data-stat'=>'draft'],
      ['value'=>'0','label'=>'Private','color'=>'info','icon'=>'lock','data-stat'=>'private']
    ]
  ])

  @component('admin.components.filters-card',['items'=>[]])
    <div class="col-md-3">
      <select class="form-select" id="statusFilter" data-filter="status_id"><option value="">All Status</option></select>
    </div>
    <div class="col-md-6"><input type="search" class="form-control" id="searchInput" placeholder="Search fields..." data-filter="search"></div>
    <div class="col-md-3"><button type="button" class="btn btn-outline-secondary" data-action="clear-filters"><i class="fas fa-times me-1"></i>Clear Filters</button></div>
  @endcomponent

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Fields List</h5>
      <div class="d-flex align-items-center gap-3">
        <label class="form-check m-0"><input type="checkbox" class="form-check-input" id="selectAll"><small class="ms-1">Select All</small></label>
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-secondary bulk-action" data-action="bulk-duplicate" disabled><i class="fas fa-copy me-1"></i>Duplicate Selected</button>
          <button class="btn btn-outline-danger bulk-action" data-action="bulk-delete" disabled><i class="fas fa-trash me-1"></i>Delete Selected</button>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover mb-0" id="fieldsTable">
        <thead class="table-light" id="fieldsHead">
          <tr>
            <th width="50"><input type="checkbox" id="selectAllHeader"></th>
            <th class="sortable" data-key="field">Field <i class="fas fa-sort ms-1 text-muted"></i></th>
            <th class="sortable" data-key="description">Description <i class="fas fa-sort ms-1 text-muted"></i></th>
            <th class="sortable" data-key="status_id">Status <i class="fas fa-sort ms-1 text-muted"></i></th>
            <th class="sortable" data-key="tracks_count">Tracks <i class="fas fa-sort ms-1 text-muted"></i></th>
            <th class="sortable" data-key="created_at">Created <i class="fas fa-sort ms-1 text-muted"></i></th>
            <th width="150" class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody id="fieldsBody">
          <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div><div class="mt-2">Loading fields...</div></td></tr>
        </tbody>
      </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="text-muted">Showing <span data-info="start">0</span> to <span data-info="end">0</span> of <span data-info="total">0</span> entries</div>
      <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
  const QS = s => document.querySelector(s);
  const QSA = s => [...document.querySelectorAll(s)];
  const API = {
    list:   p => fetch(u('/admin/fields', p), hdr()).then(j),
    patch:  (id, body) => fetch(`/admin/fields/${id}`, hdr('PATCH', body)).then(j),
    dup:    id => fetch(`/admin/fields/${id}/duplicate`, hdr('POST')).then(j),
    del:    id => fetch(`/admin/fields/${id}`, hdr('DELETE')).then(j),
    bulk:   (path, ids) => fetch(path, hdr('POST', { field_ids: ids })).then(j),
    statuses: () => fetch('/admin/statuses?limit=1000', hdr()).then(j)
  };
  const state = { rows:[], page:1, pages:1, total:0, sort:'', dir:'asc', per:50, filters:{} , selected:new Set(), statusMap:{}, statusByName:{}};
  const el = {
    body: QS('#fieldsBody'), pag: QS('#pagination'),
    info: {start:QS('[data-info="start"]'), end:QS('[data-info="end"]'), total:QS('[data-info="total"]')},
    status: QS('#statusFilter'), search: QS('#searchInput'),
    selectAll: QS('#selectAll'), selectAllHd: QS('#selectAllHeader'),
    bulkBtns: QSA('.bulk-action')
  };

  function hdr(method='GET', body){
    const opt = { method, headers:{ 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' } };
    if(method!=='GET') { opt.headers['Content-Type']='application/json'; opt.headers['X-CSRF-TOKEN']=CSRF; opt.body = JSON.stringify(body||{}); }
    return opt;
  }
  const j = r => r.ok ? r.json() : r.json().then(d=>Promise.reject(d.message||`HTTP ${r.status}`));
  const u = (base, p={}) => {
    const url = new URL(base, location.origin); Object.entries(p).forEach(([k,v])=> v!=null && v!=='' && url.searchParams.set(k,v)); return url;
  };
  const fmtDate = d => d ? new Date(d).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}) : 'Unknown';
  const toast = (m,t='info') => window.showToast ? window.showToast(m,t) : console.log(t.toUpperCase()+':',m);
  const debounce = (fn,ms) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };

  init();
  async function init(){
    await loadStatuses();
    bind();
    load();
  }

  async function load(page=1){
    renderLoading();
    const data = await API.list({
      page, sort:state.sort, direction:state.dir,
      status_id: el.status.value || '', search: el.search.value || ''
    }).catch(()=> (toast('Error loading fields','error'), { fields:[], num_pages:1, totals:{total:0}}));
    state.rows = data.fields||[];
    state.page = page;
    state.pages = data.num_pages||1;
    state.total = data.totals?.total ?? state.rows.length;
    render();
    updateStats(data.totals||{});
  }

  function bind(){
    // Sorting
    QS('#fieldsHead').addEventListener('click', e=>{
      const th = e.target.closest('.sortable'); if(!th) return;
      const key = th.dataset.key;
      state.dir = state.sort===key && state.dir==='asc' ? 'desc':'asc'; state.sort = key; updateSortIcons(); load(1);
    });

    // Filters
    el.status.addEventListener('change', ()=>load(1));
    el.search.addEventListener('input', debounce(()=>load(1), 300));
    document.addEventListener('click', e=>{
      const a = e.target.closest('[data-action]'); if(!a) return;
      const act = a.dataset.action;
      if(act==='export') location.href='/admin/fields/export';
      if(act==='import') toast('Import functionality coming soon');
      if(act==='refresh') load(state.page);
      if(act==='clear-filters'){ el.status.value=''; el.search.value=''; state.sort=''; state.dir='asc'; updateSortIcons(); load(1); }
      if(act==='bulk-duplicate') bulk('duplicate');
      if(act==='bulk-delete') bulk('delete');
    });

    // Select all
    [el.selectAll, el.selectAllHd].forEach(c=>c.addEventListener('change', e=>{
      const on = e.target.checked;
      [el.selectAll, el.selectAllHd].forEach(i=> i && (i.checked = on));
      QSA('.field-checkbox').forEach(cb=>{ cb.checked = on; toggleSel(cb.value, on); });
      updateBulkBtns();
    }));

    // Delegated table actions
    el.body.addEventListener('click', e=>{
      const btn = e.target.closest('button'); if(!btn) return;
      const tr = e.target.closest('tr'); const id = tr?.dataset.id;
      if(btn.dataset.action==='view') location.href = `/admin/fields/${id}`;
      if(btn.dataset.action==='duplicate') doDup(id);
      if(btn.dataset.action==='delete') doDel(id);
    });

    // Checkbox selection
    el.body.addEventListener('change', e=>{
      if(!e.target.classList.contains('field-checkbox')) return;
      toggleSel(e.target.value, e.target.checked); updateBulkBtns();
    });

    // Inline edit: blur/Enter saves, Esc reverts
    el.body.addEventListener('keydown', e=>{
      const cell = e.target.closest('[contenteditable][data-field]'); if(!cell) return;
      if(e.key==='Enter'){ e.preventDefault(); cell.blur(); }
      if(e.key==='Escape'){ e.target.textContent = cell.dataset.prev || cell.textContent; cell.blur(); }
    });
    el.body.addEventListener('focusin', e=>{
      const cell = e.target.closest('[contenteditable][data-field]'); if(cell) cell.dataset.prev = cell.textContent.trim();
    });
    el.body.addEventListener('focusout', async e=>{
      const cell = e.target.closest('[contenteditable][data-field]'); if(!cell) return;
      const tr = cell.closest('tr'); const id = tr.dataset.id; const key = cell.dataset.field;
      let val = cell.textContent.trim();

      // Status: map name to status_id if user typed label
      if(key==='status_id' && isNaN(+val)){
        const sid = state.statusByName[val] ?? null;
        if(!sid){ toast('Unknown status','warning'); cell.textContent = cell.dataset.prev; return; }
        val = sid;
      }
      // Numeric coercion for tracks_count
      if((key==='tracks_count') && isNaN(+val)){ toast('Tracks must be a number','warning'); cell.textContent = cell.dataset.prev; return; }

      if(val === (cell.dataset.prev||'')) return;
      cell.classList.add('bg-warning-subtle');
      try{
        await API.patch(id, { [key]: key==='tracks_count' ? +val : val });
        toast('Saved','success'); cell.classList.remove('bg-warning-subtle'); cell.classList.add('bg-success-subtle');
        setTimeout(()=>cell.classList.remove('bg-success-subtle'), 600);
        if(key==='status_id') cell.textContent = state.statusMap[val] || val;
        if(key==='created_at') cell.textContent = fmtDate(val);
      }catch{
        toast('Save failed','error'); cell.textContent = cell.dataset.prev; cell.classList.remove('bg-warning-subtle');
      }
    });
  }

  function render(){
    if(!state.rows.length){ el.body.innerHTML = `<tr><td colspan="7" class="text-center py-4"><i class="fas fa-search me-2"></i>No fields found</td></tr>`; return; }
    el.body.innerHTML = state.rows.map(r=> row(r)).join('');
    paginate();
  }

  function row(r){
    const statusName = r.status?.status || state.statusMap[r.status_id] || 'Unknown';
    return `
      <tr data-id="${r.id}">
        <td><input type="checkbox" class="form-check-input field-checkbox" value="${r.id}" ${state.selected.has(String(r.id))?'checked':''}></td>
        <td contenteditable="true" data-field="field" class="fw-semibold">${esc(r.field||'')}</td>
        <td contenteditable="true" data-field="description">${esc(r.description||'')}</td>
        <td contenteditable="true" data-field="status_id">${esc(statusName)}</td>
        <td contenteditable="true" data-field="tracks_count">${r.tracks_count ?? 0}</td>
        <td contenteditable="true" data-field="created_at">${fmtDate(r.created_at)}</td>
        <td class="text-center">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-info" data-action="view" title="View"><i class="fas fa-eye"></i></button>
            <button class="btn btn-outline-secondary" data-action="duplicate" title="Duplicate"><i class="fas fa-copy"></i></button>
            <button class="btn btn-outline-danger" data-action="delete" title="Delete"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      </tr>
    `;
  }

  function paginate(){
    const make = (label, page, disabled=false, active=false) =>
      `<li class="page-item ${disabled?'disabled':''} ${active?'active':''}">
        <a class="page-link" href="#" ${disabled?'':`onclick="return false"`} data-page="${page||''}">${label}</a>
      </li>`;
    const items = [];
    items.push(make('&laquo;', state.page-1, state.page<=1));
    const s = Math.max(1, state.page-2), e = Math.min(state.pages, state.page+2);
    if(s>1){ items.push(make('1',1)); if(s>2) items.push(`<li class="page-item disabled"><span class="page-link">…</span></li>`); }
    for(let i=s;i<=e;i++) items.push(make(String(i),i,false,i===state.page));
    if(e<state.pages){ if(e<state.pages-1) items.push(`<li class="page-item disabled"><span class="page-link">…</span></li>`); items.push(make(String(state.pages), state.pages)); }
    items.push(make('&raquo;', state.page+1, state.page>=state.pages));
    el.pag.innerHTML = items.join('');
    el.pag.onclick = ev=>{
      const a = ev.target.closest('a[data-page]'); if(!a) return;
      const p = +a.dataset.page; if(!p || p===state.page) return;
      load(p);
    };
    const start = (state.page-1)*state.per + 1, end = Math.min(state.page*state.per, state.total);
    if(el.info.start) el.info.start.textContent = state.total ? start : 0;
    if(el.info.end) el.info.end.textContent = state.total ? end : 0;
    if(el.info.total) el.info.total.textContent = state.total;
  }

  async function doDup(id){ await API.dup(id).then(()=> (toast('Duplicated','success'), load(state.page))).catch(()=>toast('Duplicate failed','error')); }
  async function doDel(id){
    if(!confirm('Delete this field? This cannot be undone.')) return;
    await API.del(id).then(()=> (toast('Deleted','success'), load(state.page))).catch(()=>toast('Delete failed','error'));
  }
  async function bulk(kind){
    const ids = [...state.selected]; if(!ids.length) return toast(`Please select fields to ${kind}`,'warning');
    if(!confirm(`${kind==='delete'?'Delete':'Duplicate'} ${ids.length} selected?${kind==='delete'?' This cannot be undone.':''}`)) return;
    const path = kind==='delete'? '/admin/fields/bulk-delete':'/admin/fields/bulk-duplicate';
    toggleBulk(true, kind); await API.bulk(path, ids).then(()=>{
      toast(`${ids.length} ${kind==='delete'?'deleted':'duplicated'}!`,'success'); state.selected.clear(); load(state.page);
    }).catch(()=> toast(`Bulk ${kind} failed`,'error')).finally(()=> toggleBulk(false, kind));
  }

  function toggleBulk(disabled, kind){
    const btn = document.querySelector(`[data-action="bulk-${kind}"]`); if(!btn) return;
    btn.disabled = disabled; btn.innerHTML = disabled ? `<i class="fas fa-spinner fa-spin me-1"></i>${kind==='delete'?'Deleting...':'Duplicating...'}` : btn.dataset.action==='bulk-delete'?'<i class="fas fa-trash me-1"></i>Delete Selected':'<i class="fas fa-copy me-1"></i>Duplicate Selected';
    QSA('[data-bulk="true"]').forEach(i=> i.classList.toggle('disabled', disabled));
  }

  function updateStats(t){ const s = { total: t.total||state.total, public:t.public||0, draft:t.draft||0, private:t.private||0 };
    QSA('[data-stat]').forEach(e=> { const k=e.dataset.stat; if(k in s) e.textContent = s[k]; });
  }

  function updateSortIcons(){
    QSA('#fieldsHead .sortable i').forEach(i=> i.className='fas fa-sort ms-1 text-muted');
    const th = QSA('#fieldsHead .sortable').find(h=> h.dataset.key===state.sort);
    if(th) th.querySelector('i').className = `fas fa-sort-${state.dir==='asc'?'up':'down'} ms-1 text-primary`;
  }

  async function loadStatuses(){
    const res = await API.statuses().catch(()=>({data:[]}));
    const list = res.data || res.statuses || res || [];
    state.statusMap = {}; state.statusByName = {};
    list.forEach(s=>{ const id = s.id; const name = s.status || s.name || 'Unnamed'; state.statusMap[id]=name; state.statusByName[name]=id; });
    // fill filter
    const frag = document.createDocumentFragment();
    Object.entries(state.statusMap).forEach(([id,name])=>{ const o=document.createElement('option'); o.value=id; o.textContent=name; frag.appendChild(o); });
    el.status.appendChild(frag);
  }

  function toggleSel(id,on){ on ? state.selected.add(String(id)) : state.selected.delete(String(id)); }
  function updateBulkBtns(){ const on = state.selected.size>0; el.bulkBtns.forEach(b=> b.disabled = !on); }
  function renderLoading(){ el.body.innerHTML = `<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div><div class="mt-2">Loading fields...</div></td></tr>`; }
  function esc(s){ const d=document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }
})();
</script>
@endpush
