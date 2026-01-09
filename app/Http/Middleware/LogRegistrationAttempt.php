<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRegistrationAttempt
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);

            $status = $response->getStatusCode();
            $content = $response->getContent();
        } catch (\Throwable $e) {
            $status = 500;
            $content = $e->getMessage();

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $status = $e->status;
                $content = json_encode($e->errors());
            }

            $this->logAttempt($request, $status, $content);

            throw $e;
        }

        $this->logAttempt($request, $status, $content);

        return $response;
    }

    protected function logAttempt(Request $request, int $status, string $content): void
    {
        Log::info(sprintf(
            'Registration attempt: %s (%s) - Status: %d - Response: %s',
            $request->input('name', 'N/A'),
            $request->input('email', 'N/A'),
            $status,
            $content
        ));
    }
}
