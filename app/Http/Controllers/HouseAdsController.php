<?php

namespace App\Http\Controllers;

use App\Models\HouseCampaign;
use App\Models\HousePromo;
use Illuminate\Http\Request;

class HouseAdsController extends Controller
{
    public function create() { return view('pages.house_campaign_new'); }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'     => 'required|string|max:120',
            'priority' => 'nullable|integer|min:1|max:10',
            'start_at' => 'nullable|date',
            'end_at'   => 'nullable|date|after_or_equal:start_at',
        ]);

        $campaign = HouseCampaign::create([
            'name' => $data['name'],
            'priority' => $data['priority'] ?? 5,
            'start_at' => $data['start_at'] ?? null,
            'end_at'   => $data['end_at'] ?? null,
            'status'   => 'active',
        ]);

        // Optional: create a blank promo for convenience
        HousePromo::create(['campaign_id'=>$campaign->id, 'label'=>'Default mid-roll', 'slot'=>'mid']);

        return redirect()->route('monetization')->with('ok','House campaign created.');
    }

    public function import(Request $r)
    {
        $r->validate(['json' => 'required|file|mimes:json,txt']);
        $payload = json_decode(file_get_contents($r->file('json')->getRealPath()), true) ?? [];
        $camp = HouseCampaign::create([
            'name' => $payload['name'] ?? 'Imported campaign',
            'status' => 'active',
            'priority' => $payload['priority'] ?? 5,
        ]);
        foreach (($payload['promos'] ?? []) as $p) {
            HousePromo::create([
                'campaign_id'=>$camp->id,
                'label'=>$p['label'] ?? 'Promo',
                'slot'=>$p['slot'] ?? 'mid',
                'audio_url'=>$p['audio_url'] ?? null,
                'cta_url'=>$p['cta_url'] ?? null,
                'episodes'=>$p['episodes'] ?? [],
            ]);
        }
        return redirect()->route('monetization')->with('ok','Promo set imported.');
    }
}
