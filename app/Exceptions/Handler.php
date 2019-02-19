<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Response;

class Handler extends ExceptionHandler
{
    /**
     * Handler constructor.
     */
    public function __construct()
    {
        $this->dontReport = [
            AuthorizationException::class,
            HttpException::class,
            ModelNotFoundException::class,
            ValidationException::class
        ];
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
    * Render an exception into an HTTP response.
    *
    * @param  \Illuminate\Http\Request $request
    * @param  \Exception $e
    * @return \Illuminate\Http\Response
    */
    public function render($request, Exception $e) 
    {
        if (env('APP_DEBUG')) {
            return parent::render($request, $e);
        }
        
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($e instanceof ValidationException && $e->getResponse()) {
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
            $e = new ValidationException('HTTP_UNPROCESSABLE_ENTITY', $status);

        } else if ($e instanceof HttpResponseException) {
            
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        
        } else if ($e instanceof MethodNotAllowedHttpException) {
           
            $status = Response::HTTP_METHOD_NOT_ALLOWED;
            $e = new MethodNotAllowedHttpException([], 'HTTP_METHOD_NOT_ALLOWED', $e);
        
        } else if ($e instanceof NotFoundHttpException) {
            
            $status = Response::HTTP_NOT_FOUND;
            $e = new NotFoundHttpException('HTTP_NOT_FOUND', $e);
        
        } else if ($e instanceof \Dotenv\Exception\ValidationException && $e->getResponse()) {
            
            $status = Response::HTTP_BAD_REQUEST;
            $e = new \Dotenv\Exception\ValidationException('HTTP_BAD_REQUEST', $status, $e);
        
        } else if ($e) {
            $e = new HttpException($status, 'HTTP_INTERNAL_SERVER_ERROR');

        }

        return response()->json([
          'success' => false,
          'status' => $status,
          'response_code' => $e->getMessage(),
          'message' => Response::$statusTexts[$status],
          'errors' => [],
        ], $status);

    }
}
