<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Contracts\Routing\Middleware;
use Illuminate\Http\Response;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    /*public function handle($request, Closure $next)
    {
        return $next($request)
            ->header('Access-Control-Allow-Origin','*')
            ->header('Access-Control-Allow-Methods','GET,POST,PUT,PATCH,DELETE,OPTIONS')
            ->header('Access-Control-Allow-Headers','Content-Type,Authorization,X-Requested-With,X-CSRF-Token');
    }*/

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin' , '*');
        $response->headers->set('Access-Control-Allow-Methods','GET,POST,PUT,PATCH,DELETE,OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers','Content-Type,Authorization,X-Requested-With,X-CSRF-Token');

        return $response;
    }

}
