<?php



namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\RevenueDaily;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\MonetizationController;


class MonetizationController extends Controller
{
    public function index(Request $request)
    {
        $today        = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();

        $rev = [
            'mtd'    => round(RevenueDaily::whereBetween('day', [$startOfMonth, $today])->sum('revenue_usd'), 2),
            'last30' => round(RevenueDaily::where('day','>=',$today->copy()->subDays(29))->sum('revenue_usd'), 2),
            'all'    => round(RevenueDaily::sum('revenue_usd'), 2),
            'ecpm'   => round(RevenueDaily::where('day','>=',$today->copy()->subDays(29))->avg('ecpm') ?? 0, 2),
        ];

        // optional inventory (join to episodes if you have that table)
        $inventory = null; // pass real data later if you like

        $payouts = Payout::orderByDesc('payout_date')->limit(20)->get()
            ->map(fn($p) => [
                'date'   => optional($p->payout_date)->toDateString(),
                'amount' => (float)$p->amount_usd,
                'status' => (string)$p->status,
            ])->all();

        $stripe_connected = (bool) env('STRIPE_CONNECT_ACCOUNT'); // or lookup your own link status

        return view('pages.monetization', compact('rev','payouts','inventory','stripe_connected'));
    }

    // charts: last N days
    public function timeseries(Request $request)
    {
        $days = (int)($request->get('days', 180));
        $from = now()->startOfDay()->subDays(max(1, $days - 1));

        $rows = RevenueDaily::where('day','>=',$from)
            ->orderBy('day')
            ->get(['day','downloads','revenue_usd','ecpm']);

        return response()->json([
            'labels'    => $rows->pluck('day')->map->toDateString(),
            'revenue'   => $rows->pluck('revenue_usd'),
            'downloads' => $rows->pluck('downloads'),
            'ecpm'      => $rows->pluck('ecpm'),
        ]);
    }

    // calculator (can persist)
    public function estimate(Request $request)
    {
        $data = $request->validate([
            'downloads' => 'required|integer|min:0',
            'fill'      => 'required|numeric|min:0|max:100',
            'cpm'       => 'required|numeric|min:0',
            'persist'   => 'sometimes|boolean',
            'day'       => 'sometimes|date',
        ]);

        $impressions = $data['downloads'] * ($data['fill'] / 100);
        $revenue     = ($impressions / 1000) * $data['cpm'];

        if (!empty($data['persist'])) {
            $day = isset($data['day']) ? Carbon::parse($data['day'])->toDateString() : now()->toDateString();
            RevenueDaily::updateOrCreate(
                ['day' => $day],
                [
                    'downloads'   => $data['downloads'],
                    'impressions' => $impressions,
                    'ecpm'        => $data['cpm'],
                    'revenue_usd' => $revenue,
                ]
            );
        }

        return response()->json([
            'impressions' => round($impressions, 2),
            'revenue'     => round($revenue, 2),
            'ecpm'        => round($data['cpm'], 2),
        ]);
    }

    // Buttons (no-op stubs unless you added the Stripe job already)
    public function connect(Request $request)
    {
        // For now just flash a message; wire real Connect later
        return back()->with('ok', 'Stripe connected (stub). Configure STRIPE_CONNECT_ACCOUNT in .env.');
    }

    public function refreshStripe(Request $request)
    {
        // dispatch(new \App\Jobs\SyncStripeRevenueJob()); // enable after you add the job
        return back()->with('ok', 'Stripe sync started (stub).');
    }
}
