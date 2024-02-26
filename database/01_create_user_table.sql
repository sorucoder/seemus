CREATE TABLE `USER` (
    `USER_ID`                            SERIAL,
    `USER_UUID`                          CHAR(36) NOT NULL UNIQUE DEFAULT (UUID()),
    `USER_NAME`                          VARCHAR(64) NOT NULL,
    `USER_EMAIL`                         VARCHAR(256) NOT NULL UNIQUE,
    `USER_PASSWORD_HASH`                 VARCHAR(256) NOT NULL,
    `USER_ROLE`                          ENUM('user', 'admin') NOT NULL,
    `USER_CREATED_DATETIME`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `USER_LAST_LOGIN_DATETIME`           DATETIME,
    `USER_PASSWORD_CHANGES`              INT NOT NULL DEFAULT 0,
    `USER_LAST_PASSWORD_CHANGE_DATETIME` DATETIME,
    `USER_ARCHIVED`                      BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`USER_ID`)
);