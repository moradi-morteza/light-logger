<?php

namespace LightLogger\Middleware;

use LightLogger\Http\Request;
use LightLogger\Http\Response;

interface Middleware
{
    /**
     * Handle the request
     *
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response|null
     */
    public function handle(Request $request, Response $response, callable $next): ?Response;
}
