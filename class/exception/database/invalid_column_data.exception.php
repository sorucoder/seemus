<?php
declare(strict_types=1);

final class InvalidColumnDataException extends LogicException {
    public function __construct(?string $message = 'invalid column data', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>