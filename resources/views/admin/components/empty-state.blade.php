{{-- resources/views/admin/components/empty-state.blade.php --}}
@php
    // Top-level props with sane defaults
    $icon      = $icon      ?? 'question-circle';
    $title     = $title     ?? 'Nothing here yet';
    $message   = $message   ?? '';
    $hasAction = isset($action) && is_array($action) && !empty($action);

    // Backward-compatible action mapping
    // Accepts either:
    //   ['text'=>..., 'icon'=>..., 'style'=>..., 'onclick'=>..., 'modal'=>...]
    // or legacy:
    //   ['label'=>..., 'type'=>..., 'action'=>...]  (maps to text/style/onclick)
    $actionText    = $hasAction ? ($action['text']   ?? ($action['label']  ?? 'Action')) : null;
    $actionStyle   = $hasAction ? ($action['style']  ?? ($action['type']   ?? 'primary')) : null;
    $actionIcon    = $hasAction ? ($action['icon']   ?? 'plus') : null;
    $actionOnclick = $hasAction ? ($action['onclick']?? ($action['action'] ?? null)) : null;
    $actionModal   = $hasAction ? ($action['modal']  ?? null) : null;
@endphp

<div class="text-center py-5">
    <i class="fas fa-{{ e($icon) }} fa-3x text-muted mb-3"></i>
    <h5 class="text-muted">{{ e($title) }}</h5>
    @if(!empty($message))
        <p class="text-muted">{{ e($message) }}</p>
    @endif

    @if($hasAction)
        <button
            type="button"
            class="btn btn-{{ e($actionStyle) }}"
            @if(!empty($actionModal)) data-bs-toggle="modal" data-bs-target="#{{ e($actionModal) }}" @endif
            @if(!empty($actionOnclick)) onclick="{{ $actionOnclick }}" @endif
        >
            @if(!empty($actionIcon))
                <i class="fas fa-{{ e($actionIcon) }} me-2"></i>
            @endif
            {{ e($actionText) }}
        </button>
    @endif
</div>
