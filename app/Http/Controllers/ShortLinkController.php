<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Illuminate\Http\RedirectResponse;

class ShortLinkController extends Controller
{
    public function __invoke(string $code): RedirectResponse
    {
        $link = ShortLink::where('code', $code)->first();

        abort_if(! $link || $link->isExpired(), 404);

        return redirect()->away($link->target_url);
    }
}
