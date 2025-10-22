## Khateroshan API

Khateroshan is a Laravel based API for managing projects, tasks, comments, and related analytics.  
The project runs inside Docker and uses Laravel Passport for authentication.

## Quick Start

1. Clone the repository  
   `git clone https://github.com/sajjadnasiribrn/khateroshan.git`
2. Move into the project folder  
   `cd khateroshan`
3. Copy the default environment file  
   `cp src/.env.example src/.env`
4. Build and start the containers  
   `docker compose up -d --build --force-recreate`
5. Install PHP dependencies (if needed)  
   `docker compose run --rm composer update`
6. Generate the application key (first run)  
   `docker compose run --rm artisan key:generate`
7. Run the database migrations  
   `docker compose run --rm artisan migrate`
8. Install Passport clients (required for issuing tokens)  
   `docker compose run --rm artisan passport:install`
9. Open the app at [http://127.0.0.1](http://127.0.0.1)

## API Documentation

- The API docs are generated with [dedoc/scramble](https://github.com/dedoc/scramble).  
- Once the app is running, visit [http://127.0.0.1/docs/api](http://127.0.0.1/docs/api) to explore the interactive documentation.  
- To export the OpenAPI specification, run `docker compose run --rm artisan scramble:export`. This creates `storage/app/api.json`.

## Common Tasks

- Update Composer packages: `docker compose run --rm composer update`
- Run database seeder after migrations: `docker compose run --rm artisan db:seed`
- Replay migrations from scratch: `docker compose run --rm artisan migrate:fresh --seed`
- Run automated tests: `docker compose run --rm artisan test`

## Test Users

```
docker compose run --rm artisan db:seed
```

users:

- Ali Rezaei (`ali.rezaei@example.com`) – role `ADMIN`
- Sara Mohammadi (`sara.mohammadi@example.com`) – role `MANAGER`
- Hossein Karimi (`hossein.karimi@example.com`) – role `MEMBER`

password of all accounts: `Password123!`

## Notes

- The API requires an authenticated user. Use the `/api/auth/login` endpoint to get a token, then pass it as a Bearer token.  
- The docs route is restricted to non-production environments by default. Adjust `config/scramble.php` if you need different access rules.
