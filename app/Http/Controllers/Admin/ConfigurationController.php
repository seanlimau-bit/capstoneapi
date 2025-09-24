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
        // Explicitly select only the fields we need
        $difficulties = Difficulty::select('id', 'difficulty', 'short_description', 'description')->get();
        $types = Type::select('id', 'type', 'description')->get();
        $levels = Level::select('id', 'level', 'description', 'age', 'start_maxile_level', 'end_maxile_level')->get();
        $statuses = Status::select('id', 'status', 'description')->get();
        
        // Don't load permissions relationship at all
        $roles = Role::select('id', 'role', 'description')
            ->withCount('users')
            ->get()
            ->map(function($role) {
                // Ensure no relationships are accidentally loaded
                unset($role->permissions);
                return $role;
            });
            
        $units = Unit::select('id', 'unit', 'description')->get();

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