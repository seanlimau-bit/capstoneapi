<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    /** Theme-impacting fields */
    private const THEME_FIELDS = [
        'main_color','secondary_color','tertiary_color','success_color','error_color','warning_color','info_color',
        'black_color','white_color','primary_font','secondary_font','body_font_size',
        'h1_font_size','h2_font_size','h3_font_size','h4_font_size','h5_font_size',
        'body_line_height','heading_line_height','font_weight_normal','font_weight_medium','font_weight_bold',
        'border_radius','sidebar_width','content_max_width'
    ];

    /** .env / config() defaults for mail display */
    private function envMailDefaults(): array
    {
        return [
            'mail_host'         => config('mail.mailers.smtp.host')       ?? config('mail.host') ?? env('MAIL_HOST'),
            'mail_port'         => config('mail.mailers.smtp.port')       ?? env('MAIL_PORT'),
            'mail_username'     => config('mail.mailers.smtp.username')   ?? env('MAIL_USERNAME'),
            'mail_from_name'    => config('mail.from.name')               ?? env('MAIL_FROM_NAME') ?? 'System',
            'mail_encryption'   => config('mail.mailers.smtp.encryption') ?? env('MAIL_ENCRYPTION', 'tls'),
            'mail_from_address' => config('mail.from.address')            ?? env('MAIL_FROM_ADDRESS', 'ags2025mail@gmail.com'),
        ];
    }

    /** GET: screen */
    public function general()
    {
        $cfg = Schema::hasTable('configs') ? (Config::current() ?? Config::create()) : null;

        // Used only for previewing logo/favicon cache-busting in this admin view
        $assetVersion = $cfg?->updated_at?->getTimestamp() ?? time();

        $dbFields = Schema::hasTable('configs') ? Schema::getColumnListing('configs') : [];

        return view('admin.settings.general', [
            'config'        => $cfg,
            'assetVersion'  => $assetVersion,
            'dbFields'      => $dbFields,
            'mailDefaults'  => $this->envMailDefaults(),
        ]);
    }

    /** POST JSON: update one setting */
    public function updateGeneral(Request $request)
    {
        if (!Schema::hasTable('configs')) {
            return response()->json(['success' => false, 'message' => 'Config table not ready'], 503);
        }

        $cfg = Config::current() ?? Config::create();

        $rules = [
            // Basic
            'site_name'         => 'sometimes|string|max:120',
            'site_shortname'    => 'sometimes|string|max:50',
            'site_url'          => 'sometimes|url|max:255',
            'email'             => 'sometimes|email|max:255',
            'timezone'          => 'sometimes|string|max:64',
            'date_format'       => ['sometimes', Rule::in(['d/m/Y','m/d/Y','Y-m-d','d-m-Y'])],
            'time_format'       => ['sometimes', Rule::in(['12','24'])],

            // Colors
            'main_color'        => ['sometimes','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'black_color'       => ['sometimes','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'white_color'       => ['sometimes','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondary_color'   => ['sometimes','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'tertiary_color'    => ['sometimes','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'success_color'     => ['sometimes','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'error_color'       => ['sometimes','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'warning_color'     => ['sometimes','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'info_color'        => ['sometimes','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],

            // Typography
            'primary_font'      => 'sometimes|string|max:255',
            'secondary_font'    => 'sometimes|string|max:255',
            'body_font_size'    => 'sometimes|string|max:10',
            'h1_font_size'      => 'sometimes|string|max:10',
            'h2_font_size'      => 'sometimes|string|max:10',
            'h3_font_size'      => 'sometimes|string|max:10',
            'h4_font_size'      => 'sometimes|string|max:10',
            'h5_font_size'      => 'sometimes|string|max:10',
            'body_line_height'  => 'sometimes|string|max:10',
            'heading_line_height'=> 'sometimes|string|max:10',
            'font_weight_normal'=> 'sometimes|string|max:10',
            'font_weight_medium'=> 'sometimes|string|max:10',
            'font_weight_bold'  => 'sometimes|string|max:10',

            // Layout
            'border_radius'     => 'sometimes|string|max:10',
            'sidebar_width'     => 'sometimes|string|max:10',
            'content_max_width' => 'sometimes|string|max:10',

            // Email
            'mail_host'         => 'sometimes|string|max:255|nullable',
            'mail_port'         => 'sometimes|integer|min:1|max:65535|nullable',
            'mail_username'     => 'sometimes|string|max:255|nullable',
            'mail_from_name'    => 'sometimes|string|max:255|nullable',
            'mail_encryption'   => ['sometimes', Rule::in(['none','tls','ssl'])],
            'mail_from_address' => 'sometimes|email|max:255|nullable',

            // Learning (number_of_teaching_days removed)
            'questions_per_test' => 'sometimes|integer|min:1|max:100',
            'no_rights_to_pass'  => 'sometimes|integer|min:1|max:10',
            'no_wrongs_to_fail'  => 'sometimes|integer|min:1|max:10',

            // Toggles
            'self_paced'               => 'sometimes|boolean',
            'maintenance_mode'         => 'sometimes|boolean',
            'maintenance_message'      => 'sometimes|string|nullable',
        ];

        $validated = $request->validate($rules);
        if (count($validated) !== 1) {
            return response()->json(['success' => false, 'message' => 'Provide exactly one setting per request'], 422);
        }

        $key = array_key_first($validated);

        // only allow legit columns
        $columns = Schema::getColumnListing('configs');
        if (!in_array($key, $columns, true)) {
            return response()->json(['success' => false, 'message' => "Unknown setting: {$key}"], 422);
        }

        $cfg->{$key} = $validated[$key];
        $cfg->save();

        $themeChanged = in_array($key, self::THEME_FIELDS, true);
        if ($themeChanged) {
            $this->generateThemeCSS($cfg);
        }

        // Version theme CSS by file mtime
        $cssVersion = @filemtime(public_path('css/theme-generated.css')) ?: time();

        return response()->json([
            'success'        => true,
            'key'            => $key,
            'value'          => $cfg->{$key},
            'theme_changed'  => $themeChanged,
            'css_version'    => $cssVersion,
        ]);
    }

    private function generateThemeCSS($config): void
    {
        $cssContent = $this->buildCSSContent($config);
        $filePath   = public_path('css/theme-generated.css');

        if (!file_exists(dirname($filePath))) {
            @mkdir(dirname($filePath), 0755, true);
        }
        @file_put_contents($filePath, $cssContent);
    }

    private function buildCSSContent($config): string
    {
        $ts = now()->format('Y-m-d H:i:s');
        $v  = fn($x,$d)=>($config->{$x} ?? $d);

        return <<<CSS
/* Auto-generated Theme CSS */
/* Generated: {$ts} */
/* DO NOT EDIT MANUALLY */

:root{
  --primary-color: {$v('main_color','#960000')};
  --black-color: {$v('black_color','#121212')};
  --white-color: {$v('white_color','#EFEFEF')};
  --secondary-color: {$v('secondary_color','#FFBF66')};
  --tertiary-color: {$v('tertiary_color','#50D200')};
  --success-color: {$v('success_color','#50D200')};
  --error-color: {$v('error_color','#D80000')};
  --warning-color: {$v('warning_color','#FFBF66')};
  --info-color: {$v('info_color','#6D6D6D')};

  --primary-font: {$v('primary_font','Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif')};
  --secondary-font: {$v('secondary_font','Georgia, "Times New Roman", Times, serif')};
  --body-font-size: {$v('body_font_size','16px')};
  --h1-font-size: {$v('h1_font_size','32px')};
  --h2-font-size: {$v('h2_font_size','24px')};
  --h3-font-size: {$v('h3_font_size','20px')};
  --h4-font-size: {$v('h4_font_size','18px')};
  --h5-font-size: {$v('h5_font_size','16px')};
  --body-line-height: {$v('body_line_height','1.5')};
  --heading_line_height: {$v('heading_line_height','1.2')};
  --font-weight-normal: {$v('font_weight_normal','400')};
  --font-weight-medium: {$v('font_weight_medium','500')};
  --font-weight-bold: {$v('font_weight_bold','600')};

  --border-radius: {$v('border_radius','8px')};
  --sidebar-width: {$v('sidebar_width','280px')};
  --content-max-width: {$v('content_max_width','1200px')};
}
CSS;
    }

    /** POST: quick health tests */
    public function testConfiguration()
    {
        $config = Config::current();

        $tests = [
            'storage_link_exists'  => is_link(public_path('storage')) || file_exists(public_path('storage')),
            'public_disk_writable' => $this->isDiskWritable('public'),
            'logo_present'         => $config && $config->site_logo && file_exists(public_path($config->site_logo)),
            'favicon_present'      => $config && $config->favicon && file_exists(public_path($config->favicon)),
            'css_file_writable'    => is_writable(public_path('css')) || is_writable(public_path()),
        ];

        return response()->json([
            'success' => !in_array(false, $tests, true),
            'tests'   => $tests,
        ]);
    }

    /** POST: reset key theme values */
    public function resetToDefaults()
    {
        if (!Schema::hasTable('configs')) {
            return response()->json(['success'=>false,'message'=>'Config table not ready'], 503);
        }

        $cfg = Config::current() ?? Config::create();
        $cfg->update([
            // Colors
            'main_color'      => '#960000',
            'black_color'     => '#121212',
            'white_color'     => '#EFEFEF',
            'secondary_color' => '#FFBF66',
            'tertiary_color'  => '#50D200',
            'success_color'   => '#50D200',
            'error_color'     => '#D80000',
            'warning_color'   => '#FFBF66',
            'info_color'      => '#6D6D6D',

            // Typography
            'primary_font'    => 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            'secondary_font'  => 'Georgia, "Times New Roman", Times, serif',
            'body_font_size'  => '16px',
            'h1_font_size'    => '32px',
            'h2_font_size'    => '24px',
            'h3_font_size'    => '20px',
            'h4_font_size'    => '18px',
            'h5_font_size'    => '16px',

            // Misc
            'timezone'        => 'UTC',
            'date_format'     => 'Y-m-d',
            'time_format'     => '24',
        ]);

        $this->generateThemeCSS($cfg);

        return response()->json([
            'success'     => true,
            'css_version' => @filemtime(public_path('css/theme-generated.css')) ?: time(),
        ]);
    }

    /** Branding uploads */
    public function updateLogo(Request $request)         
    { return $this->uploadBranding($request, 'logo', 'images', 'site_logo'); }
    public function deleteLogo()
    { return $this->deleteBranding('site_logo', 'images'); }
    public function updateFavicon(Request $request)
    {  return $this->uploadBranding($request, 'favicon', 'favicons', 'favicon'); }
    public function deleteFavicon()                         { return $this->deleteBranding('favicon', 'favicons'); }
    public function updateLoginBackground(Request $request) { return $this->uploadBranding($request, 'login_background', 'backgrounds', 'login_background'); }
    public function deleteLoginBackground()                 { return $this->deleteBranding('login_background', 'backgrounds'); }

    private function uploadBranding(Request $request, string $formKey, string $folder, string $configField)
    {
        $request->validate([
            $formKey => 'required|file|mimes:png,jpg,jpeg,webp,svg,ico|max:2048',
        ]);

        $file = $request->file($formKey);
        $ext  = strtolower($file->getClientOriginalExtension());

        $baseName     = str_replace(['_', '-'], ['', ''], $configField);
        $filename     = "{$baseName}.{$ext}";
        $relativePath = null;

        if ($folder === 'images') {
            @mkdir(public_path('images'), 0755, true);
            $file->move(public_path('images'), $filename);
            $relativePath = "images/{$filename}";
        } else {
            $stored       = $file->storeAs($folder, $filename, 'public');
            $relativePath = "storage/{$stored}";
        }

        $this->deleteSiblingVariants($folder, $baseName, $ext);

        $config = Config::current() ?? Config::create();
        $config->update([$configField => $relativePath]);

        return response()->json([
            'success'     => true,
            'url'         => asset($relativePath) . '?v=' . (@filemtime(public_path($relativePath)) ?: time()),
            'path'        => $relativePath,
            'css_version' => @filemtime(public_path('css/theme-generated.css')) ?: time(),
        ]);
    }

    private function deleteBranding(string $configField, string $folder)
    {
        $config = Config::current() ?? Config::create();

        if ($config->$configField) {
            $fullPath = public_path($config->$configField);
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        $config->update([$configField => null]);

        return response()->json([
            'success'     => true,
            'css_version' => @filemtime(public_path('css/theme-generated.css')) ?: time(),
        ]);
    }

    private function deleteSiblingVariants(string $folder, string $baseName, ?string $keepExt): void
    {
        try {
            if ($folder === 'images') {
                foreach (glob(public_path("images/{$baseName}.*")) ?: [] as $file) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if ($keepExt === null || strcasecmp($ext, $keepExt) !== 0) @unlink($file);
                }
            } else {
                foreach (Storage::disk('public')->files($folder) as $p) {
                    $name = basename($p);
                    if (!str_starts_with($name, $baseName.'.')) continue;
                    $ext = pathinfo($p, PATHINFO_EXTENSION);
                    if ($keepExt !== null && strcasecmp($ext, $keepExt) === 0) continue;
                    @Storage::disk('public')->delete($p);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Unable to delete branding variants: ".$e->getMessage());
        }
    }

    private function isDiskWritable(string $disk): bool
    {
        try {
            $tmp = 'health_'.Str::random(8).txt;
            Storage::disk($disk)->put($tmp, 'ok');
            Storage::disk($disk)->delete($tmp);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** POST /admin/settings/test-email  */
    public function testEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            Mail::raw('Test email from LMS settings', function ($m) use ($request) {
                $m->to($request->email)->subject('SMTP Test');
            });
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
