# e_commerce_symfony

## Install

Clone the project

Make sure symfony-cli, composer and PHP 8.2 are installed.

Install depencencies via `composer install`.

Launch your local phpmyadmin server.

Create a `.env.local` file with credentials for phpmyadmin.

Create database via `symfony console d:d:c`.

Ignore the SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'categorie' as it run two times

Migrate the database via `symfony console d:m:m`.

Launch the app via `symfony serve:start`

Go to http://127.0.0.1:8000.

Enjoy.

## Available Languages :

FR French
CH Chinese
PL Polish
LG Lingala
RU Russian
JP Japanese
AR Arabic

## Authors

Oliwer SKWERES. https://github.com/swerkf
Isaac De Gaston Koumous. https://github.com/ikdg0
