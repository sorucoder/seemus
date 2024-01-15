<?php
declare(strict_types=1);

require_once './class/user.class.php';

class Content {
    private string $id;
    private string $title;
    private string $description;
    private string $body;
    private User $author;
    private DateTime $createdDate;
    private DateTime $updatedDate;
    private bool $archived;

    private function __construct(string $id, string $title, string $description, string $body, User $author, DateTime $createdDate, DateTime $updatedDate, bool $archived) {
        // TODO: stub
    }

    public static function create(string $id, string $title, string $description, string $body): ?self {
        // TODO: stub
    }

    public static function fromId(string $id): ?self {
        // TODO: stub
    }

    public function getId(): string {
        // TODO: stub
    }

    public function getTitle(): string {
        // TODO: stub
    }

    public function setTitle(string $title) {
        // TODO: stub
    }

    public function getDescription(): string {
        // TODO: stub
    }

    public function setDescription(string $description) {
        // TODO: stub
    }

    public function getBody(): string {
        // TODO: stub
    }

    public function setBody(string $body) {
        // TODO: stub
    }

    public function getAuthor(): User {
        // TODO: stub
    }

    public function getCreatedDate(): DateTime {
        // TODO: stub
    }

    public function getUpdatedDate(): DateTime {
        // TODO: stub
    }

    public function isArchived(): bool {
        // TODO: stub
    }

    public function archive(): void {
        // TODO: stub
    }

    public function delete(): void {
        // TODO: stub
    }
}
?>