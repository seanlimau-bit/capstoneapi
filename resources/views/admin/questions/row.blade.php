{{-- resources/views/components/admin/questions/row.blade.php --}}
@props([
'question',
'skillId' => null,
'showCheckbox' => false,
'showSource' => true,
'showAuthor' => true,
'actions' => ['view' => true, 'duplicate' => true, 'delete' => true, 'generate' => false],
])

@php
use Illuminate\Support\Str;
$qid = $question->id;
@endphp

<tr data-id="{{ $qid }}">
  @if($showCheckbox)
  <td>
    <div class="form-check">
      <input type="checkbox" value="{{ $qid }}" class="form-check-input question-checkbox">
    </div>
  </td>
  @endif

  {{-- Question Details --}}
  <td>
    <div class="fw-semibold mb-1 d-flex align-items-start gap-2">
      <span>{{ Str::limit(strip_tags($question->question ?? ''), 60) }}</span>

      @if($question->is_diagnostic)
        <span class="badge bg-warning text-dark" title="Diagnostic sentinel">SENTINEL</span>
      @endif

      @if(($question->source ?? null) === 'auto:sentinel')
        <span class="badge bg-secondary" title="Auto-generated sentinel">auto</span>
      @endif
    </div>

    @if(!empty($question->correct_answer))
      <small class="text-success">Answer: {{ Str::limit($question->correct_answer, 40) }}</small>
    @endif

    <div class="mt-1 d-flex align-items-center gap-2">
      <small class="text-muted">ID: {{ $qid }}</small>

      @if($question->type)
        <span class="badge bg-info">{{ $question->type->description ?? $question->type->type }}</span>
      @endif

      @if($question->is_diagnostic)
        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">
          diagnostic
        </span>
      @endif
    </div>
  </td>


  {{-- Skill & Difficulty --}}
  <td>
    <div class="d-flex flex-column gap-1">
      @if($question->skill)
      <div><strong>Skill - </strong> {{ Str::limit($question->skill->id . ': ' . $question->skill->skill ?? 'Unknown', 25) }}</div>

      @if($question->skill->tracks && $question->skill->tracks->count())
      <div class="d-flex flex-wrap gap-1">
        @foreach($question->skill->tracks->take(2) as $track)
        <span class="badge bg-secondary">
          {{ Str::limit($track->track, 15) }}@if($track->level) (L{{ $track->level->level }}) @endif
        </span>
        @endforeach
        @if($question->skill->tracks->count() > 2)
        <span class="badge bg-light text-dark">+{{ $question->skill->tracks->count() - 2 }}</span>
        @endif
      </div>
      @endif
      @else
      <span class="text-muted">No skill assigned</span>
      @endif

      @php
      $diffLabel = optional($question->difficulty)->short_description
      ?? optional($question->difficulty)->description
      ?? null;
      $diffText = $diffLabel ?: 'No difficulty set';
      $diffClass = 'bg-secondary';
      if ($diffLabel) {
      $l = strtolower($diffLabel);
      $diffClass = str_contains($l, 'easy') ? 'bg-success'
      : (str_contains($l, 'medium') ? 'bg-warning' : 'bg-danger');
    }
    @endphp
    <div><span class="badge {{ $diffClass }}">{{ $diffText }}</span></div>
  </div>
</td>
{{-- Status --}}
<td>
  @php
  $statusName = $question->status->status ?? 'Unknown';
  // simple mapping (tweak as you like)
  $map = [
  'Public'   => 'success',
  'Draft'    => 'secondary',
  'Only Me'  => 'dark',
  'Restricted' => 'warning',
  'Draft'  => 'danger',
  ];
  $cls = $map[$statusName] ?? 'info';
  @endphp
  <span class="badge bg-{{ $cls }}">{{ $statusName }}</span>
</td>

{{-- QA Status --}}
<td>
  @php
  $qaMap = [
  'approved'       => ['success','check-circle'],
  'flagged'        => ['danger','flag'],
  'needs_revision' => ['warning','edit'],
  'unreviewed'     => ['info','clock'],
  'ai_generated'   => ['primary','robot'],
  ];
  [$c,$i] = $qaMap[$question->qa_status ?? ''] ?? ['secondary','question'];
  $qaLabel = $question->qa_status ? ucfirst(str_replace('_',' ',$question->qa_status)) : 'Unknown';
  @endphp
  <span class="badge bg-{{ $c }}"><i class="fas fa-{{ $i }} me-1"></i>{{ $qaLabel }}</span>
</td>

{{-- Source --}}
@if($showSource)
<td>
  @php
  $author = $question->author;
  $name = $author->name ?? 'Unknown';
  @endphp
  <div class="d-flex flex-column">
    <div class="fw-bold small">{{ $name }}</div>
    <div class="text-muted small">{{ optional($question->created_at)->format('M j, Y') }}</div>
  </div>

  <small class="text-muted">{{ $question->source ?? 'Unknown' }}</small></td>
  @endif

  {{-- Actions --}}
  <td class="text-center" actions-col>
    <div class="btn-group btn-group-sm" role="group">
      @if(!empty($actions['view']))
      <button type="button" class="btn btn-outline-info" onclick="viewQuestion({{ $qid }})" title="View">
        <i class="fas fa-eye"></i>
      </button>
      @endif

      @if(!empty($actions['duplicate']))
      {{-- This will be auto-replaced to "Generate" by sanitizeRows() --}}
      <button type="button" class="btn btn-outline-secondary btn-duplicate" title="Duplicate">
        <i class="fas fa-copy"></i>
      </button>
      @endif

      @if(!empty($actions['delete']))
      <button type="button"
      class="btn btn-outline-danger"
      data-action="delete"
      data-id="{{ $qid }}"
      title="Delete">
      <i class="fas fa-trash"></i>
    </button>
    @endif

    @if(!empty($actions['generate']) && $skillId)
    <button type="button"
    class="btn btn-outline-success"
    onclick="openGenerateModal({{ $qid }})"
    title="Generate Similar">
    <i class="fas fa-wand-magic-sparkles"></i>
  </button>
  @endif
</div>
</td>
</tr>
