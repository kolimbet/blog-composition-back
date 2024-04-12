<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckIsAdmin
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
   * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
   */
  public function handle(Request $request, Closure $next)
  {
    // Log::info("Middleware CheckIsAdmin: ", [$request->user(), $request->user()->isAdmin()]);
    if(!$request->user() || !$request->user()->isAdmin())
    {
      Log::info("Access denied to '{$request->path()}' for {$request->user()->name} #{$request->user()->id}");
      throw new AccessDeniedHttpException('Access denied');
    }
    else return $next($request);
  }
}