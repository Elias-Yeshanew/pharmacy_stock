<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compresses API JSON responses with gzip when the client supports it.
 * This is crucial for cross-WAN deployments (Render ↔ Vercel) where
 * large JSON payloads can be reduced by 70–90%.
 */
class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        // Only compress if the client supports gzip and the response is large enough
        if (
            str_contains($request->header('Accept-Encoding', ''), 'gzip') &&
            strlen($response->getContent()) > 1024 // only compress >1KB
        ) {
            $compressed = gzencode($response->getContent(), 6); // level 6: good ratio/speed

            if ($compressed !== false) {
                $response->setContent($compressed);
                $response->headers->set('Content-Encoding', 'gzip');
                $response->headers->set('Content-Length', strlen($compressed));
                $response->headers->remove('Transfer-Encoding');
            }
        }

        return $response;
    }
}
