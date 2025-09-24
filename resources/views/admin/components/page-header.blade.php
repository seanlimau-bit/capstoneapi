{{-- resources/views/admin/components/page-header.blade.php --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        @if(!empty($icon))
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-{{ $icon }} text-primary me-2"></i>
                                <h2 class="mb-0">{{ $title ?? 'Page Title' }}</h2>
                            </div>
                        @else
                            <h2 class="mb-1">{{ $title ?? 'Page Title' }}</h2>
                        @endif

                        @if(!empty($subtitle))
                            <p class="text-muted mb-0">{{ $subtitle }}</p>
                        @endif

                        @if(!empty($breadcrumbs) && is_iterable($breadcrumbs))
                            <nav aria-label="breadcrumb" class="mt-2">
                                <ol class="breadcrumb mb-0">
                                    @foreach($breadcrumbs as $crumb)
                                        @php
                                            $crumbTitle = $crumb['title'] ?? '';
                                            $crumbUrl   = $crumb['url']   ?? '';
                                        @endphp
                                        @if($loop->last || empty($crumbUrl))
                                            <li class="breadcrumb-item active" aria-current="page">{{ $crumbTitle }}</li>
                                        @else
                                            <li class="breadcrumb-item">
                                                <a href="{{ $crumbUrl }}">{{ $crumbTitle }}</a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ol>
                            </nav>
                        @endif
                    </div>

                    @if(!empty($actions) && is_iterable($actions))
                        <div class="btn-toolbar ms-3" role="toolbar">
                            @foreach($actions as $action)
                                @php
                                    $atype  = $action['type']  ?? null;
                                    $aclass = $action['class'] ?? 'primary';
                                    $atext  = $action['text']  ?? ($action['title'] ?? 'Action');
                                    $aicon  = $action['icon']  ?? null;
                                @endphp

                                @if($atype === 'dropdown')
                                    {{-- Dropdown Button --}}
                                    <div class="dropdown me-2">
                                        <button class="btn btn-{{ $aclass }} btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            @if($aicon)<i class="fas fa-{{ $aicon }} me-1"></i>@endif
                                            {{ $atext }}
                                        </button>
                                        <ul class="dropdown-menu">
                                            @foreach(($action['items'] ?? []) as $item)
                                                @if(is_string($item) && $item === 'divider')
                                                    <li><hr class="dropdown-divider"></li>
                                                @else
                                                    @php
                                                        $itemText   = $item['text'] ?? ($item['title'] ?? 'Item');
                                                        $itemUrl    = $item['url']  ?? '#';
                                                        $itemOnclick= $item['onclick'] ?? null;
                                                        $itemIcon   = $item['icon'] ?? null;
                                                    @endphp
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="{{ $itemUrl }}"
                                                           @if($itemOnclick) onclick="{{ $itemOnclick }}" @endif>
                                                            @if($itemIcon)<i class="fas fa-{{ $itemIcon }} me-2"></i>@endif
                                                            {{ $itemText }}
                                                        </a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>

                                @elseif(!empty($action['modal']))
                                    {{-- Modal Trigger Button --}}
                                    <button type="button"
                                            class="btn btn-{{ $aclass }} btn-sm me-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#{{ $action['modal'] }}">
                                        @if($aicon)<i class="fas fa-{{ $aicon }} me-1"></i>@endif
                                        {{ $atext }}
                                    </button>

                                @elseif(!empty($action['onclick']))
                                    {{-- JavaScript Action Button --}}
                                    <button type="button"
                                            class="btn btn-{{ $aclass }} btn-sm me-2"
                                            onclick="{{ $action['onclick'] }}">
                                        @if($aicon)<i class="fas fa-{{ $aicon }} me-1"></i>@endif
                                        {{ $atext }}
                                    </button>

                                @else
                                    {{-- Regular Link Button --}}
                                    @php $aurl = $action['url'] ?? '#'; @endphp
                                    <a href="{{ $aurl }}"
                                       class="btn btn-{{ $aclass }} btn-sm me-2"
                                       @if(!empty($action['target'])) target="{{ $action['target'] }}" @endif>
                                        @if($aicon)<i class="fas fa-{{ $aicon }} me-1"></i>@endif
                                        {{ $atext }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
