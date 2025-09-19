<?php

namespace App\Http\Controllers;

use App\Models\DynamicAdSetting;
use Illuminate\Http\Request;

class AdMarketplaceController extends Controller
{
    public function show()
    {
        $cfg = DynamicAdSetting::first() ?? new DynamicAdSetting();
        return view('pages.dynamic_config', compact('cfg'));
    }

    public function save(Request $r)
    {
        $data = $r->validate([
            'status'       => 'required|in:disabled,selling,paused',
            'default_fill' => 'required|integer|min:0|max:100',
            'pre_total'    => 'required|integer|min:0|max:5',
            'mid_total'    => 'required|integer|min:0|max:10',
            'post_total'   => 'required|integer|min:0|max:5',
            'targets'      => 'nullable|array',
        ]);

        DynamicAdSetting::updateOrCreate(['id'=>optional(DynamicAdSetting::first())->id], $data);
        return redirect()->route('monetization.dynamic.show')->with('ok','Dynamic ad settings saved.');
    }
}
