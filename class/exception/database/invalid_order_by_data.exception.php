<?php
declare(strict_types=1);

final class InvalidOrderByDataException extends LogicException {
    public function __construct(?string $message = 'invalid order by data', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>