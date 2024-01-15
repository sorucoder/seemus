<?php
declare(strict_types=1);

class Role {
    private string $id;
    private string $name;

    private function __construct(string $id, string $name) {
        // TODO: stub
    }

    public static function all(): array {
        // TODO: stub
    }

    public static function fromId(string $id): ?self {
        // TODO: stub
    }

    public function getId(): string {
        // TODO: stub
    }

    public function getName(): string {
        // TODO: stub
    }
}
?>