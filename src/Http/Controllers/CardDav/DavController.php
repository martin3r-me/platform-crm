<?php

namespace Platform\Crm\Http\Controllers\CardDav;

use Illuminate\Http\Request;
use Platform\Crm\CardDav\DavServer;
use Sabre\HTTP\Request as SabreRequest;
use Sabre\HTTP\Response as SabreResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bridge zwischen Laravel und dem Sabre-CardDAV-Server.
 *
 * Statt sabre direkt in den Output schreiben zu lassen (headers-already-sent),
 * fahren wir `invokeMethod(..., sendResponse: false)` und mappen die Sabre-
 * Response zurück auf eine Laravel-/Symfony-Response. Siehe docs/carddav.md.
 */
class DavController
{
    public function __invoke(Request $request): Response
    {
        $path = trim((string) config('crm.carddav.path', 'crm/dav'), '/');
        $baseUri = '/'.$path.'/';

        $server = DavServer::make($baseUri);

        $server->httpRequest = new SabreRequest(
            $request->getMethod(),
            $request->getRequestUri(),
            $this->headers($request),
            $request->getContent(),
        );
        $server->httpResponse = new SabreResponse();

        // exec() fährt die volle DAV-Pipeline inkl. Exception-Handling (401/403/404
        // statt Fatals); die CapturingSapi unterdrückt den direkten Output.
        $server->exec();

        $sabreResponse = $server->httpResponse;

        return response(
            $sabreResponse->getBodyAsString(),
            $sabreResponse->getStatus(),
            $sabreResponse->getHeaders(),
        );
    }

    /**
     * Laravel-Header (name => string[]) in das von Sabre erwartete Format.
     *
     * @return array<string, string[]>
     */
    private function headers(Request $request): array
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $headers[$name] = $values;
        }

        return $headers;
    }
}
