# Recruitment Task: Primeo

[![CI](https://github.com/gander/Recruitment-Task__Primeo/actions/workflows/laravel.yml/badge.svg)](https://github.com/gander/Recruitment-Task__Primeo/actions/workflows/laravel.yml)

Zadanie: RESTful API do zarządzania pracownikami (Laravel, Laravel Sanctum). Publiczna lista pracowników z filtrowaniem, sortowaniem i paginacją, logowanie tokenem Bearer, reset hasła oraz chronione operacje CRUD i usuwanie zbiorcze. Dokumentacja API jest generowana przez Scribe, a testy napisane w PHPUnit.

## Requirements

- Docker Engine z Docker Compose v2 (jedyna zależność; PHP ani Composer na hoście nie są potrzebne).
- `curl` do przykładów użycia.

## Install

```bash
docker compose up --build -d --wait
curl --retry 30 --retry-all-errors --retry-delay 2 -f http://localhost:8080/api/employees
```

Kontener przy starcie wykonuje `migrate:fresh --seed` i `scribe:generate`, więc pierwsza odpowiedź może potrwać kilka sekund.

## Usage

- Dokumentacja: <http://localhost:8080/docs>
- Base URL: <http://localhost:8080/api>

### Konta testowe

- **Active**: `active@example.com` / `password123` (może się zalogować)
- **Inactive**: `inactive@example.com` / `password123` (logowanie zablokowane)

### Logowanie i chroniony endpoint

```bash
TOKEN=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "active@example.com", "password": "password123"}' | sed -E 's/.*"token":"([^"]+)".*/\1/')
curl -s http://localhost:8080/api/me -H "Authorization: Bearer $TOKEN"
```

## Test

```bash
docker compose run --rm --no-deps -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: app sh -c 'touch .env && php artisan test'
```

Collection Postman: `postman/Employee_Management_API.postman_collection.json` i `postman/Employee_Management_Environment.postman_environment.json`.

## Override

Lokalne zmiany (np. montowanie kodu do kontenera) trzymaj w `compose.override.yml`, ignorowanym przez git:

```bash
cat > compose.override.yml <<'OVERRIDE'
services:
  app:
    volumes:
      - .:/app
OVERRIDE
docker compose up --build -d --wait
```

## Cleanup

```bash
docker compose down -v --rmi local --remove-orphans
rm -f compose.override.yml
```

## Key Endpoints

### Public
- `GET /api/employees` - List employees (with filtering, sorting, pagination)

### Authentication
- `POST /api/auth/login` - Login
- `POST /api/auth/forgot-password` - Request password reset
- `POST /api/auth/reset-password` - Reset password

### Protected (require Bearer token)
- `POST /api/employees` - Create employee
- `GET /api/employees/{id}` - Get employee details
- `PUT /api/employees/{id}` - Update employee
- `DELETE /api/employees/{id}` - Delete employee
- `DELETE /api/employees/bulk` - Bulk delete
- `GET /api/me` - Current user info

## License

All content in this repository is licensed under a
[CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/).
