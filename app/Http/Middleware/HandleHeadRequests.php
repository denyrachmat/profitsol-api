<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleHeadRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // If this is a HEAD request, respond immediately with headers
        if ($request->isMethod('head')) {
            // For file download endpoints, return appropriate headers
            if (strpos($request->path(), 'getDataMRPWeek') !== false) {
                return response('', 200)
                    ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                    ->header('Content-Disposition', 'attachment; filename="mrp_scheme_weekly.xlsx"');
            }
            
            // For other HEAD requests, still process normally but mark them
            $request->attributes->set('_is_head_request', true);
            $request->setMethod('GET');
        }

        $response = $next($request);

        return $response;
    }
}
