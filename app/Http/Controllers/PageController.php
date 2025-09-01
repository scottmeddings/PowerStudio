<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function episodes()     { return view('pages.episodes'); }
    public function distribution() { return view('pages.distribution'); }
    public function statistics()   { return view('pages.statistics'); }
    public function monetization() { return view('pages.monetization'); }
    public function settings()     { return view('pages.settings'); }
}
