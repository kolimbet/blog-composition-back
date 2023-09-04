# Backend for ToDo SPA

API on Laravel 9 for Blog SPA

> Backend for https://github.com/kolimbet/blog-spa-front

## API supports:

- authorization and user logout
- registration of new users with verification of the uniqueness of email and user name
- changing the user's password
- uploading and deleting user images, as well as displaying a list of all images uploaded by the user
- setting the user's avatar from the list of images uploaded by him
- actions with user posts for admin
- actions with user comments

## Installation

Clone this repository to your server:

```
git clone https://github.com/kolimbet/blog-spa-back.git blog-spa.back
```

Install the necessary composer packages:

```
composer install
```

Rename .env.example to .env and enter APP_URL and your DB settings in it.

Generate key and link in /public to the storage:

```
php artisan key:generate
php artisan storage:link
```

Generate a database with seeds:

```
php artisan migrate --seed
```

If you used seeds, then when rolling back the database using the migrate:rollback command, clear the /storage/app/seeds/avatars and /storage/app/seeds/posts folders.

Set rights:

```
sudo chown www-data:www-data -R storage/logs
sudo chmod -R 777 storage
sudo chmod -R 777 bootstrap/cache
```
