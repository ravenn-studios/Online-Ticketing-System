<?php

namespace App\Http\Middleware;

use Closure;

class SessionTimeOut
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // return $next($request);

        if (! session()->has('lastActivityTime')) {
            session(['lastActivityTime' => now()]);
        }

        if (now()->diffInMinutes(session('lastActivityTime')) >= (1) ) {  // also you can this value in your config file and use here

            if (auth()->check() && auth()->id() > 1) {
                $user = auth()->user();
                auth()->logout();
     
                session()->forget('lastActivityTime');
     
                return redirect(route('ers.login'));
            }
     
        }

        session(['lastActivityTime' => now()]);

        return $next($request);

    }
}
