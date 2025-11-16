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

### Крок 2: Налаштування середовища

**Створення файлу .env:**
```bash
# Копіювання шаблону конфігурації
cp .env.example .env
```

**Генерація ключа додатку:**
```bash
# Після запуску контейнерів
docker-compose exec app php artisan key:generate
```

### Крок 3: Запуск через Docker

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

## Найпоширеніші проблеми

### Проблема: Connection refused при міграції

Якщо отримуєте помилку `Connection refused` при спробі запуску міграції:

1. **Перевірте, що .env файл існує:**
   ```bash
   ls -la .env
   ```
   Якщо файл відсутній, скопіюйте з шаблону:
   ```bash
   cp .env.example .env
   ```

2. **Перевірте статус контейнерів:**
   ```bash
   docker-compose ps
   ```

3. **Дочекайтеся готовності MySQL:**
   ```bash
   # Перевірте логи MySQL
   docker-compose logs db
   
   # Або дочекайтеся healthcheck
   docker-compose ps db
   ```

4. **Перезапустіть контейнери при необхідності:**
   ```bash
   docker-compose down
   docker-compose up -d
   ```

### Проблема: Відсутній APP_KEY

Якщо отримуєте помилку про відсутній APP_KEY:

```bash
docker-compose exec app php artisan key:generate
```

### Проблема: Права доступу

Якщо виникають проблеми з правами доступу:

```bash
sudo chown -R $USER:$USER storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```