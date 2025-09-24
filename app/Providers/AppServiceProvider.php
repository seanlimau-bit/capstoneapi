<?php

namespace App\Providers;

use App\Models\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            [$siteConfig, $siteSettings, $version] = $this->resolveSiteConfigAndSettings();

            $view->with('siteConfig', $siteConfig);
            $view->with('siteSettings', $siteSettings);
            $view->with('assetVersion', $version);
        });
    }

    /**
     * Always fetch fresh values from the writer PDO to avoid replica lag.
     * No framework cache is used here.
     *
     * @return array{0:?Config,1:array,2:int}
     */
    private function resolveSiteConfigAndSettings(): array
    {
        try {
            if (!Schema::hasTable('configs')) {
                return [null, $this->getDefaults(), time()];
            }

            // Fresh read, no Cache::remember
            $siteConfig = Config::query()->useWritePdo()->first();

            $settings = $this->buildSettings($siteConfig);

            // Version for cache busting of CSS, images, icons
            $version = optional($siteConfig?->updated_at)->timestamp ?? time();

            return [$siteConfig, $settings, $version];
        } catch (\Throwable $e) {
            return [null, $this->getDefaults(), time()];
        }
    }

    private function buildSettings(?Config $config): array
    {
        $defaults = $this->getDefaults();
        if (!$config) {
            return $defaults;
        }
        foreach ($defaults as $key => $default) {
            if (isset($config->$key) && $config->$key !== null) {
                $defaults[$key] = $config->$key;
            }
        }
        return $defaults;
    }

    private function getDefaults(): array
    {
        return [
            // Basic
            'site_name'         => 'Admin',
            'site_shortname'    => 'Admin',
            'email'             => 'admin@example.com',
            'site_url'          => config('app.url'),
            'timezone'          => 'UTC',
            'date_format'       => 'Y-m-d',
            'time_format'       => '24',

            // Colors
            'main_color'        => '#960000',
            'black_color'       => '#121212',
            'white_color'       => '#EFEFEF',
            'secondary_color'   => '#FFBF66',
            'tertiary_color'    => '#50D200',
            'success_color'     => '#50D200',
            'error_color'       => '#D80000',
            'warning_color'     => '#FFBF66',
            'info_color'        => '#6D6D6D',

            // Typography
            'primary_font'         => 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            'secondary_font'       => 'Georgia, "Times New Roman", Times, serif',
            'body_font_size'       => '16px',
            'h1_font_size'         => '32px',
            'h2_font_size'         => '24px',
            'h3_font_size'         => '20px',
            'h4_font_size'         => '18px',
            'h5_font_size'         => '16px',
            'body_line_height'     => '1.5',
            'heading_line_height'  => '1.2',
            'font_weight_normal'   => '400',
            'font_weight_medium'   => '500',
            'font_weight_bold'     => '600',

            // Layout
            'border_radius'     => '8.56px',
            'sidebar_width'     => '280px',
            'content_max_width' => '1400px',

            // Files
            'site_logo'         => null,
            'favicon'           => null,
            'login_background'  => null,
        ];
    }
}
