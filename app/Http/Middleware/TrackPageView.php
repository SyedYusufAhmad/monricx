<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackPageView
{
    private const TRACKED_ROUTES = [
        'home',
        'shop',
        'privacy-policy',
        'refund-policy',
        'faq',
        'contact',
        'about',
        'category',
        'product.show',
        'checkout',
        'order.confirmation',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET')
            || $response->getStatusCode() >= 400
            || ! in_array($request->route()?->getName(), self::TRACKED_ROUTES, true)
            || preg_match('/bot|crawler|spider|preview|slurp/i', (string) $request->userAgent())) {
            return $response;
        }

        try {
            $key = (string) config('app.key');
            $referrerHost = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

            PageView::query()->create([
                'session_hash' => hash_hmac('sha256', $request->session()->getId(), $key),
                'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), $key) : null,
                'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 512),
                'route_name' => $request->route()?->getName(),
                'referrer_host' => is_string($referrerHost) ? mb_substr($referrerHost, 0, 255) : null,
                'device_type' => $this->deviceType((string) $request->userAgent()),
                'visited_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }

    private function deviceType(string $userAgent): string
    {
        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/mobile|iphone|android/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }
}
