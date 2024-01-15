<?php
declare(strict_types=1);

require_once './class/user.class.php';

class File {
    private string $id;
    private string $title;
    private string $description;
    private string $mimeType;
    private string $bytes;
    private User $uploader;
    private DateTime $createdDate;
    private DateTime $updatedDate;
    private bool $archived;
    private function __construct(string $id, string $title, string $description, string $mimeType, string $bytes, User $uploader, DateTime $createdDate, DateTime $updatedDate, bool $archived) {
        // TODO: stub
    }

    public static function create(string $title, string $description, string $mimeType, string $bytes): ?self {
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

    public function getMimeType(): string {
        // TODO: stub
    }

    public function setMimeType(string $mimeType) {
        // TODO: stub
    }

    public function getBytes(): string {
        // TODO: stub
    }

    public function setBytes(string $bytes) {
        // TODO: stub
    }

    public function getUploader(): User {
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