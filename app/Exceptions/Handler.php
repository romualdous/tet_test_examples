<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use ReflectionClass;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        /**
         * General ApiException class is used to any exception
         * that is being thrown by the API itself except for
         * application's internal logic.
         * If you want to handle API exceptions here,
         * make sure to extend your exceptions
         * with ApiException rather than Exception.
         */
        $this->renderable(function (ApiException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        });
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Exception|Throwable $e
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Exception|Throwable $e)
    {

        if ($e instanceof ModelNotFoundException) {
            $class = $e->getModel();

            return response()->json([
                'success' => false,
                'message' => "Entry for {$class} not found",
                'data'    => []
            ], 404);
        }

        if ($e instanceof \Laravel\Cashier\Exceptions\IncompletePayment) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => $e->payment
            ], 502);
        }

        return parent::render($request, $e);
    }
}
