<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MyFatoorahWebhookAccessKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        if (!$request->has('secret_access_key') || $request->input('secret_access_key') != config('myfatoorah.webhook_secret_key')) {
            abort(403);
        }
        return $next($request);
    }
}
