<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/database.class.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/user.class.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/media/invalid_file_data.exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/media/file_archived.exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/media/file_not_archived.exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/user/user_not_logged_in.exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/class/exception/user/user_not_permitted.exception.php';

final class File {
    private int $id;
    private string $uuid;
    private string $title;
    private string $description;
    private string $mimeType;
    private string $bytes;
    private User $uploader;
    private DateTime $createdDateTime;
    private ?DateTime $lastUpdatedDateTime;
    private bool $archived;

    // Selected from https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
    private const MIME_TYPES = [
        'audio/aac' => ['.aac'],
        'application/x-adbiword' => ['.abw'],
        'image/apng' => ['.apng'],
        'application/x-freearc' => ['.arc'],
        'image/avif' => ['.avif'],
        'video/x-msvideo' => ['.avi'],
        'image/bmp' => ['.bmp'],
        'application/x-bzip' => ['.bz'],
        'application/x-bzip2' => ['.bz2'],
        'text/csv'  => ['.csv'],
        'application/msword'  => ['.doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['.docx'],
        'application/vnd.ms-fontobject' => ['.eot'],
        'application/epub+zip' => ['.epub'],
        'application/gzip' => ['.gz'],
        'image/gif' => ['.gif'],
        'image/vnd.microsoft.icon' => ['.ico'],
        'text/calendar' => ['.ics'],
        'image/jpeg' => ['.jpg', '.jpeg'],
        'application/json' => ['.json'],
        'audio/midi' => ['.mid', '.midi'],
        'audio/x-midi' => ['.mid', '.midi'],
        'audio/mpeg' => ['.mp3'],
        'video/mp4' => ['.mp4'],
        'video/mpeg' => ['.mpeg'],
        'application/vnd.oasis.opendocument.presentation' => ['.odp'],
        'application/vnd.oasis.opendocument.spreadsheet' => ['.ods'],
        'application/vnd.oasis.opendocument.text' => ['.odt'],
        'audio/ogg' => ['.ogg'],
        'video/ogg' => ['.ogg'],
        'application/ogg' => ['.ogg'],
        'audio/opus' => ['.opus'],
        'font/otf' => ['.otf'],
        'image/png' => ['.png'],
        'application/pdf' => ['.pdf'],
        'application/vnd.ms-powerpoint' => ['.ppt'],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['.pptx'],
        'application/vnd.rar' => ['.rar'],
        'image/svg+xml' => ['.svg'],
        'application/x-tar' => ['.tar'],
        'image/tiff' => ['.tif', '.tiff'],
        'video/mp2t' => ['.ts'],
        'font/ttf' => ['.ttf'],
        'application/vnd.visio' => ['.vsd'],
        'audio/wav' => ['.wav'],
        'audio/webm' => ['.weba'],
        'video/webm' => ['.webm'],
        'image/webp' => ['.webp'],
        'font/woff' => ['.woff'],
        'font/woff2' => ['.woff2'],
        'application/vnd.ms-excel' => ['.xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['.xlsx'],
        'application/xml' => ['.xml'],
        'text/xml' => ['.xml'],
        'application/zip' => ['.zip'],
        'audio/3gpp' => ['.3gp', '.3gpp'],
        'video/3gpp' => ['.3gp', '.3gpp'],
        'audio/3gpp2' => ['.3g2', '.3gpp2'],
        'video/3gpp2' => ['.3g2', '.3gpp2'],
        'application/x-7z-compressed' => ['.7z']
    ];

    private function __construct(int $id, string $uuid, string $title, string $description, string $mimeType, string $bytes, User $uploader, DateTime $createdDateTime, ?DateTime $lastUpdatedDateTime, bool $archived) {
        $this->id = $id;
        $this->uuid = $uuid;
        $this->title = $title;
        $this->description = $description;
        $this->mimeType = $mimeType;
        $this->bytes = $bytes;
        $this->uploader = $uploader;
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

    private static function validateMimeType(string $mimeType): ?string {
        if (!isset(self::MIME_TYPES[$mimeType])) {
            return NULL;
        }
        return $mimeType;
    }

    private static function validateBytes(string $bytes): ?string {
        // TODO: Find a REST API to validate files and use it
        return $bytes;
    }
    
    public static function upload(string $title, string $description, string $mimeType, string $bytes): self {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        $title = self::validateTitle($title) ?? throw new InvalidFileDataException('invalid title');
        $description = self::validateDescription($description) ?? throw new InvalidFileDataException('invalid description');
        $mimeType = self::validateMimeType($mimeType) ?? throw new InvalidFileDataException('invalid mime type');
        $bytes = self::validateBytes($bytes) ?? throw new InvalidFileDataException('invalid bytes');

        $currentUserID = $currentUser->getID();

        $database = Database::connect();

        $insertedFileRow = NULL;
        try {
            $insertedFileRow = $database->insertRow(
                'FILE',
                [
                    'FILE_TITLE' => ':title',
                    'FILE_DESCRIPTION' => ':description',
                    'FILE_MIME_TYPE' => ':mimeType',
                    'FILE_BYTES' => ':bytes',
                    'USER_ID' => ':uploaderID'
                ],
                [
                    ':title' => $title,
                    ':description' => $description,
                    ':mimeType' => $mimeType,
                    ':bytes' => $bytes,
                    ':uploaderID' => $currentUserID
                ],
                [
                    'FILE_ID' => 'id',
                    'FILE_UUID' => 'uuid',
                    'FILE_CREATED_DATETIME' => 'createdDateTimeValue'
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        $id = $insertedFileRow['id'];
        $uuid = $insertedFileRow['uuid'];
        $createdDateTime = new DateTime($insertedFileRow['createdDateTimeValue']);

        $file = new self(
            $id,
            $uuid,
            $title,
            $description,
            $mimeType,
            $bytes,
            $currentUser,
            $createdDateTime,
            NULL,
            false
        );

        // TODO: Specify action
        Audit::log(
            "Uploaded File \"$title\" ($uuid)",
            $file,
            ''
        );

        if (!$currentUser->isAdministrator()) {
            Permissions::establish(
                $currentUser,
                $file,
                true,
                true,
                true,
                true
            );
        }

        return $file;
    }

    public static function fromID(int $id): ?self {
        $database = Database::connect();
        $selectedFileRow = NULL;
        try {
            $selectedFileRow = $database->selectRow(
                'FILE',
                [
                    'FILE_UUID' => 'uuid',
                    'FILE_TITLE' => 'title',
                    'FILE_DESCRIPTION' => 'description',
                    'FILE_MIME_TYPE' => 'mimeType',
                    'FILE_BYTES' => 'bytes',
                    'USER_ID' => 'uploaderID',
                    'FILE_CREATED_DATETIME' => 'createdDateTimeValue',
                    'FILE_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue',
                    'FILE_ARCHIVED' => 'archivedValue'
                ],
                '`FILE_ID` = :id',
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
        $uuid = $selectedFileRow['uuid'];
        $title = $selectedFileRow['title'];
        $description = $selectedFileRow['description'];
        $mimeType = $selectedFileRow['mimeType'];
        $bytes = $selectedFileRow['bytes'];
        $uploader = User::fromID($selectedFileRow['uploaderID']);
        $createdDateTime = new DateTime($selectedFileRow['createdDateTimeValue']);
        $lastUpdatedDateTime = NULL;
        if ($selectedFileRow['lastUpdatedDateTimeValue']) {
            $lastUpdatedDateTime = new DateTime($selectedFileRow['lastUpdatedDateTimeValue']);
        }
        $archived = $selectedFileRow['archived'] !== 0;

        $file = new self(
            $id,
            $uuid,
            $title,
            $description,
            $mimeType,
            $bytes,
            $uploader,
            $createdDateTime,
            $lastUpdatedDateTime,
            $archived
        );

        return $file;
    }

    public static function fromUUID(string $uuid): ?self {
        $database = Database::connect();
        $selectedFileRow = NULL;
        try {
            $selectedFileRow = $database->selectRow(
                'FILE',
                [
                    'FILE_ID' => 'id',
                    'FILE_TITLE' => 'title',
                    'FILE_DESCRIPTION' => 'description',
                    'FILE_MIME_TYPE' => 'mimeType',
                    'FILE_BYTES' => 'bytes',
                    'USER_ID' => 'uploaderID',
                    'FILE_CREATED_DATETIME' => 'createdDateTimeValue',
                    'FILE_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue',
                    'FILE_ARCHIVED' => 'archivedValue'
                ],
                '`FILE_UUID` = :uuid',
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
        $id = $selectedFileRow['id'];
        $title = $selectedFileRow['title'];
        $description = $selectedFileRow['description'];
        $mimeType = $selectedFileRow['mimeType'];
        $bytes = $selectedFileRow['bytes'];
        $uploader = User::fromID($selectedFileRow['uploaderID']);
        $createdDateTime = new DateTime($selectedFileRow['createdDateTimeValue']);
        $lastUpdatedDateTime = NULL;
        if ($selectedFileRow['lastUpdatedDateTimeValue']) {
            $lastUpdatedDateTime = new DateTime($selectedFileRow['lastUpdatedDateTimeValue']);
        }
        $archived = $selectedFileRow['archived'] !== 0;

        $file = new self(
            $id,
            $uuid,
            $title,
            $description,
            $mimeType,
            $bytes,
            $uploader,
            $createdDateTime,
            $lastUpdatedDateTime,
            $archived
        );

        return $file;
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

    public function getMimeType(): string {
        return $this->mimeType;
    }

    public function getBytes(): string {
        return $this->bytes;
    }

    public function getUploader(): User {
        return $this->uploader;
    }

    public function getCreatedDateTime(): DateTime {
        return $this->createdDateTime;
    }

    public function getLastUpdatedDateTime(): ?DateTime {
        return $this->lastUpdatedDateTime;
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
            $filePermissions = Permissions::between($currentUser, $this);
            if (!($filePermissions && $filePermissions->canWrite())) {
                throw new UserNotPermittedException();
            }
        }

        if ($this->isArchived()) {
            throw new FileArchivedException();
        }
        
        $id = $this->id;
        $oldTitle = $this->title;
        $newTitle = self::validateTitle($newTitle) ?? throw new InvalidFileDataException('invalid title');
        if ($oldTitle === $newTitle) {
            return;
        }

        $database = Database::connect();
        $updatedFileRow = NULL;
        try {
            $updatedFileRow = $database->updateRow(
                'FILE',
                [
                    'FILE_TITLE' => ':newTitle',
                    'FILE_LAST_UPDATED_DATETIME' => 'NOW()'
                ],
                '`FILE_ID` = :id',
                [
                    ':id' => $id,
                    ':newTitle' => $newTitle 
                ],
                [
                    'FILE_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue'
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        $newLastUpdatedDateTime = new DateTime($updatedFileRow['lastUpdatedDateTime']);

        $uuid = $this->uuid;

        // TODO: Specify action
        Audit::log(
            "Changed File \"$oldTitle\" ($uuid) title",
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
            $filePermissions = Permissions::between($currentUser, $this);
            if (!($filePermissions && $filePermissions->canWrite())) {
                throw new UserNotPermittedException();
            }
        }

        if ($this->isArchived()) {
            throw new FileArchivedException();
        }
        
        $id = $this->id;
        $oldDescription = $this->description;
        $newDescription = self::validateDescription($newDescription) ?? throw new InvalidFileDataException('invalid title');
        if ($oldDescription === $newDescription) {
            return;
        }

        $database = Database::connect();
        $updatedFileRow = NULL;
        try {
            $updatedFileRow = $database->updateRow(
                'FILE',
                [
                    'FILE_DESCRIPTION' => ':newDescription',
                    'FILE_LAST_UPDATED_DATETIME' => 'NOW()'
                ],
                '`FILE_ID` = :id',
                [
                    ':id' => $id,
                    ':newDescription' => $newDescription
                ],
                [
                    'FILE_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue'
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        $newLastUpdatedDateTime = new DateTime($updatedFileRow['lastUpdatedDateTime']);

        $uuid = $this->uuid;
        $title = $this->title;

        // TODO: Specify action
        Audit::log(
            "Changed File \"$title\" ($uuid) description",
            $this,
            ''
        );

        $this->description = $newDescription;
        $this->lastUpdatedDateTime = $newLastUpdatedDateTime;
    }

    public function replace(string $newMimeType, string $newBytes): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        if (!$currentUser->isAdministrator()) {
            $filePermissions = Permissions::between($currentUser, $this);
            if (!($filePermissions && $filePermissions->canWrite())) {
                throw new UserNotPermittedException();
            }
        }

        if ($this->isArchived()) {
            throw new FileArchivedException();
        }
        
        $id = $this->id;
        $oldMimeType = $this->mimeType;
        $newMimeType = self::validateMimeType($newMimeType) ?? throw new InvalidFileDataException('invalid mime type');
        $oldBytes = $this->bytes;
        $newBytes = self::validateBytes($newBytes) ?? throw new InvalidFileDataException('invalid bytes');
        if ($oldMimeType === $newMimeType && $oldBytes === $newBytes) {
            return;
        }

        $database = Database::connect();
        $updatedFileRow = NULL;
        try {
            $updatedFileRow = $database->updateRow(
                'FILE',
                [
                    'FILE_MIME_TYPE' => ':newMimeType',
                    'FILE_BYTES' => ':newBytes',
                    'FILE_LAST_UPDATED_DATETIME' => 'NOW()'
                ],
                '`FILE_ID` = :id',
                [
                    ':id' => $id,
                    ':newMimeType' => $newMimeType,
                    ':newBytes' => $newBytes
                ],
                [
                    'FILE_LAST_UPDATED_DATETIME' => 'lastUpdatedDateTimeValue'
                ]
            );
        } catch (PDOException $exception) {
            // TODO: Write cases for common exceptions here
            switch ($exception->getMessage()) {
            default:
                throw $exception;
            }
        }
        $newLastUpdatedDateTime = new DateTime($updatedFileRow['lastUpdatedDateTime']);

        $uuid = $this->uuid;
        $title = $this->title;

        // TODO: Specify action
        Audit::log(
            "Replaced File \"$title\" ($uuid)",
            $this,
            ''
        );

        $this->mimeType = $newMimeType;
        $this->bytes = $newBytes;
        $this->lastUpdatedDateTime = $newLastUpdatedDateTime;
    }

    public function archive(): void {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        if (!$currentUser->isAdministrator()) {
            $filePermissions = Permissions::between($currentUser, $this);
            if (!($filePermissions && $filePermissions->canArchive())) {
                throw new UserNotPermittedException();
            }
        }

        if ($this->isArchived()) {
            throw new FileArchivedException();
        }

        $id = $this->id;

        $database = Database::connect();
        try {
            $database->updateRow(
                'FILE',
                [
                    'FILE_ARCHIVED' => 'TRUE'
                ],
                '`FILE_ID` = :id',
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
            "Archived File \"$title\" ($uuid)",
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
            $filePermissions = Permissions::between($currentUser, $this);
            if (!($filePermissions && $filePermissions->canArchive())) {
                throw new UserNotPermittedException();
            }
        }

        if (!$this->isArchived()) {
            throw new FileNotArchivedException();
        }

        $id = $this->id;

        $database = Database::connect();
        try {
            $database->updateRow(
                'FILE',
                [
                    'FILE_ARCHIVED' => 'FALSE'
                ],
                '`FILE_ID` = :id',
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
            "Unarchived File \"$title\" ($uuid)",
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
            $filePermissions = Permissions::between($currentUser, $this);
            if (!($filePermissions && $filePermissions->canDelete())) {
                throw new UserNotPermittedException();
            }
        }

        if (!$this->isArchived()) {
            throw new FileNotArchivedException();
        }

        $id = $this->id;

        $database = Database::connect();
        try {
            $database->deleteRow(
                'FILE',
                '`FILE_ID` = :id',
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
            "Deleted File \"$title\" ($uuid)",
            $this,
            ''
        );
    }
}
?>