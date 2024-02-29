<?php
declare(strict_types=1);

require_once $_SERVER['ROOT_PATH'] . '/class/database.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/user.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/permissions.class.php';

require_once $_SERVER['ROOT_PATH'] . '/class/exception/user/user_not_logged_in.exception.php';
require_once $_SERVER['ROOT_PATH'] . '/class/exception/media/invalid_content_data.exception.php';
require_once $_SERVER['ROOT_PATH'] . '/class/exception/media/content_archived.exception.php';
require_once $_SERVER['ROOT_PATH'] . '/class/exception/media/content_not_archived.exception.php';

final class Content {
    private int $id;
    private string $uuid;
    private string $title;
    private string $description;
    private string $body;
    private User $author;
    private DateTime $createdDateTime;
    private ?DateTime $lastUpdatedDateTime;
    private bool $archived;

    private function __construct(int $id, string $uuid, string $title, string $description, string $body, User $author, DateTime $createdDateTime, ?DateTime $lastUpdatedDateTime, bool $archived) {
        $this->id = $id;
        $this->uuid = $uuid;
        $this->title = $title;
        $this->description = $description;
        $this->body = $body;
        $this->author = $author;
        $this->createdDateTime = $createdDateTime;
        $this->lastUpdatedDateTime = $lastUpdatedDateTime;
        $this->archived = $archived;
    }

    private static function validateTitle(string $title): ?string {
        $title = preg_replace(
            [
                '/^[\r\n\t\p{Z}]+/u',
                '/[\r\n\t\p{Z}]+$/u'
            ],
            '',
            $title
        );
        return $title;
    }

    private static function validateDescription(string $description): ?string {
        $description = preg_replace(
            [
                '/^[\r\n\t\p{Z}]+/u',
                '/[\r\n\t\p{Z}]+$/u'
            ],
            '',
            $description
        );
        return $description;
    }

    private static function validateBody(string $body): ?string {
        // TODO: Find a REST API (if possible) for validation and sanitization of HTML and use it.
        return $body;
    }

