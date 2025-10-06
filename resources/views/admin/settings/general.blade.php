@extends('layouts.admin')
@section('title','General Settings')

@push('styles')
<style>
  .nav-tabs .nav-link { color: var(--on-surface) !important; opacity:.8; border:1px solid transparent; background:transparent; }
  .nav-tabs .nav-link:hover { color: var(--primary-color) !important; opacity:1; background:var(--surface-container); border-color: var(--outline-variant); }
  .nav-tabs .nav-link.active { color: var(--primary-color) !important; background: var(--surface-color) !important; border-color: var(--outline-variant); border-bottom-color: var(--surface-color); opacity:1; font-weight:600; }
  .color-preview { width:32px; height:32px; border-radius:6px; border:2px solid var(--outline); display:inline-block; margin-right:8px; vertical-align:middle; }
  .font-preview { padding:8px 12px; border:1px solid var(--outline-variant); border-radius: var(--border-radius-sm); background: var(--surface-container); font-size:14px; margin-top:4px; display:inline-block; }
  .inline-edit { cursor: text; }
</style>
@endpush

@section('content')
@php
use Illuminate\Support\Facades\Schema;

$get = fn($k) => data_get($config ?? [], $k);
$mailDefaults = $mailDefaults ?? [];
$columnsFromController = isset($dbFields) && is_array($dbFields)
? $dbFields
: (Schema::hasTable('configs') ? Schema::getColumnListing('configs') : []);
$onlyDb = fn(array $items) => array_values(array_filter($items, fn($it) => in_array($it['k'], $columnsFromController, true)));

$site = [
['k'=>'site_name','label'=>'Site Name','type'=>'text','desc'=>'Full name of your LMS'],
['k'=>'site_shortname','label'=>'Short Name','type'=>'text','desc'=>'Abbreviation used in menus'],
['k'=>'site_url','label'=>'Site URL','type'=>'url','desc'=>'Base URL of your application'],
['k'=>'email','label'=>'Contact Email','type'=>'email','desc'=>'Primary contact email'],
['k'=>'timezone','label'=>'Timezone','type'=>'select','desc'=>'Site timezone','options'=>[
'UTC'=>'UTC','America/New_York'=>'Eastern Time','America/Chicago'=>'Central Time','America/Denver'=>'Mountain Time',
'America/Los_Angeles'=>'Pacific Time','Europe/London'=>'London','Asia/Singapore'=>'Singapore','Asia/Tokyo'=>'Tokyo'
]],
['k'=>'date_format','label'=>'Date Format','type'=>'select','desc'=>'Display format for dates','options'=>[
'd/m/Y'=>'DD/MM/YYYY','m/d/Y'=>'MM/DD/YYYY','Y-m-d'=>'YYYY-MM-DD','d-m-Y'=>'DD-MM-YYYY'
]],
['k'=>'time_format','label'=>'Time Format','type'=>'select','desc'=>'Display format for time','options'=>['12'=>'12 Hour','24'=>'24 Hour']],
];

$email = [
['k'=>'mail_host','label'=>'SMTP Host','type'=>'text','desc'=>'Email server hostname (e.g., smtp.gmail.com)'],
['k'=>'mail_port','label'=>'SMTP Port','type'=>'number','desc'=>'Email server port (usually 587 for TLS)'],
['k'=>'mail_username','label'=>'SMTP Username','type'=>'text','desc'=>'Email server username/email address'],
['k'=>'mail_from_name','label'=>'From Name','type'=>'text','desc'=>'Default sender name for system emails'],
['k'=>'mail_encryption','label'=>'Encryption','type'=>'select','desc'=>'Email encryption method','options'=>['none'=>'None','tls'=>'TLS','ssl'=>'SSL']],
['k'=>'mail_from_address','label'=>'From Email Address','type'=>'email','desc'=>'Default sender email address'],
];

$colors = [
['k'=>'main_color','label'=>'Primary Color','type'=>'color','desc'=>'Main brand color for buttons and links'],
['k'=>'secondary_color','label'=>'Secondary Color','type'=>'color','desc'=>'Accent color for highlights and warnings'],
['k'=>'tertiary_color','label'=>'Tertiary Color','type'=>'color','desc'=>'Success and positive actions'],
['k'=>'success_color','label'=>'Success Color','type'=>'color','desc'=>'Success messages and confirmations'],
['k'=>'error_color','label'=>'Error Color','type'=>'color','desc'=>'Error messages and warnings'],
['k'=>'warning_color','label'=>'Warning Color','type'=>'color','desc'=>'Warning messages and cautions'],
['k'=>'info_color','label'=>'Info Color','type'=>'color','desc'=>'Informational messages'],
['k'=>'black_color','label'=>'Dark Color','type'=>'color','desc'=>'Dark text and backgrounds'],
['k'=>'white_color','label'=>'Light Color','type'=>'color','desc'=>'Light text and backgrounds'],
];

