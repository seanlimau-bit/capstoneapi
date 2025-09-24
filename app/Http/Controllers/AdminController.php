<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Config;

class AdminController extends Controller
{
    public function index()
    {
        // Get dynamic config
        $config = Config::current();
        
        // Define stats first
        $stats = [
            'total_questions' => DB::table('questions')->count(),
            'active_users' => User::where('status', 'active')->count(),
            'pending_qa' => DB::table('questions')->where('qa_status', 'unreviewed')->count(),
            'total_skills' => DB::table('skills')->count(),
        ];
        
        // Get recent activity
        $recent_activity = [
            [
                'action' => 'Question Approved',
                'description' => 'Math question #1234 was approved for Grade 5',
                'time_ago' => '2 hours ago',
                'icon' => 'fas fa-check-circle text-success'
            ],
            [
                'action' => 'New User Registration', 
                'description' => 'Student registered via ' . $config->site_name,
                'time_ago' => '4 hours ago',
                'icon' => 'fas fa-user-plus text-info'
            ],
            [
                'action' => 'Skills Updated',
                'description' => 'Added new algebra skills for Grade 8',
                'time_ago' => '6 hours ago',
                'icon' => 'fas fa-brain text-warning'
            ],
            [
                'action' => 'QA Review Completed',
                'description' => '15 questions passed quality assurance',
                'time_ago' => '8 hours ago',
                'icon' => 'fas fa-clipboard-check text-primary'
            ]
        ];
        
        // Management items using dynamic config
        $managementItems = [
            [
                'title' => 'Questions',
                'description' => 'Create and manage math questions',
                'icon' => 'fas fa-question-circle',
                'route' => 'admin.questions.index',
                'color' => 'primary',
                'count' => $stats['total_questions']
            ],
            [
                'title' => 'Skills',
                'description' => 'Organize learning skills and topics',
                'icon' => 'fas fa-brain',
                'route' => 'admin.skills.index',
                'color' => 'success',
                'count' => $stats['total_skills']
            ],
            [
                'title' => 'Users',
                'description' => 'Manage user accounts and permissions',
                'icon' => 'fas fa-users',
                'route' => 'admin.users.index',
                'color' => 'info',
                'count' => $stats['active_users']
            ],
            [
                'title' => 'QA Review',
                'description' => 'Quality assurance for content',
                'icon' => 'fas fa-clipboard-check',
                'route' => 'admin.qa.index',
                'color' => 'warning',
                'count' => $stats['pending_qa']
            ],
            [
                'title' => 'Assets',
                'description' => 'Manage images and media files',
                'icon' => 'fas fa-folder-open',
                'route' => 'admin.assets.index',
                'color' => 'secondary',
                'count' => null
            ],
            [
                'title' => 'Settings',
                'description' => 'Configure ' . $config->site_shortname . ' system',
                'icon' => 'fas fa-cogs',
                'route' => 'admin.settings.general',
                'color' => 'dark',
                'count' => null
            ]
        ];
        
        // Pass config to view (note: config is already available via AppServiceProvider)
        return view('admin.dashboard.index', compact('stats', 'recent_activity', 'managementItems', 'config'));
    }
    
    public function dashboard()
    {
        // Redirect to main index method
        return $this->index();
    }
}

// Also create a settings controller for handling updates
// app/Http/Controllers/Admin/SettingsController.php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function general()
    {
        $config = Config::current();
        return view('admin.settings.general', compact('config'));
    }

    public function update(Request $request)
    {
        $config = Config::current();
        
        $validated = $request->validate([
            'site_name' => 'nullable|string|max:255',
            'site_shortname' => 'nullable|string|max:255',
            'site_url' => 'nullable|url|max:255',
            'email' => 'nullable|email|max:255',
            'main_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'timezone' => 'nullable|string|max:50',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|in:12,24',
            'number_of_teaching_days' => 'nullable|integer|min:1|max:365',
            'no_rights_to_pass' => 'nullable|integer|min:1|max:10',
            'no_wrongs_to_fail' => 'nullable|integer|min:1|max:10',
            'self_paced' => 'nullable|boolean',
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_message' => 'nullable|string',
        ]);

        $config->updateSettings($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Settings updated successfully']);
        }

        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $config = Config::current();
        
        // Delete old logo if exists
        if ($config->site_logo && Storage::disk('public')->exists($config->site_logo)) {
            Storage::disk('public')->delete($config->site_logo);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $config->updateSettings(['site_logo' => 'storage/' . $path]);

        return response()->json([
            'success' => true,
            'message' => 'Logo uploaded successfully',
            'url' => asset('storage/' . $path)
        ]);
    }

    public function uploadFavicon(Request $request)
    {
        $request->validate([
            'favicon' => 'required|image|mimes:ico,png|max:512',
        ]);

        $config = Config::current();
        
        if ($config->favicon && Storage::disk('public')->exists($config->favicon)) {
            Storage::disk('public')->delete($config->favicon);
        }

        $path = $request->file('favicon')->store('favicons', 'public');
        $config->updateSettings(['favicon' => 'storage/' . $path]);

        return response()->json([
            'success' => true,
            'message' => 'Favicon uploaded successfully'
        ]);
    }

    public function uploadLoginBackground(Request $request)
    {
        $request->validate([
            'login_background' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
        ]);

        $config = Config::current();
        
        if ($config->login_background && Storage::disk('public')->exists($config->login_background)) {
            Storage::disk('public')->delete($config->login_background);
        }

        $path = $request->file('login_background')->store('backgrounds', 'public');
        $config->updateSettings(['login_background' => 'storage/' . $path]);

        return response()->json([
            'success' => true,
            'message' => 'Login background uploaded successfully'
        ]);
    }

    public function test()
    {
        $config = Config::current();
        
        $tests = [
            'database_connection' => true,
            'config_loaded' => !empty($config->site_name),
            'timezone_valid' => in_array($config->timezone, timezone_identifiers_list()),
            'email_format' => filter_var($config->email, FILTER_VALIDATE_EMAIL) !== false,
            'color_format' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $config->main_color),
            'logo_exists' => $config->hasLogo(),
        ];

        return response()->json([
            'success' => !in_array(false, $tests),
            'tests' => $tests,
            'message' => 'Configuration test completed'
        ]);
    }

    public function reset()
    {
        $config = Config::current();
        
        $config->updateSettings([
            'site_name' => 'All Gifted Math',
            'site_shortname' => 'AGM',
            'main_color' => '#960000',
            'timezone' => 'UTC',
            'date_format' => 'd/m/Y',
            'time_format' => '12',
            'number_of_teaching_days' => 180,
            'no_rights_to_pass' => 2,
            'no_wrongs_to_fail' => 2,
            'self_paced' => true,
            'maintenance_mode' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Settings reset to defaults'
        ]);
    }
}