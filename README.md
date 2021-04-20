# TimeTracker

Web App for tracking your time. Currently only locally deployable.

## Features

* Use tags on each time entry so you can track multiple records at once
* Add descriptions in Markdown 
* Working on more, see TODO.md for what's coming up

## Local Setup

1. Make sure you have php 8.0 or greater
2. Install [Composer](https://getcomposer.org/download/)
3. Install [Yarn](https://classic.yarnpkg.com/en/docs/install)
4. Install [Symfony Binary](https://symfony.com/download)
   
5. Cd to project root and run

```bash
composer install
yarn encore dev
```

6. Set up your database connection in `.env`

7. Run migrations

```bash
./bin/console doctrine:migrations:migrate
```

8. To start the symfony server

```bash
symfony serve
```
