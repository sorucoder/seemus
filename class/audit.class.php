<?php
declare(strict_types=1);

require_once './class/user.class.php';
require_once './class/content.class.php';
require_once './class/file.class.php';

class Audit {
    private int $id;
    private string $description;
    private User $actor;
    private Content|File $media;
    private DateTime $date;
    private string $action;

    private function __construct(int $id, string $description, User $actor, Content|File $media, DateTime $date, string $action) {
        // TODO: stub
    }

    public static function create(string $description, User $actor, Content|File $media, string $action): ?self {
        // TODO: stub
    }

    public static function fromId(int $id): ?self {
        // TODO: stub
    }

    public function getId(): int {
        // TODO: stub
    }

    public function getDescription(): string {
        // TODO: stub
    }

    public function getActor(): User {
        // TODO: stub
    }

    public function getMedia(): Content|File {
        // TODO: stub
    }

    public function getDate(): DateTime {
        // TODO: stub
    }

    public function getAction(): string {
        // TODO: stub
    }
}
?>