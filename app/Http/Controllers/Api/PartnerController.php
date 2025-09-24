<?php

namespace App\Http\Controllers\Api;

use App\Models\Partner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PartnerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum'); // or your admin auth
    }

    /**
     * Get all partners
     */
    public function index()
    {
        $partners = Partner::with(['users' => function($query) {
            $query->select('id', 'partner_id', 'status', 'created_at');
        }])->get();

        return response()->json($partners);
    }

    /**
     * Create new partner
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:partners,code',
            'name' => 'required|string',
            'phone_prefixes' => 'required|array',
            'phone_prefixes.*' => 'string',
            'access_type' => 'required|in:premium,freemium,trial,basic',
            'billing_method' => 'required|string',
            'default_lives' => 'nullable|integer',
            'trial_duration_days' => 'nullable|integer',
            'features' => 'required|array',
            'verification_required' => 'boolean',
            'auto_activate' => 'boolean',
            'api_key' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'status_sync_url' => 'nullable|url',
            'config' => 'nullable|array'
        ]);

        $partner = Partner::create($request->all());

        return response()->json([
            'message' => 'Partner created successfully',
            'partner' => $partner
        ], 201);
    }

    /**
     * Update partner configuration
     */
    public function update(Request $request, Partner $partner)
    {
        $request->validate([
            'name' => 'sometimes|string',
            'phone_prefixes' => 'sometimes|array',
            'phone_prefixes.*' => 'string',
            'access_type' => 'sometimes|in:premium,freemium,trial,basic',
            'billing_method' => 'sometimes|string',
            'default_lives' => 'nullable|integer',
            'trial_duration_days' => 'nullable|integer',
            'features' => 'sometimes|array',
            'verification_required' => 'sometimes|boolean',
            'auto_activate' => 'sometimes|boolean',
            'api_key' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'status_sync_url' => 'nullable|url',
            'is_active' => 'sometimes|boolean',
            'config' => 'nullable|array'
        ]);

        $partner->update($request->all());

        return response()->json([
            'message' => 'Partner updated successfully',
            'partner' => $partner->fresh()
        ]);
    }

    /**
     * Get partner by code
     */
    public function show($code)
    {
        $partner = Partner::where('code', $code)
                         ->with('users')
                         ->firstOrFail();

        return response()->json($partner);
    }

    /**
     * Delete/deactivate partner
     */
    public function destroy(Partner $partner)
    {
        // Don't actually delete, just deactivate
        $partner->update(['is_active' => false]);

        return response()->json([
            'message' => 'Partner deactivated successfully'
        ]);
    }
}