<?php

namespace Workstation\PhpApi;

use ErrorException;
use Throwable;

class ErrorHandler
{
    public static function handleException(Throwable $exception): void
    {
        // Log the exception
        self::logException($exception);

        // Set default HTTP response code
        $statusCode = 500;

        // Determine appropriate HTTP status code
        if ($exception instanceof \PDOException || $exception instanceof \mysqli_sql_exception) {
            $statusCode = 503; // Service Unavailable for database errors
        } elseif ($exception instanceof \InvalidArgumentException) {
            $statusCode = 400; // Bad Request for invalid arguments
        } elseif ($exception instanceof \RuntimeException) {
            $statusCode = 500; // Internal Server Error for runtime errors
        }

        // Set HTTP response code
        http_response_code($statusCode);

        // Send standardized error response
        echo json_encode([
            'error' => [
                'code' => $statusCode,
                'message' => 'An unexpected error occurred. Please try again later.',
            ]
        ]);
    }

    public static function handleError(
        int $errno,
        string $errstr,
        string $errfile,
        int $errline
    ): bool {
        // Convert PHP errors to ErrorException
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    private static function logException(Throwable $exception): void
    {
        // Log the exception to a file or external logging service
        // Example: Log to a file
        $logMessage = sprintf(
            "[%s] %s in %s:%s\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        error_log($logMessage, 3, 'error.log'); // Log to error.log file
    }
}
