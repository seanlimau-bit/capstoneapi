<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\Field;
use App\Models\Question;
use App\Models\Track;
use App\Models\Type;
use App\Models\Difficulty;
use App\Models\Status;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LookupOptionsService
{
    public function __construct(private int $ttl = 600) {}

    public function skills(): Collection
    {
        return Cache::remember('opts.skills.public', $this->ttl, function () {
            return Skill::public()->orderBy('skill')
                ->get(['id','skill'])
                ->map(fn($s) => ['id'=>$s->id, 'text'=>$s->skill]);
        });
    }

    public function types(): Collection
    {
        return Cache::remember('opts.types.public', $this->ttl, function () {
            return Type::public()->orderBy('type')
                ->get(['id','type'])
                ->map(fn($t) => ['id'=>$t->id, 'text'=>$t->type]);
        });
    }

    public function difficulties(): Collection
    {
        return Cache::remember('opts.difficulties.public', $this->ttl, function () {
            return Difficulty::public()->orderBy('id')
                ->get(['id','short_description'])
                ->map(fn($d) => ['id'=>$d->id, 'text'=>$d->short_description ?: "Difficulty #{$d->id}"]);
        });
    }

    public function statuses(): Collection
    {
        return Cache::remember('opts.statuses.public', $this->ttl, function () {
            // Prefer a model scope if you have it.
            $query = method_exists(Status::class, 'scopePublic')
                ? Status::public()
                : Status::query(); // fallback, remove if you want strictly public-only and you have the scope

            return $query->orderBy('status')
                ->get(['id','status'])
                ->map(fn($s) => ['id'=>$s->id, 'text'=>$s->status]);
        });
    }

    public function bust(): void
    {
        Cache::forget('opts.skills.public');
        Cache::forget('opts.types.public');
        Cache::forget('opts.difficulties.public');
        Cache::forget('opts.statuses.public');
        Cache::forget('opts.questions.index');
        Cache::forget('opts.questions.show');
    }

    public function filterOptionsForQuestionsIndex(): array
    {
        return Cache::remember('opts.questions.index', 600, function () {
            // All selectors limited to public data
            $statusesQuery = method_exists(Status::class, 'scopePublic')
                ? Status::public()
                : Status::query(); // see note above

            return [
                'fields'       => Field::public()->orderBy('field')->get(['id','field']),
                'skills'       => Skill::public()->orderBy('skill')->get(['id','skill']),
                'types'        => Type::public()->orderBy('type')->get(['id','type']),
                'difficulties' => Difficulty::public()->orderBy('id')->get(['id','short_description']),
                'statuses'     => $statusesQuery->orderBy('status')->get(['id','status as text']),
                'qa_statuses'  => Question::getQaStatuses(),
                'sources'      => Question::query()
                    ->whereNotNull('source')->where('source','!=','')
                    ->distinct()->orderBy('source')->pluck('source')->all(),
            ];
        });
    }

    public function bustQuestionsIndexCache(): void
    {
        Cache::forget('opts.questions.index');
    }

    public function questionShowOptions(): array
    {
        return Cache::remember('opts.questions.show', 600, function () {
            $statusesQuery = method_exists(Status::class, 'scopePublic')
                ? Status::public()
                : Status::query();

            return [
                'skills'       => Skill::public()->orderBy('skill')->get(['id','skill']),
                'difficulties' => Difficulty::public()->orderBy('id')->get(['id','short_description']),
                'types'        => Type::public()->orderBy('id')->get(['id','type']),
                'statuses'     => $statusesQuery->orderBy('status')->get(['id','status']),
                'qaStatuses'   => [
                    ['value'=>'unreviewed','label'=>'Unreviewed','class'=>'bg-secondary'],
                    ['value'=>'ai_generated','label'=>'AI Generated','class'=>'bg-info'],
                    ['value'=>'approved','label'=>'Approved','class'=>'bg-success'],
                    ['value'=>'flagged','label'=>'Flagged','class'=>'bg-danger'],
                    ['value'=>'needs_revision','label'=>'Needs Revision','class'=>'bg-warning'],
                ],
            ];
        });
    }
}
