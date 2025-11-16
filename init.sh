#!/bin/bash

echo "🚀 Starting UEEX Company Versioning API..."

# Build and start containers
docker-compose up -d --build

# Wait for containers to fully initialize
echo "⏳ Waiting for containers to initialize (10 seconds)..."
sleep 10

# Install composer dependencies
echo "📦 Installing dependencies..."
if ! docker-compose exec app composer install --no-interaction; then
    echo "⚠️  First attempt failed, waiting additional 10 seconds..."
    sleep 10
    docker-compose exec app composer install --no-interaction
fi

# Run migrations and seeders with retry
echo "📊 Running migrations and seeders..."
if ! docker-compose exec app php artisan migrate:fresh --seed --force; then
    echo "⚠️  Migration failed, waiting additional 10 seconds..."
    sleep 10
    docker-compose exec app php artisan migrate:fresh --seed --force
fi

echo "✅ Setup complete!"
echo ""
echo "🌐 API available at: http://localhost:8080"
echo "📖 Swagger docs: http://localhost:8080/api/documentation"
echo "🗄️ phpMyAdmin: http://localhost:8081"