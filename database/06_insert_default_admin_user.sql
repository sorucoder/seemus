INSERT INTO `USER` (
    `USER_NAME`,
    `USER_EMAIL`,
    `USER_PASSWORD_HASH`,
    `USER_ROLE`
)
VALUE (
    'Administrator',
    'admin@seemus.com',
    '$2y$10$MSBJc5iNC98ii.a7kyCPx.ZKbWgNhVGsedZkoHcy4zPWb5cwUjbuG',
    'admin'
);