<?php

namespace App\Http\Controllers\App\Outlet;

use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchOutletController extends Controller
{
    /**
     * Switch the active outlet context.
     */
    public function switch(Request $request, string $id): RedirectResponse
    {
        SelectedOutlet::make($request->user())->change($id);

        return $this->cleanRedirectBack($request);
    }

    /**
     * Reset the active outlet context to all outlets.
     */
    public function all(Request $request): RedirectResponse
    {
        SelectedOutlet::make($request->user())->all();

        return $this->cleanRedirectBack($request);
    }

    /**
     * Redirect back while clearing stale outlet and pagination query parameters.
     */
    private function cleanRedirectBack(Request $request): RedirectResponse
    {
        $referer = $request->header('referer');
        if (! $referer) {
            return redirect()->back();
        }

        $parsedUrl = parse_url($referer);
        if (! isset($parsedUrl['query'])) {
            return redirect()->back();
        }

        parse_str($parsedUrl['query'], $queryParams);
        unset($queryParams['outlet'], $queryParams['outlet_id'], $queryParams['page']);

        $cleanUrl = ($parsedUrl['scheme'] ?? 'http').'://'.($parsedUrl['host'] ?? '');
        if (isset($parsedUrl['port'])) {
            $cleanUrl .= ':'.$parsedUrl['port'];
        }
        $cleanUrl .= ($parsedUrl['path'] ?? '');

        if (! empty($queryParams)) {
            $cleanUrl .= '?'.http_build_query($queryParams);
        }

        return redirect()->to($cleanUrl);
    }
}
