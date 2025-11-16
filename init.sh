#!/bin/bash

echo "🚀 Starting UEEX Company Versioning API..."

# Build and start containers
docker-compose up -d --build

# Wait for database to be healthy
echo "⏳ Waiting for database to be ready..."
docker-compose exec app bash -c 'until php artisan migrate:status >/dev/null 2>&1; do sleep 1; done'

# Run migrations and seeders
echo "📊 Running migrations and seeders..."
docker-compose exec app php artisan migrate:fresh --seed --force

echo "✅ Setup complete!"
echo ""
echo "🌐 API available at: http://localhost:8080"
echo "📖 Swagger docs: http://localhost:8080/api/documentation"
echo "🗄️ phpMyAdmin: http://localhost:8081"