$typography = [
['k'=>'primary_font','label'=>'Primary Font','type'=>'text','desc'=>'Main font family for body text'],
['k'=>'secondary_font','label'=>'Secondary Font','type'=>'text','desc'=>'Font family for headings and accents'],
['k'=>'body_font_size','label'=>'Body Font Size','type'=>'text','desc'=>'Base font size (e.g., 16px)'],
['k'=>'h1_font_size','label'=>'H1 Font Size','type'=>'text','desc'=>'Large heading size'],
['k'=>'h2_font_size','label'=>'H2 Font Size','type'=>'text','desc'=>'Medium heading size'],
['k'=>'h3_font_size','label'=>'H3 Font Size','type'=>'text','desc'=>'Small heading size'],
['k'=>'h4_font_size','label'=>'H4 Font Size','type'=>'text','desc'=>'Extra small heading size'],
['k'=>'h5_font_size','label'=>'H5 Font Size','type'=>'text','desc'=>'Minimal heading size'],
['k'=>'body_line_height','label'=>'Body Line Height','type'=>'text','desc'=>'Line spacing for body text (e.g., 1.5)'],
['k'=>'heading_line_height','label'=>'Heading Line Height','type'=>'text','desc'=>'Line spacing for headings (e.g., 1.2)'],
['k'=>'font_weight_normal','label'=>'Normal Weight','type'=>'text','desc'=>'Regular text weight (e.g., 400)'],
['k'=>'font_weight_medium','label'=>'Medium Weight','type'=>'text','desc'=>'Medium text weight (e.g., 500)'],
['k'=>'font_weight_bold','label'=>'Bold Weight','type'=>'text','desc'=>'Bold text weight (e.g., 600)'],
];

$layout = [
['k'=>'border_radius','label'=>'Border Radius','type'=>'text','desc'=>'Corner roundness (e.g., 8px)'],
['k'=>'sidebar_width','label'=>'Sidebar Width','type'=>'text','desc'=>'Admin sidebar width (e.g., 280px)'],
['k'=>'content_max_width','label'=>'Content Max Width','type'=>'text','desc'=>'Maximum content area width (e.g., 1400px)'],
];

$learning = [
// Removed number_of_teaching_days as requested
['k'=>'questions_per_test','label'=>'Questions Per Test','type'=>'number','min'=>1,'max'=>100],
['k'=>'no_rights_to_pass','label'=>'Rights Needed to Pass','type'=>'number','min'=>1,'max'=>10],
['k'=>'no_wrongs_to_fail','label'=>'Wrongs to Fail','type'=>'number','min'=>1,'max'=>10],
];

