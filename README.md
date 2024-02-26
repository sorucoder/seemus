# Seemus

A simple content management system.

## Setup

1. Install Apache, PHP, and MySQL. This will depend on the operating system you are running.
2. Run the `./migrate.sh` script to set up the database.
3. Modify your VirtualHost file like so:
```
<VirtualHost *.80>
    # ...
    SetEnv DATABASE_DRIVER mysql      # Required: Database PDO driver
    SetEnv DATABASE_HOST localhost    # Required: Database host url
    SetEnv DATABASE_USER root         # Required: Database user
    SetEnv DATABASE_PASSWORD password # Required: Database password
    SetEnv DATABASE_SCHEMA database   # Required: Database schema
    # ...
</VirtualHost>
```
4. The default credentials for the default administrator account are `admin@seemus.com` and `password`. It is **highly** recommended to change the password for this account, or archive this account and create a new administrator account.