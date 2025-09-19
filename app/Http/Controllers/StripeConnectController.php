<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StripeConnectController extends Controller
{
    public function connect(Request $request)
    {
        // move/connect your existing MonetizationController::connect logic here
        // return redirect()->route('monetization.index')->with('status', 'Stripe connected');
        abort(501, 'Not implemented'); // placeholder
    }

    public function refresh(Request $request)
    {
        // move/connect your existing MonetizationController::refreshStripe logic here
        // return back()->with('status', 'Stripe data synced');
        abort(501, 'Not implemented'); // placeholder
    }
}
