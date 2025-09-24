<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
	protected $fillable = [
		'site_name', 'site_shortname', 'main_color', 'email',
		'number_of_teaching_days', 'site_url', 'site_logo',
		'no_rights_to_pass', 'no_wrongs_to_fail', 'self_paced',
		'timezone', 'date_format', 'time_format', 'favicon',
		'login_background', 'maintenance_mode', 'maintenance_message', 'updated_at'
	];

	protected $casts = [
		'self_paced' => 'boolean',
		'maintenance_mode' => 'boolean',
		'no_rights_to_pass' => 'integer',
		'no_wrongs_to_fail' => 'integer',
		'number_of_teaching_days' => 'integer',
	];

	    // Ensure Eloquent manages timestamps so updated_at is maintained.
	public $timestamps = true;

	protected static function booted()
	{
		static::saved(function () {
			Cache::forget('site_config');
		});

		static::deleted(function () {
			Cache::forget('site_config');
		});
	}public function getLogoUrlAttribute()
	{
		if (!empty($this->site_logo)) {
			$fullPath = public_path($this->site_logo);
			if (file_exists($fullPath)) {
				return asset($this->site_logo);
			}
		}
		return null;
	}

	public function getFaviconUrlAttribute()
	{
		if (!empty($this->favicon)) {
			return asset($this->favicon);
		}
    // Fallback to default favicon
		return asset('images/favicon.ico');
	}
    // Singleton pattern - only one config record
	public static function current()
	{
		return cache()->remember('app.config', 3600, function () {
			return static::first() ?? static::create([
				'site_name' => 'All Gifted Math',
				'site_shortname' => 'AGM',
				'main_color' => '#960000',
				'email' => 'admin@allgifted.com',
				'site_url' => config('app.url'),
				'timezone' => 'UTC',
				'date_format' => 'd/m/Y',
				'time_format' => '12',
				'number_of_teaching_days' => 180,
				'no_rights_to_pass' => 2,
				'no_wrongs_to_fail' => 2,
				'self_paced' => true,
				'maintenance_mode' => false,
			]);
		});
	}

    // Update and clear cache
	public function updateSettings(array $data)
	{
		$this->update($data);
		cache()->forget('app.config');
		return $this;
	}

    // Helper methods
	public function hasLogo()
	{
		return !empty($this->site_logo) && file_exists(public_path($this->site_logo));
	}

	public function getLoginBackgroundUrlAttribute()
	{
		return !empty($this->login_background) ? asset($this->login_background) : null;
	}
}