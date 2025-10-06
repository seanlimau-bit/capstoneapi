<?php

namespace App\Livewire\Admin\Fields;

use App\Models\Field;
use App\Models\Status;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class FieldIndex extends Component
{
    use WithPagination;

    // Keep filters/sort in the URL
    #[Url(as: 'status_id')] public $status_id = '';
    #[Url] public $search = '';
    #[Url] public $sort = 'created_at';
    #[Url] public $direction = 'desc';

    public $perPage = 50;

    // Ensure integer compare in SQL
    protected $casts = [
        'status_id' => 'integer',
    ];

    // Inline edit (field/description/status_id)
    public function updateField($id, $key, $value): void
    {
        $allowed = ['field','description','status_id'];
        if (!in_array($key, $allowed, true)) return;

        $rules = [
            'field'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'status_id'   => 'nullable|exists:statuses,id',
        ];

        // Validate just the value against the chosen rule
        $this->validateOnly('value', ['value' => $rules[$key]], ['value.*' => 'Invalid value']);

        Field::whereKey($id)->update([$key => $value]);
        // No flash to keep UI quiet
    }

    // One hook that resets pagination when filters change
    public function updated($name, $value): void
    {
        if (in_array($name, ['status_id', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $key): void
    {
        if ($this->sort === $key) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $key;
            $this->direction = 'asc';
        }
        $this->resetPage();
    }

    public function getTotalsProperty(): array
    {
        return [
            'total'   => Field::count(),
            'public'  => Field::where('status_id', 3)->count(),
            'draft'   => Field::where('status_id', 4)->count(),
            'private' => Field::whereIn('status_id', [1, 2])->count(),
        ];
    }

    public function render()
    {
        $q = Field::query()
            ->with('status')
            ->withCount('tracks');

        if (!empty($this->status_id)) {
            $q->where('status_id', (int) $this->status_id);
        }

        $s = trim($this->search);
        if ($s !== '') {
            $q->where(fn($qq) => $qq
                ->where('field', 'like', "%{$s}%")
                ->orWhere('description', 'like', "%{$s}%")
            );
        }

        $sortable = ['field','description','status_id','tracks_count','created_at'];
        $sort = in_array($this->sort, $sortable, true) ? $this->sort : 'created_at';
        $dir  = $this->direction === 'asc' ? 'asc' : 'desc';

        $fields   = $q->orderBy($sort, $dir)->paginate($this->perPage);
        $statuses = Status::orderBy('status')->pluck('status', 'id');

        return view('livewire.admin.fields.field-index', compact('fields', 'statuses'));
        // If you route directly to this component (no controller), chain:
        // ->layout('layouts.admin');
    }
}
