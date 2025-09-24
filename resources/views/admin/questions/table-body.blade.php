@php
  // defaults for includes
  $skillId = $skillId ?? null;
  $withCheckbox = $withCheckbox ?? true;
@endphp

@forelse($questions as $question)
  @include('admin.questions.row', [
    'question' => $question,
    'skillId' => $skillId,
    'showCheckbox' => $withCheckbox,
    'actions' => [
      'view' => true,
      'duplicate' => true,
      'delete' => true,
      'generate' => !is_null($skillId),
    ],
  ])
@empty
  <tr>
    <td colspan="7" class="text-center py-4">
      <i class="fas fa-search me-2"></i>No questions found
    </td>
  </tr>
@endforelse
