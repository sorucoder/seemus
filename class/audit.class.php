<?php
declare(strict_types=1);

require_once $_SERVER['ROOT_PATH'] . '/class/database.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/user.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/content.class.php';
require_once $_SERVER['ROOT_PATH'] . '/class/file.class.php';

require_once $_SERVER['ROOT_PATH'] . '/class/exception/user/user_not_logged_in.exception.php';

final class Audit {
    private int $id;
    private string $description;
    private User $actor;
    private null|Content|File $media;
    private DateTime $date;
    private string $action;

    private function __construct(int $id, string $description, User $actor, null|Content|File $media, DateTime $date, string $action) {
        $this->id = $id;
        $this->description = $description;
        $this->actor = $actor;
        $this->media = $media;
        $this->date = $date;
        $this->action = $action;
    }

    public static function log(string $description, null|Content|File $media, string $action): self {
        $currentUser = User::current();
        if (!$currentUser) {
            throw new UserNotLoggedInException();
        }

        $currentUserID = $currentUser->getID();
        $mediaID = $media ? $media->getID() : NULL;

        $database = Database::connect();
        $insertedAuditRow = NULL;
        try {
            $insertedAuditRow = $database->insertRow(
                'AUDIT',
                [
                    'AUDIT_DESCRIPTION' => ':description',
                    'USER_ID' => ':actorID',
                    'CONTENT_ID' => $media instanceof Content ? ':mediaID' : 'NULL',
                    'FILE_ID' => $media instanceof File ? ':mediaID' : 'NULL',
                    'AUDIT_ACTION' => ':action'
                ],
                [
                    ':description' => $description,
                    ':actorID' => $currentUserID,
                    ':mediaID' => $mediaID,
                    ':action' => $action
                ], 
                [
                    'AUDIT_ID' => 'id',
                    'AUDIT_DATETIME' => 'dateTimeValue'
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions here
            default:
                throw $exception;
            }
        }
        $id = $insertedAuditRow['id'];
        $date = new DateTime($insertedAuditRow['dateTimeValue']);

        $audit = new Audit(
            $id,
            $description,
            $currentUser,
            $media,
            $date,
            $action
        );
        
        return $audit;
    }
    
    public static function fromID(int $id): ?self {
        $database = Database::connect();
        $selectedAuditRow = NULL;
        try {
            $selectedAuditRow = $database->selectRow(
                'AUDIT',
                [
                    'AUDIT_DESCRIPTION' => 'description',
                    'USER_ID' => 'actorID',
                    'CONTENT_ID' => 'contentID',
                    'FILE_ID' => 'fileID',
                    'AUDIT_DATETIME' => 'dateTimeValue',
                    'AUDIT_ACTION' => 'action'
                ],
                '`AUDIT_ID` = :id',
                [
                    ':id' => $id
                ]
            );
        } catch (PDOException $exception) {
            switch ($exception->getMessage()) {
            // TODO: Write cases for common exceptions here
            default:
                throw $exception;
            }
        }
        if (!$selectedAuditRow) {
            return NULL;
        }
        $description = $selectedAuditRow['description'];
        $actor = User::fromID($selectedAuditRow['actorID']);
        $media = NULL;
        if ($selectedAuditRow['contentID']) {
            $media = Content::fromID($selectedAuditRow['contentID']);
        } else if ($selectedAuditRow['fileID']) {
            $media = File::fromID($selectedAuditRow['fileID']);
        }
        $date = new DateTime($selectedAuditRow['dateTimeValue']);
        $action = $selectedAuditRow['action'];

        $audit = new Audit(
            $id,
            $description,
            $actor,
            $media,
            $date,
            $action
        );

        return $audit;
    }

    public function getID(): int {
        return $this->id;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getActor(): User {
        return $this->actor;
    }

    public function getMedia(): Content|File {
        return $this->media;
    }

    public function getDate(): DateTime {
        return $this->date;
    }

    public function getAction(): string {
        return $this->action;
    }
}
?>