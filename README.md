# UEEX Company Versioning API

REST API для управління даними компаній з автоматичним версіонуванням змін.

## Технологічний стек

- PHP 8.2
- Laravel 12.x
- MySQL 8.0
- Docker & Docker Compose
- Swagger/OpenAPI документація

## Встановлення

### Крок 1: Клонування репозиторію

```bash
git clone https://github.com/illiatasminskyi/ueex-company-versioning-api.git
cd ueex-company-versioning-api
```

### Крок 2: Запуск через Docker

```bash
# Запуск контейнерів
docker-compose up -d --build

# Встановлення залежностей
docker-compose exec app composer install

# Міграції та тестові дані (виконати коли MySQL готовий)
docker-compose exec app php artisan migrate:fresh --seed --force
```

**Перевірка готовності MySQL:**
```bash
# Статус контейнерів
docker-compose ps

# Логи MySQL (якщо потрібно)
docker-compose logs db
```

### Крок 3: Перевірка роботи

```bash
# Тестування API
curl http://localhost:8080/api/company/37027819/versions

# Запуск тестів
docker-compose exec app php artisan test
```

## Доступ до сервісів

- **API**: http://localhost:8080
- **Swagger документація**: http://localhost:8080/api/documentation
- **phpMyAdmin**: http://localhost:8081
- **MySQL**: localhost:3306 (laravel/laravel)

## Розробка

### Тестування

```bash
# Запуск всіх тестів
docker-compose exec app php artisan test

# Тести з детальним виводом
docker-compose exec app php artisan test --verbose

# Конкретний тест
docker-compose exec app php artisan test tests/Feature/CompanyApiTest.php
```

### Оновлення Swagger документації

```bash
docker-compose exec app php artisan l5-swagger:generate
```