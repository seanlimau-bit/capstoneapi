{{-- resources/views/components/admin/questions/row.blade.php --}}
@props([
  'question',
  'skillId' => null,
  'showCheckbox' => false,
  'showSource' => true,
  'showAuthor' => true,
  'actions' => ['view' => true, 'duplicate' => true, 'delete' => true, 'generate' => false],
])

<tr data-question-id="{{ $question->id }}">
  @if($showCheckbox)
    <td>
      <div class="form-check">
        <input type="checkbox" value="{{ $question->id }}" class="form-check-input question-checkbox">
      </div>
    </td>
  @endif

  <td>
    <div class="fw-semibold mb-1">{{ \Illuminate\Support\Str::limit(strip_tags($question->question ?? ''), 60) }}</div>
    @if(!empty($question->correct_answer))
      <small class="text-success">Answer: {{ \Illuminate\Support\Str::limit($question->correct_answer, 40) }}</small>
    @endif
    <div class="mt-1">
      <small class="text-muted">ID: {{ $question->id }}</small>
      @if($question->type)
        <span class="badge bg-info ms-2">{{ $question->type->description ?? $question->type->type }}</span>
      @endif
    </div>
  </td>

  <td>
    <div class="d-flex flex-column gap-1">
      @if($question->skill)
        <div><strong>Skill:</strong> {{ \Illuminate\Support\Str::limit($question->skill->skill ?? 'Unknown', 25) }}</div>
        @if($question->skill->tracks && $question->skill->tracks->count())
          <div class="d-flex flex-wrap gap-1">
            @foreach($question->skill->tracks->take(2) as $track)
              <span class="badge bg-secondary">
                {{ \Illuminate\Support\Str::limit($track->track, 15) }}@if($track->level) (L{{ $track->level->level }}) @endif
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
        $diff = optional($question->difficulty)->short_description ?? optional($question->difficulty)->description ?? 'Unknown';
        $cls = str_contains(strtolower($diff), 'easy') ? 'bg-success' : (str_contains(strtolower($diff), 'medium') ? 'bg-warning' : 'bg-danger');
      @endphp
      <div><span class="badge {{ $question->difficulty ? $cls : 'bg-secondary' }}">{{ $question->difficulty ? $diff : 'No difficulty set' }}</span></div>
    </div>
  </td>

  <td>
    @php
      $map = [
        'approved' => ['success','check-circle'],
        'flagged' => ['danger','flag'],
        'needs_revision' => ['warning','edit'],
        'unreviewed' => ['info','clock'],
        'ai_generated' => ['primary','robot'],
      ];
      [$c,$i] = $map[$question->qa_status ?? ''] ?? ['secondary','question'];
      $label = $question->qa_status ? ucfirst(str_replace('_',' ',$question->qa_status)) : 'Unknown';
    @endphp
    <span class="badge bg-{{ $c }}"><i class="fas fa-{{ $i }} me-1"></i>{{ $label }}</span>
  </td>

  @if($showSource)
    <td><small class="text-muted">{{ $question->source ?? 'Unknown' }}</small></td>
  @endif

  @if($showAuthor)
    <td>
      @php $author = $question->author; $name = $author->name ?? 'Unknown'; @endphp
      <div class="d-flex align-items-center">
        @if(!empty($author?->image))
          <img src="{{ $author->image }}" alt="{{ $name }}" class="rounded-circle me-2" width="32" height="32">
        @else
          <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;font-size:12px;">
            {{ mb_substr($name,0,1) }}
          </div>
        @endif
        <div>
          <div class="fw-bold small">{{ $name }}</div>
          <div class="text-muted small">{{ optional($question->created_at)->format('M j, Y') }}</div>
        </div>
      </div>
    </td>
  @endif

  <td class="text-center">
    <div class="btn-group btn-group-sm">
      @if($actions['view'])
        <button type="button" class="btn btn-outline-info" onclick="viewQuestion({{ $question->id }})" title="View"><i class="fas fa-eye"></i></button>
      @endif
      @if($actions['duplicate'])
        <button type="button" class="btn btn-outline-secondary" onclick="copyQuestion({{ $question->id }})" title="Duplicate"><i class="fas fa-copy"></i></button>
      @endif
      @if($actions['delete'])
        <button type="button" class="btn btn-outline-danger" onclick="deleteQuestion({{ $question->id }})" title="Delete"><i class="fas fa-trash"></i></button>
      @endif
      @if($actions['generate'] && $skillId)
        <button type="button" class="btn btn-outline-success" onclick="SkillManager.generateSimilar({{ $question->id }}, {{ $skillId }}, '{{ addslashes(\Illuminate\Support\Str::limit(strip_tags($question->question ?? ''),50)) }}')" title="Generate Similar"><i class="fas fa-wand-magic-sparkles"></i></button>
      @endif
    </div>
  </td>
</tr>
