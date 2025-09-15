<?php




namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class FeedAliasController extends Controller
{
    public function __invoke(Request $request)
    {
        $s = Setting::singleton();

        // Optional DB-configured custom alias
        $customHost = trim((string)($s->feed_custom_domain ?? ''), '.');
        $customPath = trim((string)($s->feed_custom_path ?? ''), '/'); // e.g. "rss" or "new-feed.xml"

        $hostOk = $customHost === '' || strcasecmp($request->getHost(), $customHost) === 0;
        $pathOk = $customPath !== '' && trim($request->path(), '/') === $customPath;

        if ($hostOk && $pathOk) {
            $canonical = (string) ($s->feed_url ?? url('/feed.xml'));
            return redirect()->to($canonical, 301);
        }

        abort(404);
    }
}

