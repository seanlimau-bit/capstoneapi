<div>
	{{-- Filters --}}
	<div class="row g-2 mb-3">
		<div class="col-md-3">
			<select class="form-select" wire:model.number="status_id">
				<option value="">All Status</option>
				@foreach($statuses as $id => $name)
				<option value="{{ $id }}">{{ $name }}</option>
				@endforeach
			</select>
		</div>
		<div class="col-md-6">
			<input type="search" class="form-control" placeholder="Search fields..." wire:model.debounce.400ms="search">
		</div>
		<div class="col-md-3 d-flex gap-2">
			<button type="button" class="btn btn-outline-secondary" wire:click="$set('status_id','');$set('search','');$set('sort','created_at');$set('direction','desc');$set('page',1)">Clear</button>
		</div>
	</div>

	@php
	$statusColors = [
	'Only Me'    => 'badge bg-secondary',
	'Restricted' => 'badge bg-warning text-dark',
	'Public'     => 'badge bg-success',
	'Draft'      => 'badge bg-info text-dark',
	];
	$badge = fn($name) => $statusColors[$name] ?? 'badge bg-light text-dark';
	$sortIcon = fn($key) => $sort===$key ? ($direction==='asc'?'fa-sort-up text-primary':'fa-sort-down text-primary') : 'fa-sort text-muted';
	@endphp

	{{-- Stats --}}
	<div class="row mb-2">
		<div class="col">
			<div class="alert alert-light border py-2">
				<strong>Totals:</strong>
				Total {{ $this->totals['total'] }},
				Public {{ $this->totals['public'] }},
				Draft {{ $this->totals['draft'] }},
				Private {{ $this->totals['private'] }}
			</div>
		</div>
	</div>

	{{-- Table --}}
	<div class="table-responsive">
		<table class="table table-hover mb-0">
			<thead class="table-light">
				<tr>
					<th width="30"></th>
					<th role="button" wire:click="sortBy('field')">
						Field <i class="fas {{ $sortIcon('field') }} ms-1"></i>
					</th>
					<th role="button" wire:click="sortBy('description')">
						Description <i class="fas {{ $sortIcon('description') }} ms-1"></i>
					</th>
					<th role="button" wire:click="sortBy('status_id')">
						Status <i class="fas {{ $sortIcon('status_id') }} ms-1"></i>
					</th>
					<th role="button" wire:click="sortBy('tracks_count')">
						Tracks <i class="fas {{ $sortIcon('tracks_count') }} ms-1"></i>
					</th>
					<th role="button" wire:click="sortBy('created_at')">
						Created <i class="fas {{ $sortIcon('created_at') }} ms-1"></i>
					</th>
					<th width="150" class="text-center">Actions</th>
				</tr>
			</thead>
			<tbody>
				@forelse($fields as $f)
				<tr wire:key="row-{{ $f->id }}">
					<td><input type="checkbox"></td>

					{{-- Inline editable cells using contenteditable + blur hook --}}
					<td>
						<span contenteditable
						onblur="@this.updateField({{ $f->id }}, 'field', this.innerText.trim())">
						{{ $f->field }}
					</span>
				</td>

				<td>
					<span contenteditable
					onblur="@this.updateField({{ $f->id }}, 'description', this.innerText.trim())">
					{{ $f->description }}
				</span>
			</td>

			<td>
				{{-- show badge, click to cycle through a simple select if you want later --}}
				<span class="{{ $badge($f->status->status ?? 'Unknown') }}">
					{{ $f->status->status ?? 'Unknown' }}
				</span>
			</td>

			<td>{{ $f->tracks_count }}</td>
			<td>{{ optional($f->created_at)->format('M d, Y') }}</td>
			<td class="text-center">
				<div class="btn-group btn-group-sm">
					<a class="btn btn-outline-info" href="{{ route('admin.fields.show',$f) }}"><i class="fas fa-eye"></i></a>
					<form action="{{ route('admin.fields.duplicate',$f) }}" method="POST" class="d-inline">@csrf
						<button class="btn btn-outline-secondary"><i class="fas fa-copy"></i></button>
					</form>
					<form action="{{ route('admin.fields.destroy',$f) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this field?')">
						@csrf @method('DELETE')
						<button class="btn btn-outline-danger"><i class="fas fa-trash"></i></button>
					</form>
				</div>
			</td>
		</tr>
		@empty
		<tr><td colspan="7" class="text-center py-4">No fields found</td></tr>
		@endforelse
	</tbody>
</table>
</div>

<div class="d-flex justify-content-between align-items-center mt-2">
	<div class="text-muted">
		Showing {{ $fields->firstItem() ?? 0 }} to {{ $fields->lastItem() ?? 0 }} of {{ $fields->total() }}
	</div>
	{{ $fields->links() }}
</div>
</div>
