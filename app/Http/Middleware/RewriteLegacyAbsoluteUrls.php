<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Rewrite leftover absolute URLs from old hosts to the current app base URL.
 * Important: must NOT rewrite to bare "/" when the app lives in a subdirectory
 * (e.g. /collegenew/college), or CSS/JS asset() links break.
 */
class RewriteLegacyAbsoluteUrls
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $contentType = (string) $response->headers->get('Content-Type', '');
        if ($contentType !== '' && stripos($contentType, 'text/html') === false) {
            return $response;
        }

        $content = $response->getContent();
        if (!is_string($content) || $content === '') {
            return $response;
        }

        // Current install root, e.g. http://localhost/collegenew/college or https://media.psr.edu.in
        $appRoot = rtrim(url('/'), '/');
        $appRootSlash = $appRoot . '/';

        // Protect current media host from being rewritten as bare psr.edu.in
        $token = '___MEDIA_PSR_HOST___';
        $protected = str_ireplace(
            ['https://media.psr.edu.in', 'http://media.psr.edu.in'],
            [$token, $token],
            $content
        );

        $rewritten = preg_replace(
            [
                '#https?://localhost/collegenew/college/#i',
                '#https?://localhost/collegenew/college(?=[^a-z0-9_/]|$)#i',
                '#https?://127\.0\.0\.1/collegenew/college/#i',
                '#https?://127\.0\.0\.1/collegenew/college(?=[^a-z0-9_/]|$)#i',
                '#https?://www\.psr\.edu\.in/#i',
                '#https?://www\.psr\.edu\.in(?=[^a-z0-9_/]|$)#i',
                '#https?://psr\.edu\.in/#i',
                '#https?://psr\.edu\.in(?=[^a-z0-9_/]|$)#i',
                '#https?://www\.anywheranytime\.co\.in/#i',
                '#https?://www\.anywheranytime\.co\.in(?=[^a-z0-9_/]|$)#i',
                '#https?://anywheranytime\.co\.in/#i',
                '#https?://anywheranytime\.co\.in(?=[^a-z0-9_/]|$)#i',
            ],
            [
                $appRootSlash,
                $appRoot,
                $appRootSlash,
                $appRoot,
                $appRootSlash,
                $appRoot,
                $appRootSlash,
                $appRoot,
                $appRootSlash,
                $appRoot,
                $appRootSlash,
                $appRoot,
            ],
            $protected
        );

        if (!is_string($rewritten)) {
            return $response;
        }

        $rewritten = str_replace($token, 'https://media.psr.edu.in', $rewritten);

        if ($rewritten !== $content) {
            $response->setContent($rewritten);
        }

        return $response;
    }
}
