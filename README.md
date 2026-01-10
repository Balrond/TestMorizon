# TestMorizon

Recruitment task: **Phoenix (Elixir) API + Symfony (PHP) client + PostgreSQL**, orchestrated with **Docker Compose**.

This project demonstrates:
- clean Docker-based architecture
- separation of backend API and frontend client
- CSV-based data import
- database-level and application-level data uniqueness
- secure API endpoint with `dry_run` mode
- idempotent imports
- defensive API client implementation (non-JSON, error handling)

---

## Tech stack

- **Elixir / Phoenix** – backend REST API
- **PostgreSQL 15** – database
- **PHP 8.3 / Symfony 7** – frontend / API client
- **Docker & Docker Compose**

---

## Project structure

```
phoenix-api/   # Phoenix API (port 4000)
symfony-app/   # Symfony application (port 8000)
docker-compose.yml
.env           # Docker environment variables
README.md
```

---

## Docker services

| Service  | Description       | Port |
|--------|-------------------|------|
| db      | PostgreSQL 15     | 5432 |
| phoenix | Phoenix API       | 4000 |
| symfony | Symfony frontend  | 8000 |

---

## Environment configuration

The project uses **Docker environment variables** as the main source of configuration.

- `.env` (project root) – used by Docker Compose
- `symfony-app/.env` – minimal bootstrap file required by Symfony runtime

The Symfony `.env` file is intentionally minimal and acts only as a fallback during container bootstrapping.  
All real configuration is provided by Docker.

Required variables:

```env
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=dev-secret
PHOENIX_API_BASE_URL=http://phoenix:4000/api
IMPORT_API_TOKEN=your-token
```

---

## Database configuration

- Database name: `phoenix_app`
- Connection string (Docker internal):

```
ecto://postgres:postgres@db/phoenix_app
```

---

## Migrations

The project relies on **Ecto migrations** and expects them to be executed on startup.

Run manually:

```
docker compose exec phoenix mix ecto.migrate
```

---

## User model

Stored fields:

- `external_id` (UUID, unique)
- `first_name`
- `last_name`
- `gender`
- `birthdate`
- `inserted_at`
- `updated_at`

---

## Data uniqueness & idempotency

Uniqueness is enforced on **two levels**.

### Application level
- `Repo.insert_all/3`
- `on_conflict: :nothing`

### Database level
- **UNIQUE INDEX** on `(first_name, last_name, birthdate, gender)`

---

## CSV import

### Data sources

```
phoenix-api/priv/names/
├── first_names_female.csv
├── first_names_male.csv
├── last_names_female.csv
└── last_names_male.csv
```

### Generation logic
- random gender
- name matched to gender
- birthdate range: `1970-01-01` → `2024-12-31`
- default: **100 users**

---

## API endpoints (Phoenix)

### POST `/api/import`

Header:

```
x-api-token: <IMPORT_API_TOKEN>
```

Payload:

```json
{
  "count": 100,
  "dry_run": true
}
```

---

### Users API

- `GET /api/users`
- `GET /api/users/:id`
- `POST /api/users`
- `PUT /api/users/:id`
- `DELETE /api/users/:id`

Payload example:

```json
{
  "user": {
    "first_name": "John",
    "last_name": "Doe",
    "gender": "male",
    "birthdate": "1990-01-01"
  }
}
```

---

## Symfony application

Symfony acts as a **thin client** on top of Phoenix API.

Features:
- full CRUD
- filters & sorting
- DTO-based validation
- defensive API client
- Bootstrap UI

---

## CLI import helper

```
./bin/import_users
```

Examples:

```
./bin/import_users --dry-run
./bin/import_users --count=20
```

---

## Running the project

```
docker compose up -d
docker compose exec phoenix mix ecto.setup
```

Phoenix: http://localhost:4000  
Symfony: http://localhost:8000

---


Running tests

Run all Symfony tests inside the Docker container:
```
docker compose exec symfony php bin/phpunit
```

Tests are executed in the test environment and do not depend on the database.


---

## Notes

- imports are idempotent
- frontend has no DB access
- API client handles non-JSON errors
