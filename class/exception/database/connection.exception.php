<?php
declare(strict_types=1);

final class ConnectionException extends RuntimeException {
    public function __construct(?string $message = 'cannot connect to database', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>