    public static function create(string $title, string $description, string $body): self {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        $invalidFields = [];
        $title = self::validateTitle($title);
        if (!$title) {
            $invalidFields []= 'title';
        }
        $description = self::validateDescription($description);
        if (!$description) {
            $invalidFields []= 'description';
        }
        $body = self::validateBody($body);
        if (!$body) {
            $invalidFields []= 'body';
        }
        if (!empty($invalidFields)) {
            throw new InvalidContentDataException($invalidFields);
        }

        $currentUserID = $currentUser->getID();

        $database = Database::connect();

        $insertedContentRow = NULL;
        try {
            $insertedContentRow = $database->insertRow(
                'CONTENT',
                [
                    'CONTENT_TITLE' => ':title',
                    'CONTENT_DESCRIPTION' => ':description',
                    'CONTENT_BODY' => ':body',
                    'USER_ID' => ':authorID'
                ],
                [
                    ':title' => $title,
                    ':description' => $description,
                    ':body' => $body,
                    ':authorID' => $currentUserID
                ],
                [
                    'CONTENT_ID' => 'id',
                    'CONTENT_UUID' => 'uuid',
                    'CONTENT_CREATED_DATETIME' => 'createdDateTimeValue',
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        $id = $insertedContentRow['id'];
        $uuid = $insertedContentRow['uuid'];
        $createdDateTime = new DateTime($insertedContentRow['createdDateTimeValue']);

        $content = new self(
            $id,
            $uuid,
            $title,
            $description,
            $body,
            $currentUser,
            $createdDateTime,
            NULL,
            false
        );

        // TODO: Specify action
        Audit::log(
            "Created Content \"$title\" ($uuid)",
            $content,
            ''
        );

        if (!$currentUser->isAdministrator()) {
            Permissions::establish(
                $currentUser,
                $content,
                true,
                true,
                true,
                true
            );
        }

        return $content;
    }

    public static function fromID(int $id): ?self {
        $database = Database::connect();
        $selectedContentRow = NULL;
        try {
            $selectedContentRow = $database->selectRow(
                'CONTENT',
                [
                    'CONTENT_UUID' => 'uuid',
                    'CONTENT_TITLE' => 'title',
                    'CONTENT_DESCRIPTION' => 'description',
                    'CONTENT_BODY' => 'body',
                    'USER_ID' => 'authorID',
                    'CONTENT_CREATED_DATETIME' => 'createdDateTimeValue',
                    'CONTENT_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue',
                    'CONTENT_ARCHIVED' => 'archivedValue'
                ],
                '`USER_ID` = :id',
                [
                    ':id' => $id
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        if (!$selectedContentRow) {
            return NULL;
        }
        $uuid = $selectedContentRow['uuid'];
        $title = $selectedContentRow['title'];
        $description = $selectedContentRow['description'];
        $body = $selectedContentRow['body'];
        $author = User::fromID($selectedContentRow['authorID']);
        $createdDateTime = new DateTime($selectedContentRow['createdDateTimeValue']);
        $lastUpdatedDateTime = NULL;
        if ($selectedContentRow['lastUpdatedDateTime']) {
            $lastUpdatedDateTime = new DateTime($selectedContentRow['lastUpdatedDateTime']);
        }
        $archived = $selectedContentRow['archivedValue'] !== 0;

        $content = new self(
            $id,
            $uuid,
            $title,
            $description,
            $body,
            $author,
            $createdDateTime,
            $lastUpdatedDateTime,
            $archived
        );

        return $content;
    }

    public static function all(): array {
        $database = Database::connect();
        $selectedContentRows = NULL;
        try {
            $selectedContentRows = $database->selectRows(
                'CONTENT',
                [
                    'CONTENT_ID' => 'id',
                    'CONTENT_UUID' => 'uuid',
                    'CONTENT_TITLE' => 'title',
                    'CONTENT_DESCRIPTION' => 'description',
                    'CONTENT_BODY' => 'body',
                    'USER_ID' => 'authorID',
                    'CONTENT_CREATED_DATETIME' => 'createdDateTimeValue',
                    'CONTENT_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue',
                    'CONTENT_ARCHIVED' => 'archivedValue'
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        $content = [];
        foreach ($selectedContentRows as $_ => $selectedContentRow) {
            $id = $selectedContentRow['id'];
            $uuid = $selectedContentRow['uuid'];
            $title = $selectedContentRow['title'];
            $description = $selectedContentRow['description'];
            $body = $selectedContentRow['body'];
            $author = User::fromID($selectedContentRow['authorID']);
            $createdDateTime = new DateTime($selectedContentRow['createdDateTimeValue']);
            $lastUpdatedDateTime = NULL;
            if ($selectedContentRow['lastUpdatedDateTime']) {
                $lastUpdatedDateTime = new DateTime($selectedContentRow['lastUpdatedDateTime']);
            }
            $archived = $selectedContentRow['archivedValue'] !== 0;

            $content []= new self(
                $id,
                $uuid,
                $title,
                $description,
                $body,
                $author,
                $createdDateTime,
                $lastUpdatedDateTime,
                $archived
            );
        }

        return $content;
    }

    public static function allByAuthor(User $author): array {
        $authorID = $author->getID();

        $database = Database::connect();
        $selectedContentRows = NULL;
        try {
            $selectedContentRows = $database->selectRows(
                'CONTENT',
                [
                    'CONTENT_ID' => 'id',
                    'CONTENT_UUID' => 'uuid',
                    'CONTENT_TITLE' => 'title',
                    'CONTENT_DESCRIPTION' => 'description',
                    'CONTENT_BODY' => 'body',
                    'CONTENT_CREATED_DATETIME' => 'createdDateTimeValue',
                    'CONTENT_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue',
                    'CONTENT_ARCHIVED' => 'archivedValue'
                ],
                '`USER_ID` = :authorID',
                NULL,
                NULL,
                NULL,
                [
                    ':authorID' => $authorID
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        $content = [];
        foreach ($selectedContentRows as $_ => $selectedContentRow) {
            $id = $selectedContentRow['id'];
            $uuid = $selectedContentRow['uuid'];
            $title = $selectedContentRow['title'];
            $description = $selectedContentRow['description'];
            $body = $selectedContentRow['body'];
            $createdDateTime = new DateTime($selectedContentRow['createdDateTimeValue']);
            $lastUpdatedDateTime = NULL;
            if ($selectedContentRow['lastUpdatedDateTime']) {
                $lastUpdatedDateTime = new DateTime($selectedContentRow['lastUpdatedDateTime']);
            }
            $archived = $selectedContentRow['archivedValue'] !== 0;

            $content []= new self(
                $id,
                $uuid,
                $title,
                $description,
                $body,
                $author,
                $createdDateTime,
                $lastUpdatedDateTime,
                $archived
            );
        }

        return $content;
    }

    public static function fromUUID(string $uuid): ?self {
        $database = Database::connect();
        $selectedContentRow = NULL;
        try {
            $selectedContentRow = $database->selectRow(
                'CONTENT',
                [
                    'CONTENT_ID' => 'id',
                    'CONTENT_TITLE' => 'title',
                    'CONTENT_DESCRIPTION' => 'description',
                    'CONTENT_BODY' => 'body',
                    'USER_ID' => 'authorID',
                    'CONTENT_CREATED_DATETIME' => 'createdDateTimeValue',
                    'CONTENT_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue',
                    'CONTENT_ARCHIVED' => 'archivedValue'
                ],
                '`CONTENT_UUID` = :uuid',
                [
                    ':uuid' => $uuid
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        if (!$selectedContentRow) {
            return NULL;
        }
        $id = $selectedContentRow['id'];
        $title = $selectedContentRow['title'];
        $description = $selectedContentRow['description'];
        $body = $selectedContentRow['body'];
        $author = User::fromID($selectedContentRow['authorID']);
        $createdDateTime = new DateTime($selectedContentRow['createdDateTimeValue']);
        $lastUpdatedDateTime = NULL;
        if ($selectedContentRow['lastUpdatedDateTime']) {
            $lastUpdatedDateTime = new DateTime($selectedContentRow['lastUpdatedDateTime']);
        }
        $archived = $selectedContentRow['archivedValue'] !== 0;

        $content = new self(
            $id,
            $uuid,
            $title,
            $description,
            $body,
            $author,
            $createdDateTime,
            $lastUpdatedDateTime,
            $archived
        );

        return $content;
    }

    public function getID(): int {
        return $this->id;
    }

    public function getUUID(): string {
        return $this->uuid;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getBody(): string {
        return $this->body;
    }

    public function getAuthor(): User {
        return $this->author;
    }

    public function getCreatedDateTime(string $format = 'value'): string|DateTime {
        return match($format) {
            'value' => $this->createdDateTime,
            default => $this->createdDateTime->format($format)
        };
    }

    public function getLastUpdatedDateTime(string $format = 'value'): null|string|DateTime {
        return match($format) {
            'value' => $this->createdDateTime,
            default => $this->createdDateTime->format($format)
        };
    }

    public function isArchived(): bool {
        return $this->archived;
    }

    public function changeTitle(string $newTitle): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        if (!$currentUser->isAdministrator()) {
            $contentPermissions = Permissions::between($currentUser, $this);
            if (!($contentPermissions && $contentPermissions->canWrite())) {
                throw new UserNotPermittedException();
            }
        }

        if ($this->isArchived()) {
            throw new ContentArchivedException();
        }
        
        $id = $this->id;
        $oldTitle = $this->title;
        $newTitle = self::validateTitle($newTitle) ?? throw new InvalidContentDataException('title');
        if ($oldTitle === $newTitle) {
            return;
        }

        $database = Database::connect();
        $updatedContentRow = NULL;
        try {
            $updatedContentRow = $database->updateRow(
                'CONTENT',
                [
                    'CONTENT_TITLE' => ':newTitle',
                    'CONTENT_LAST_UPDATED_DATETIME' => 'NOW()'
                ],
                '`CONTENT_ID` = :id',
                [
                    ':id' => $id,
                    ':newTitle' => $newTitle
                ],
                [
                    'CONTENT_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue'
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        $newLastUpdatedDateTime = new DateTime($updatedContentRow['$newLastUpdatedDateTimeValue']);

        $uuid = $this->uuid;

        // TODO: Specify action
        Audit::log(
            "Changed Content \"$oldTitle\" ($uuid) title",
            $this,
            ''
        );

        $this->title = $newTitle;
        $this->lastUpdatedDateTime = $newLastUpdatedDateTime;
    }

    public function changeDescription(string $newDescription): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        if (!$currentUser->isAdministrator()) {
            $contentPermissions = Permissions::between($currentUser, $this);
            if (!($contentPermissions && $contentPermissions->canWrite())) {
                throw new UserNotPermittedException();
            }
        }

        if ($this->isArchived()) {
            throw new ContentArchivedException();
        }

        
        $id = $this->id;
        $oldDescription = $this->description;
        $newDescription = self::validateDescription($newDescription) ?? throw new InvalidContentDataException('description');
        if ($oldDescription === $newDescription) {
            return;
        }

        $database = Database::connect();
        $updatedContentRow = NULL;
        try {
            $updatedContentRow = $database->updateRow(
                'CONTENT',
                [
                    'CONTENT_DESCRIPTION' => ':newDescription',
                    'CONTENT_LAST_UPDATED_DATETIME' => 'NOW()'
                ],
                '`CONTENT_ID` = :id',
                [
                    ':id' => $id,
                    ':newDescription' => $newDescription
                ],
                [
                    'CONTENT_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue'
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        $newLastUpdatedDateTime = new DateTime($updatedContentRow['$newLastUpdatedDateTimeValue']);

        $uuid = $this->uuid;
        $title = $this->title;

        // TODO: Specify action
        Audit::log(
            "Changed Content \"$title\" ($uuid) description",
            $this,
            ''
        );

        $this->description = $newDescription;
        $this->lastUpdatedDateTime = $newLastUpdatedDateTime;
    }

    public function changeBody(string $newBody): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        if (!$currentUser->isAdministrator()) {
            $contentPermissions = Permissions::between($currentUser, $this);
            if (!($contentPermissions && $contentPermissions->canWrite())) {
                throw new UserNotPermittedException();
            }
        }

        if ($this->isArchived()) {
            throw new ContentArchivedException();
        }

        $id = $this->id;
        $oldBody = $this->body;
        $newBody = self::validateBody($newBody) ?? throw new InvalidContentDataException('body');
        if ($oldBody === $newBody) {
            return;
        }

        $database = Database::connect();
        $updatedContentRow = NULL;
        try {
            $updatedContentRow = $database->updateRow(
                'CONTENT',
                [
                    'CONTENT_BODY' => ':newBody',
                    'CONTENT_LAST_UPDATED_DATETIME' => 'NOW()'
                ],
                '`CONTENT_ID` = :id',
                [
                    ':id' => $id,
                    ':newBody' => $newBody
                ],
                [
                    'CONTENT_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue'
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        $newLastUpdatedDateTime = new DateTime($updatedContentRow['$newLastUpdatedDateTimeValue']);

        $uuid = $this->uuid;
        $title = $this->title;

        // TODO: Specify action
        Audit::log(
            "Changed Content \"$title\" ($uuid) body",
            $this,
            ''
        );

        $this->body = $newBody;
        $this->lastUpdatedDateTime = $newLastUpdatedDateTime;
    }

    public function archive(): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        if (!$currentUser->isAdministrator()) {
            $contentPermissions = Permissions::between($currentUser, $this);
            if (!($contentPermissions && $contentPermissions->canArchive())) {
                throw new UserNotPermittedException();
            }
        }

        if ($this->isArchived()) {
            throw new ContentArchivedException();
        }

        $id = $this->id;

        $database = Database::connect();
        try {
            $database->updateRow(
                'CONTENT',
                [
                    'CONTENT_ARCHIVED' => 'TRUE'
                ],
                '`CONTENT_ID` = :id',
                [
                    ':id' => $id
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }

        $uuid = $this->uuid;
        $title = $this->title;

        // TODO: Specify action
        Audit::log(
            "Archived Content \"$title\" ($uuid)",
            $this,
            ''
        );

        $this->archived = true;
    }

    public function unarchive(): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        if (!$currentUser->isAdministrator()) {
            $contentPermissions = Permissions::between($currentUser, $this);
            if (!($contentPermissions && $contentPermissions->canArchive())) {
                throw new UserNotPermittedException();
            }
        }

        if (!$this->isArchived()) {
            throw new ContentNotArchivedException();
        }

        $id = $this->id;

        $database = Database::connect();
        try {
            $database->updateRow(
                'CONTENT',
                [
                    'CONTENT_ARCHIVED' => 'FALSE'
                ],
                '`CONTENT_ID` = :id',
                [
                    ':id' => $id
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }

        $uuid = $this->uuid;
        $title = $this->title;

        // TODO: Specify action
        Audit::log(
            "Unarchived Content \"$title\" ($uuid)",
            $this,
            ''
        );

        $this->archived = false;
    }

    public function delete(): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        if (!$currentUser->isAdministrator()) {
            $contentPermissions = Permissions::between($currentUser, $this);
            if (!($contentPermissions && $contentPermissions->canDelete())) {
                throw new UserNotPermittedException();
            }
        }

        if (!$this->isArchived()) {
            throw new ContentNotArchivedException();
        }

        $id = $this->id;

        $database = Database::connect();
        try {
            $database->deleteRow(
                'CONTENT',
                '`CONTENT_ID` = :id',
                [
                    ':id' => $id
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }

        $title = $this->title;
        $uuid = $this->uuid;

        // TODO: Specify action
        Audit::log(
            "Deleted Content \"$title\" ($uuid)",
            NULL,
            ''
        );
    }
}
?>