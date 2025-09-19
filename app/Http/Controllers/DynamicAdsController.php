<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DynamicAdsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * GET /monetization/dynamic
     * Load dynamic ad insertion settings from site_settings.key = 'dynamic_ads'
     */
    public function show(Request $request)
    {
        $row = DB::table('dynamic_ad_settings')->where('key', 'dynamic_ads')->first();

        $defaults = [
            'enabled'     => false,
            'provider'    => null,          // e.g. 'adstitch', 'megaphone', 'custom'
            'fill_rate'   => 70,            // %
            'default_cpm' => 18.0,          // USD
            'slots'       => [
                'pre'  => ['count' => 1, 'max' => 2],
                'mid'  => ['count' => 2, 'max' => 3],
                'post' => ['count' => 1, 'max' => 2],
            ],
            'targeting'   => [
                'countries' => [],          // ['US','AU',...]
                'exclude_episodes' => [],   // [id,id,...]
            ],
            'webhook_url' => null,          // for delivery confirmations (optional)
        ];

        $settings = $row ? array_merge($defaults, (array) json_decode($row->value, true)) : $defaults;

        // Render a view if you have it; otherwise return JSON for now
        if (view()->exists('monetization.dynamic')) {
            return view('monetization.dynamic', compact('settings'));
        }

        return response()->json(['settings' => $settings]);
    }

    /**
     * POST /monetization/dynamic
     * Validate and save settings into site_settings.key = 'dynamic_ads'
     */
    public function save(Request $request)
    {
        $data = $request->validate([
            'enabled'           => ['nullable', 'boolean'],
            'provider'          => ['nullable', 'string', 'max:100'],
            'fill_rate'         => ['required', 'numeric', 'min:0', 'max:100'],
            'default_cpm'       => ['required', 'numeric', 'min:0'],
            'slots.pre.count'   => ['nullable', 'integer', 'min:0', 'max:10'],
            'slots.pre.max'     => ['nullable', 'integer', 'min:0', 'max:10'],
            'slots.mid.count'   => ['nullable', 'integer', 'min:0', 'max:10'],
            'slots.mid.max'     => ['nullable', 'integer', 'min:0', 'max:10'],
            'slots.post.count'  => ['nullable', 'integer', 'min:0', 'max:10'],
            'slots.post.max'    => ['nullable', 'integer', 'min:0', 'max:10'],
            'targeting.countries'        => ['nullable', 'array'],
            'targeting.countries.*'      => ['string', 'size:2'],
            'targeting.exclude_episodes' => ['nullable', 'array'],
            'targeting.exclude_episodes.*' => ['integer'],
            'webhook_url'       => ['nullable', 'url'],
        ]);

        // normalize checkbox
        $data['enabled'] = (bool) ($data['enabled'] ?? false);

        // upsert into site_settings
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES);

        DB::table('site_settings')->updateOrInsert(
            ['key' => 'dynamic_ads'],
            ['value' => $payload, 'updated_at' => now(), 'created_at' => now()]
        );

        // If the request expects JSON (AJAX), return JSON; else redirect back
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'settings' => $data]);
        }

        return redirect()
            ->route('monetization.dynamic.show')
            ->with('status', 'Dynamic Ad settings saved.');
    }
}
