<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language', 'en');

        $locale = substr($locale, 0, 2);

        $supportedLocales = ['en', 'ro', 'hu'];

        if(in_array($locale, $supportedLocales)){
            App::setLocale($locale);
        }

        return $next($request);
    }
}
