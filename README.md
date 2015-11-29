# KIV/WEB semestral work
Semestral work for KIV/WEB created by Martin Úbl (A13B0453P)

## Installation

To install WEBKONF system, just upload all files to your webserver, open command line, navigate to site root, and type:

```
cd app
php composer.phar install --no-dev
```

Next step is to create database on your or your provider's database server. Log on to database server console and create desired database:

```
CREATE DATABASE webkonf CHARACTER SET = 'utf8';
```

You may want to create specific user for this system, as well, as give him rights to operate with tables and records in newly created database.
Also replace `localhost` with proper host, where your system would run, and `webkonf` with real database name.

```
CREATE USER webkonf_user@localhost IDENTIFIED BY 'w3bk0nf';
GRANT ALL PRIVILEGES ON webkonf.* TO webkonf_user@localhost;
```

Then, assuming you run site root on your local server root, use web browser to navigate to:

[http://localhost/install.php](http://localhost/install.php)

or generally on any remote web server

[http://webkonf.cz/install.php](http://webkonf.cz/install.php)

Optionally, you may not use automated installer, and import `install/db.structure.sql` into your database manually, and copy `install/config.inc.template.php` to `app/config/config.inc.php` and fill config variables with correct values.

## Testing data

When using automated installer, you may choose to import testing data. These data contains 6 contributions and 9 users.

Users present in testing data: `autor1, autor2, autor3, autor4, recenzent1, recenzent2, recenzent3, recenzent4, administrator`

Password for all testing users is: `password`
