<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Difficulty;
use App\Models\Type;
use App\Models\Level;
use App\Models\Status;
use App\Models\Role;
use App\Models\Unit;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function index()
    {
        $difficulties = Difficulty::with('status')
            ->select('id', 'status_id', 'difficulty', 'short_description', 'description')
            ->get();

        $types = Type::with('status')
            ->select('id', 'status_id', 'type', 'description')
            ->get();

        $levels = Level::with('status')
            ->select('id', 'status_id', 'level', 'description', 'age', 'start_maxile_level', 'end_maxile_level')
            ->get();

        // No relation to eager-load on Status itself
        $statuses = Status::select('id', 'status', 'description')->get();

        $roles = Role::withCount('users')
            ->select('id', 'role', 'description')
            ->get()
            ->map(function ($role) {
                unset($role->permissions);
                return $role;
            });

        $units = Unit::select('id', 'unit', 'description')
            ->get();

        return view('admin.configuration.index', compact(
            'difficulties',
            'types',
            'levels',
            'statuses',
            'roles',
            'units'
        ));
    }
}