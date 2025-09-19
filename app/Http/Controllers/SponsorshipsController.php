<?php

namespace App\Http\Controllers;

use App\Models\SponsorshipOffer;
use Illuminate\Http\Request;

class SponsorshipsController extends Controller
{
    public function create()
    {
        return view('pages.sponsorship_offer_new');
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'title'         => 'required|string|max:120',
            'cpm_usd'       => 'required|numeric|min:0',
            'min_downloads' => 'nullable|integer|min:0',
            'pre_slots'     => 'nullable|integer|min:0|max:5',
            'mid_slots'     => 'nullable|integer|min:0|max:10',
            'post_slots'    => 'nullable|integer|min:0|max:5',
            'start_at'      => 'nullable|date',
            'end_at'        => 'nullable|date|after_or_equal:start_at',
            'notes'         => 'nullable|string',
        ]);

        // Force safe defaults for any omitted fields
        $data['min_downloads'] = isset($data['min_downloads']) ? (int)$data['min_downloads'] : 0;
        $data['pre_slots']     = isset($data['pre_slots'])     ? (int)$data['pre_slots']     : 0;
        $data['mid_slots']     = isset($data['mid_slots'])     ? (int)$data['mid_slots']     : 0;
        $data['post_slots']    = isset($data['post_slots'])    ? (int)$data['post_slots']    : 0;

        $data['status']        = 'active';

        SponsorshipOffer::create($data);

        return redirect()->route('monetization')->with('ok','Sponsorship offer created.');
    }

}
