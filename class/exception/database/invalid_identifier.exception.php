<?php
declare(strict_types=1);

final class InvalidIdentifierException extends LogicException {
    public function __construct(?string $message = 'identifier is invalid', ?int $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
?>