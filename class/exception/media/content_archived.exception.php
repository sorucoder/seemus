<?php
declare(strict_types=1);

final class ContentArchivedException extends RuntimeException {
    public function __construct(?string $message = 'content archived', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>