// Hide any fields not in DB
$site       = $onlyDb($site);
$email      = $onlyDb($email);
$colors     = $onlyDb($colors);
$typography = $onlyDb($typography);
$layout     = $onlyDb($layout);
$learning   = $onlyDb($learning);
@endphp

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h4 mb-1">General Settings</h1>
      <div class="text-muted">Click any value to edit and hit Enter to save.</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-info" onclick="testConfiguration()"><i class="fas fa-vial me-1"></i> Test</button>
      <button class="btn btn-outline-warning" onclick="resetToDefaults()"><i class="fas fa-undo me-1"></i> Reset</button>
      <button class="btn btn-primary" onclick="location.reload()"><i class="fas fa-sync me-1"></i> Refresh</button>
    </div>
  </div>

  <ul class="nav nav-tabs app-tabs app-tabs--compact mb-3" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="site-tab" data-bs-toggle="tab" data-bs-target="#site" type="button" role="tab" aria-controls="site" aria-selected="true"><i class="fas fa-globe me-2"></i>Site</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab" aria-controls="email" aria-selected="false"><i class="fas fa-envelope me-2"></i>Email</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="theme-tab" data-bs-toggle="tab" data-bs-target="#theme" type="button" role="tab" aria-controls="theme" aria-selected="false"><i class="fas fa-palette me-2"></i>Theme</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="learning-tab" data-bs-toggle="tab" data-bs-target="#learning" type="button" role="tab" aria-controls="learning" aria-selected="false"><i class="fas fa-graduation-cap me-2"></i>Learning</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab" aria-controls="system" aria-selected="false"><i class="fas fa-cog me-2"></i>System</button>
    </li>
  </ul>

  <div class="tab-content">
    {{-- Site --}}
    <div class="tab-pane fade show active" id="site" role="tabpanel" aria-labelledby="site-tab">
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header"><strong>Basic Site Settings</strong></div>
            <div class="card-body">
              @foreach($site as $it)
              @php $k=$it['k']; $type=$it['type']; $val = old($k, $get($k)); @endphp
              <div class="mb-3">
                <label class="form-label fw-semibold">{{ $it['label'] }}</label>
                <div class="border rounded p-2 d-flex align-items-center inline-edit" data-field="{{ $k }}">
                  <span class="flex-grow-1 value-display" data-raw="{{ $val }}">{{ $val }}</span>
                  @if($type==='select')
                  <select class="form-select form-select-sm d-none edit-input">
                    @foreach(($it['options'] ?? []) as $ov => $ol)
                    <option value="{{ $ov }}" {{ (string)$val===(string)$ov?'selected':'' }}>{{ $ol }}</option>
                    @endforeach
                  </select>
                  @elseif($type==='textarea')
                  <textarea rows="3" class="form-control d-none edit-input">{{ $val }}</textarea>
                  @else
                  <input class="form-control form-control-sm d-none edit-input" type="{{ $type }}" value="{{ $val }}" @if(isset($it['min'])) min="{{ $it['min'] }}" @endif @if(isset($it['max'])) max="{{ $it['max'] }}" @endif />
                  @endif
                  <small class="ms-2 text-success d-none save-indicator"><i class="fas fa-check"></i> Saved</small>
                  <small class="ms-2 text-danger d-none error-indicator"><i class="fas fa-times"></i> Error</small>
                </div>
                @if(!empty($it['desc'] ?? null))
                <div class="text-muted small mt-1">{{ $it['desc'] }}</div>
                @endif
              </div>
              @endforeach
            </div>
          </div>
        </div>

        {{-- Visual Assets card in resources/views/admin/settings/general.blade.php --}}
        <div class="col-lg-4 mt-3 mt-lg-0">
          <div class="card">
            <div class="card-header"><strong>Visual Assets</strong></div>
            <div class="card-body">
              {{-- Logo --}}
              <div class="mb-4">
                <label class="form-label fw-semibold d-block mb-2">Site Logo</label>
                @php
                $logoPath = $get('site_logo');
                $logoUrl  = $logoPath ? asset($logoPath) . '?v=' . ($assetVersion ?? time()) : null;
                @endphp
                @if($logoPath && file_exists(public_path($logoPath)))
                <div class="border rounded p-2 bg-light">
                  <img id="logoImg" src="{{ $logoUrl }}" class="img-fluid d-block mx-auto mb-2" style="max-height:80px" alt="Logo">
                  <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('logoInput').click()">
                      <i class="fas fa-edit me-1"></i>Change
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteFile('logo')">
                      <i class="fas fa-trash me-1"></i>Delete
                    </button>
                  </div>
                </div>
                @else
                <button class="btn btn-outline-secondary w-100" onclick="document.getElementById('logoInput').click()">
                  <i class="fas fa-image me-2"></i> Upload Logo
                </button>
                @endif
                <input id="logoInput" type="file" accept="image/*" class="d-none" onchange="uploadFile(this,'logo')">
              </div>

              {{-- Favicon --}}
              <div class="mb-4">
                <label class="form-label fw-semibold d-block mb-2">Favicon</label>
                @php
                $faviconPath = $get('favicon');
                $faviconUrl  = $faviconPath ? asset($faviconPath) . '?v=' . ($assetVersion ?? time()) : null;
                @endphp
                @if($faviconPath && file_exists(public_path($faviconPath)))
                <div class="border rounded p-2 bg-light text-center">
                  <img id="faviconImg" src="{{ $faviconUrl }}" class="d-block mx-auto mb-2" style="width:32px;height:32px" alt="Favicon">
                  <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('faviconInput').click()">
                      <i class="fas fa-edit me-1"></i>Change
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteFile('favicon')">
                      <i class="fas fa-trash me-1"></i>Delete
                    </button>
                  </div>
                </div>
                @else
                <button class="btn btn-outline-secondary w-100" onclick="document.getElementById('faviconInput').click()">
                  <i class="fas fa-star me-2"></i> Upload Favicon
                </button>
                <div class="text-muted small mt-1">16×16 or 32×32 px recommended</div>
                @endif
                <input id="faviconInput" type="file" class="d-none" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon,.ico" onchange="uploadFile(this,'favicon')">
              </div>

              {{-- Login background --}}
              <div class="mb-0">
                <label class="form-label fw-semibold d-block mb-2">Login Background</label>
                <button class="btn btn-outline-secondary w-100" onclick="document.getElementById('backgroundInput').click()">
                  <i class="fas fa-image me-2"></i> Upload Background
                </button>
                <input id="backgroundInput" type="file" accept="image/*" class="d-none" onchange="uploadFile(this,'login_background')">
                <div class="text-muted small mt-1">1920×1080 px recommended</div>
              </div>
            </div>
          </div>
        </div>
      </div>{{-- row --}}
    </div>{{-- /site --}}

    {{-- Email --}}
    <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
      <div class="row">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header"><strong>SMTP Email Configuration</strong></div>
            <div class="card-body">
              @foreach($email as $it)
              @php
              $k   = $it['k'];
              $type= $it['type'];
              // show DB value or .env/config() fallback
              $val = old($k, $get($k) ?? data_get($mailDefaults, $k));
              @endphp
              <div class="mb-3">
                <label class="form-label fw-semibold">{{ $it['label'] }}</label>
                <div class="border rounded p-2 d-flex align-items-center inline-edit" data-field="{{ $k }}">
                  <span class="flex-grow-1 value-display" data-raw="{{ $val }}">{{ $val }}</span>
                  @if(($it['type'] ?? '') === 'select')
                  <select class="form-select form-select-sm d-none edit-input">
                    @foreach(($it['options'] ?? []) as $ov => $ol)
                    <option value="{{ $ov }}" {{ (string)$val===(string)$ov?'selected':'' }}>{{ $ol }}</option>
                    @endforeach
                  </select>
                  @else
                  <input class="form-control form-control-sm d-none edit-input" type="{{ $type }}" value="{{ $val }}"
                  @if(isset($it['min'])) min="{{ $it['min'] }}" @endif
                  @if(isset($it['max'])) max="{{ $it['max'] }}" @endif />
                  @endif
                  <small class="ms-2 text-success d-none save-indicator"><i class="fas fa-check"></i> Saved</small>
                  <small class="ms-2 text-danger d-none error-indicator"><i class="fas fa-times"></i> Error</small>
                </div>
                @if(!empty($it['desc'] ?? null))
                <div class="text-muted small mt-1">{{ $it['desc'] }}</div>
                @endif
              </div>
              @endforeach

              <div class="alert alert-info">
                <strong>Note:</strong> SMTP password stays in server config/env.
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card">
            <div class="card-header"><strong>Email Test</strong></div>
            <div class="card-body">
              <p class="text-muted">Send a quick test email.</p>
              <div class="mb-3">
                <input type="email" class="form-control" id="testEmailAddress" placeholder="Enter test email address">
              </div>
              <button class="btn btn-primary w-100" onclick="sendTestEmail()">
                <i class="fas fa-paper-plane me-1"></i> Send Test Email
              </button>
            </div>
          </div>
        </div>
      </div>{{-- row --}}
    </div>{{-- /email --}}

    {{-- Theme --}}
    <div class="tab-pane fade" id="theme" role="tabpanel" aria-labelledby="theme-tab">
      <div class="row">
        <div class="col-lg-6">
          <div class="card mb-3">
            <div class="card-header"><strong>Colors</strong></div>
            <div class="card-body">
              @foreach($colors as $it)
              @php $k=$it['k']; $type=$it['type']; $val = old($k, $get($k)); @endphp
              <div class="mb-3">
                <label class="form-label fw-semibold">{{ $it['label'] }}</label>
                <div class="border rounded p-2 d-flex align-items-center inline-edit" data-field="{{ $k }}">
                  <span class="flex-grow-1 value-display" data-raw="{{ $val }}">{{ $val }}</span>
                  <input class="form-control form-control-sm d-none edit-input" type="{{ $type }}" value="{{ $val }}" />
                  <small class="ms-2 text-success d-none save-indicator"><i class="fas fa-check"></i> Saved</small>
                  <small class="ms-2 text-danger d-none error-indicator"><i class="fas fa-times"></i> Error</small>
                </div>
                @if(!empty($it['desc'] ?? null))
                <div class="text-muted small mt-1">{{ $it['desc'] }}</div>
                @endif
              </div>
              @endforeach
            </div>
          </div>

          <div class="card">
            <div class="card-header"><strong>Layout</strong></div>
            <div class="card-body">
              @foreach($layout as $it)
              @php $k=$it['k']; $type=$it['type']; $val = old($k, $get($k)); @endphp
              <div class="mb-3">
                <label class="form-label fw-semibold">{{ $it['label'] }}</label>
                <div class="border rounded p-2 d-flex align-items-center inline-edit" data-field="{{ $k }}">
                  <span class="flex-grow-1 value-display" data-raw="{{ $val }}">{{ $val }}</span>
                  <input class="form-control form-control-sm d-none edit-input" type="{{ $type }}" value="{{ $val }}" />
                  <small class="ms-2 text-success d-none save-indicator"><i class="fas fa-check"></i> Saved</small>
                  <small class="ms-2 text-danger d-none error-indicator"><i class="fas fa-times"></i> Error</small>
                </div>
                @if(!empty($it['desc'] ?? null))
                <div class="text-muted small mt-1">{{ $it['desc'] }}</div>
                @endif
              </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card">
            <div class="card-header"><strong>Typography</strong></div>
            <div class="card-body">
              @foreach($typography as $it)
              @php $k=$it['k']; $type=$it['type']; $val = old($k, $get($k)); @endphp
              <div class="mb-3">
                <label class="form-label fw-semibold">{{ $it['label'] }}</label>
                <div class="border rounded p-2 d-flex align-items-center inline-edit" data-field="{{ $k }}">
                  <span class="flex-grow-1 value-display" data-raw="{{ $val }}">{{ $val }}</span>
                  <input class="form-control form-control-sm d-none edit-input" type="{{ $type }}" value="{{ $val }}" />
                  <small class="ms-2 text-success d-none save-indicator"><i class="fas fa-check"></i> Saved</small>
                  <small class="ms-2 text-danger d-none error-indicator"><i class="fas fa-times"></i> Error</small>
                </div>
                @if(!empty($it['desc'] ?? null))
                <div class="text-muted small mt-1">{{ $it['desc'] }}</div>
                @endif
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>{{-- row --}}
    </div>{{-- /theme --}}

    {{-- Learning --}}
    <div class="tab-pane fade" id="learning" role="tabpanel" aria-labelledby="learning-tab">
      <div class="row">
        <div class="col-lg-6">
          <div class="card mb-3">
            <div class="card-header"><strong>Learning Settings</strong></div>
            <div class="card-body">
              @foreach($learning as $it)
              @php $k=$it['k']; $type=$it['type']; $val = old($k, $get($k)); @endphp
              <div class="mb-3">
                <label class="form-label fw-semibold">{{ $it['label'] }}</label>
                <div class="border rounded p-2 d-flex align-items-center inline-edit" data-field="{{ $k }}">
                  <span class="flex-grow-1 value-display" data-raw="{{ $val }}">{{ $val }}</span>
                  <input class="form-control form-control-sm d-none edit-input" type="{{ $type }}" value="{{ $val }}"
                  @if(isset($it['min'])) min="{{ $it['min'] }}" @endif
                  @if(isset($it['max'])) max="{{ $it['max'] }}" @endif />
                  <small class="ms-2 text-success d-none save-indicator"><i class="fas fa-check"></i> Saved</small>
                  <small class="ms-2 text-danger d-none error-indicator"><i class="fas fa-times"></i> Error</small>
                </div>
              </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card mb-3">
            <div class="card-header"><strong>Feature Toggles</strong></div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label fw-semibold d-block">Self-Paced Learning</label>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" data-field="self_paced" onchange="updateToggle(this)" {{ $get('self_paced') ? 'checked' : '' }}>
                  <span class="text-muted small">Allow students to learn at their own pace</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>{{-- row --}}
    </div>{{-- /learning --}}

    {{-- System --}}
    <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
      <div class="card">
        <div class="card-header"><strong>Maintenance</strong></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label fw-semibold d-block">Maintenance Mode</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" data-field="maintenance_mode" onchange="updateToggle(this)" {{ $get('maintenance_mode') ? 'checked' : '' }}>
              <span class="text-muted small">Put the site in maintenance mode</span>
            </div>
          </div>

          @php $k='maintenance_message'; $val = old($k, $get($k)); @endphp
          <div class="mb-0">
            <label class="form-label fw-semibold">Maintenance Message</label>
            <div class="border rounded p-2 d-flex align-items-center inline-edit" data-field="{{ $k }}">
              <span class="flex-grow-1 value-display" data-raw="{{ $val }}">{{ $val }}</span>
              <textarea rows="3" class="form-control d-none edit-input">{{ $val }}</textarea>
              <small class="ms-2 text-success d-none save-indicator"><i class="fas fa-check"></i> Saved</small>
              <small class="ms-2 text-danger d-none error-indicator"><i class="fas fa-times"></i> Error</small>
            </div>
          </div>

        </div>
      </div>
    </div>{{-- /system --}}
  </div>{{-- /tab-content --}}

  <div id="testResults" class="card mt-3 d-none">
    <div class="card-header"><strong>Configuration Test Results</strong></div>
    <div class="card-body" id="testResultsContent"></div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const API = {
    update:'/admin/settings/general',
    test:'/admin/settings/test',
    reset:'/admin/settings/reset',
    file:(t)=>`/admin/settings/${t}`,
    testEmail:'/admin/settings/test-email'
  };

  const rq   = (url, opt={}) => fetch(url, opt).then(r=>{ const ct=r.headers.get('content-type')||''; return ct.includes('application/json')? r.json(): r.text(); });
  const json = (url, body={}, method='POST') => rq(url,{ method, headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json' }, body: JSON.stringify(body) });
  const form = (url, fd, method='POST') => rq(url,{ method, headers:{ 'X-CSRF-TOKEN':csrf }, body: fd });

  const toast = (msg,type='info')=>{
    const map={success:'alert-success',error:'alert-danger',warning:'alert-warning',info:'alert-info'};
    const n=document.createElement('div'); n.className=`alert ${map[type]||map.info} position-fixed top-0 end-0 m-3 shadow`;
    n.textContent=msg; document.body.appendChild(n); setTimeout(()=>n.remove(),2400);
  };

  const setText=(el,txt)=>{ el.textContent=String(txt??''); };

  function eventElement(e){
    if (e && typeof e.composedPath === 'function'){
      for (const n of e.composedPath()) if (n && n.nodeType === Node.ELEMENT_NODE) return n;
    }
  const t=e?.target; if (t?.nodeType===Node.ELEMENT_NODE) return t; if (t?.nodeType===Node.TEXT_NODE) return t.parentElement;
  const a=document.activeElement; return a?.nodeType===Node.ELEMENT_NODE? a : null;
}

function renderValue(container, field, raw){
  const wrap=document.createElement('span'); wrap.className='d-inline-flex align-items-center gap-2';
  const colorFields=new Set(['main_color','secondary_color','tertiary_color','success_color','error_color','warning_color','info_color','black_color','white_color']);
  const fontFields=new Set(['primary_font','secondary_font']);
  const pretty=(f,v)=>{
    const s=String(v??'');
    if(f==='time_format') return s==='12'?'12 Hour':(s==='24'?'24 Hour':s);
    const units={questions_per_test:'questions',no_rights_to_pass:'correct answers',no_wrongs_to_fail:'wrong answers',mail_port:'port'};
    return units[f]? `${s} ${units[f]}`: s;
  };

  if(colorFields.has(field)){
    const sw=document.createElement('span'); sw.className='color-preview'; sw.style.background=String(raw||'transparent');
    const txt=document.createElement('span'); setText(txt,String(raw||'')); wrap.appendChild(sw); wrap.appendChild(txt);
  } else if (fontFields.has(field)){
    const box=document.createElement('div'); box.className='font-preview'; box.style.fontFamily=String(raw||'inherit'); setText(box,String(raw||'')); wrap.appendChild(box);
  } else {
    const txt=document.createElement('span'); setText(txt,pretty(field,raw)); wrap.appendChild(txt);
  }
  container.replaceChildren(wrap);
}

function initDisplays(){
  document.querySelectorAll('.inline-edit .value-display').forEach(sp=>{
    const row=sp.closest('.inline-edit'); const field=row.dataset.field; const raw=sp.dataset.raw ?? sp.textContent.trim();
    sp.dataset.raw=raw; renderValue(sp,field,raw);
  });
}
document.addEventListener('DOMContentLoaded', initDisplays);

let editingRow=null;
function showEditor(row){ const input=row.querySelector('.edit-input'); const display=row.querySelector('.value-display'); if(!input||!display) return; if(editingRow&&editingRow!==row) hideEditor(editingRow,false); editingRow=row; row.classList.add('editing'); input.classList.remove('d-none'); display.classList.add('d-none'); input.focus(); input.select?.(); }
function hideEditor(row,restore=true){ const input=row.querySelector('.edit-input'); const display=row.querySelector('.value-display'); if(!input||!display) return; input.classList.add('d-none'); if(restore) display.classList.remove('d-none'); row.classList.remove('editing'); if(editingRow===row) editingRow=null; }
function indicators(row,state){ const ok=row.querySelector('.save-indicator'); const er=row.querySelector('.error-indicator'); [ok,er].forEach(x=>x&&x.classList.add('d-none')); if(state==='ok'&&ok){ ok.classList.remove('d-none'); setTimeout(()=>ok.classList.add('d-none'),1500); } if(state==='err'&&er){ er.classList.remove('d-none'); setTimeout(()=>er.classList.add('d-none'),2000); } }

/* NEW: map DB fields -> CSS vars for instant visual update */
const fieldToVar = {
  main_color: '--primary-color',
  secondary_color: '--secondary-color',
  tertiary_color: '--tertiary-color',
  success_color: '--success-color',
  error_color: '--error-color',
  warning_color: '--warning-color',
  info_color: '--info-color',
  black_color: '--black-color',
  white_color: '--white-color',
  primary_font: '--primary-font',
  secondary_font: '--secondary-font',
  body_font_size: '--body-font-size',
  h1_font_size: '--h1-font-size',
  h2_font_size: '--h2-font-size',
  h3_font_size: '--h3-font-size',
  h4_font_size: '--h4-font-size',
  h5_font_size: '--h5-font-size',
  body_line_height: '--body-line-height',
  heading_line_height: '--heading_line_height',
  font_weight_normal: '--font-weight-normal',
  font_weight_medium: '--font-weight-medium',
  font_weight_bold: '--font-weight-bold',
  border_radius: '--border-radius',
  sidebar_width: '--sidebar-width',
  content_max_width: '--content-max-width'
};
function applyCssVar(field, value){
  const cssVar = fieldToVar[field];
  if (cssVar) document.documentElement.style.setProperty(cssVar, String(value ?? ''));
}

/* Swap theme-generated.css by version (file mtime) */
function hotSwapThemeCss(ver){
  let link = document.getElementById('theme-css') || document.querySelector('link[href*="theme-generated.css"]');
  if (!link) return;
  const base = link.href.split('?')[0];
  link.href = `${base}?v=${ver || Date.now()}`;
}

addEventListener('click', e=>{ const el=eventElement(e); const row=el? el.closest('.inline-edit'): null; if(row && !row.classList.contains('editing')) showEditor(row); });
addEventListener('keydown', e=>{ const el=eventElement(e); const row=el? el.closest('.inline-edit'): null; if(!row) return; if(e.key==='Escape'){ hideEditor(row); return; } if(e.key==='Enter' && el.tagName !== 'TEXTAREA'){ e.preventDefault(); saveRow(row); } });
addEventListener('blur', e=>{ const el=eventElement(e); const row=el? el.closest('.inline-edit'): null; if(row) setTimeout(()=>{ if(row===editingRow) saveRow(row); },80); }, true);
addEventListener('change', e=>{ const el=eventElement(e); const row=el? el.closest('.inline-edit'): null; if(!row) return; if(el.matches?.('select') || el.type==='color') saveRow(row); });

function saveRow(row){
  const field=row.dataset.field; const input=row.querySelector('.edit-input'); const display=row.querySelector('.value-display');
  const newVal=String(input.value??'').trim(); const prevRaw=display.dataset.raw ?? '';
  hideEditor(row); if(newVal===prevRaw) return;

  renderValue(display,field,newVal); display.dataset.raw=newVal; indicators(row,null);

  json(API.update,{ [field]: newVal }).then(r=>{
    if(r && r.success){
      indicators(row,'ok');
      if (r.theme_changed){
        applyCssVar(field, newVal);               // instant
        if (r.css_version) hotSwapThemeCss(r.css_version); // reload file
      }
    } else {
      indicators(row,'err'); renderValue(display,field,prevRaw); display.dataset.raw=prevRaw;
    }
  }).catch(()=>{
    indicators(row,'err'); renderValue(display,field,prevRaw); display.dataset.raw=prevRaw;
  });
}

function updateToggle(chk){
  const field=chk.dataset.field; const value=!!chk.checked; const before=!value;
  json(API.update,{[field]:value}).then(r=>{
    if(r&&r.success){
      toast('Setting updated','success');
      if (r.theme_changed){
        if (r.css_version) hotSwapThemeCss(r.css_version);
      }
    } else { chk.checked=before; toast(r?.message||'Update failed','error'); }
  }).catch(()=>{ chk.checked=before; toast('Network error','error'); });
}

async function uploadFile(input, type) {
  const f = input.files?.[0];
  if (!f) return;

  const fd = new FormData();
  fd.append('image', f);       // <- must be 'image'
  fd.append('type', type);     // e.g. 'favicon', 'logo', ...

  toast(`Uploading ${type}…`, 'info');

  try {
    const res = await fetch('/admin/upload/image', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: fd,
      credentials: 'same-origin'
    });

    const r = await res.json();

    if (res.ok && r?.success) {
      toast(`${type} uploaded`, 'success');
      if (type === 'logo'    && document.getElementById('logoImg'))    document.getElementById('logoImg').src = r.url;
      if (type === 'favicon' && document.getElementById('faviconImg')) document.getElementById('faviconImg').src = r.url;
      if (r.css_version) hotSwapThemeCss(r.css_version);
    } else {
      toast(r?.message || `Upload failed (${res.status})`, 'error');
    }
  } catch (e) {
    toast('Upload error', 'error');
  } finally {
    input.value = ''; // reset file input
  }
}


function deleteFile(type){
  if(!confirm(`Delete ${type}?`)) return;
  const fd=new FormData(); fd.append('_method','DELETE');
  form(API.file(type),fd,'POST').then(r=>{
    if(r&&r.success){
      toast(`${type} deleted`,'success');
      if (type==='logo' && document.getElementById('logoImg')) document.getElementById('logoImg').src = '';
      if (type==='favicon' && document.getElementById('faviconImg')) document.getElementById('faviconImg').src = '';
      if (r.css_version) hotSwapThemeCss(r.css_version);
    } else toast(r?.message||'Delete failed','error');
  }).catch(()=>toast('Delete error','error'));
}

function sendTestEmail() {
  const email = document.getElementById('testEmailAddress').value;
  if (!email) { toast('Please enter an email address', 'warning'); return; }
  toast('Sending test email...', 'info');
  json(API.testEmail, { email }).then(r => {
    if (r && r.success) toast('Test email sent successfully', 'success');
    else toast(r?.message || 'Failed to send test email', 'error');
  }).catch(() => toast('Error sending test email', 'error'));
}

function testConfiguration(){
  const wrap=document.getElementById('testResults'); const out=document.getElementById('testResultsContent');
  wrap.classList.remove('d-none'); out.innerHTML='<div class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Running…</div>';
  json(API.test).then(r=>{
    const tests=r?.tests; if(!tests){ out.textContent='No test results.'; return; }
    const frag=document.createDocumentFragment();
    Object.entries(tests).forEach(([k,ok])=>{
      const row=document.createElement('div'); row.className='d-flex justify-content-between border-bottom py-2';
      const name=document.createElement('span'); name.textContent=k.replace(/_/g,' ').replace(/\b\w/g,m=>m.toUpperCase());
      const badge=document.createElement('span'); badge.className=`badge ${ok?'bg-success':'bg-danger'}`; badge.textContent=ok?'PASS':'FAIL';
      row.appendChild(name); row.appendChild(badge); frag.appendChild(row);
    });
    out.replaceChildren(frag);
    toast(r.success?'All tests passed':'Some tests failed', r.success?'success':'warning');
  }).catch(()=>{ out.textContent='Network error'; toast('Test error','error'); });
}

function resetToDefaults(){
  if(!confirm('Reset all settings to defaults?')) return;
  toast('Resetting…','info');
  json(API.reset).then(r=>{
    if(r&&r.success){
      toast('Settings reset','success');
      if (r.css_version) hotSwapThemeCss(r.css_version);
    } else toast(r?.message||'Reset failed','error');
  }).catch(()=>toast('Reset error','error'));
}
</script>
@endpush
