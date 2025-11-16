#!/bin/bash

echo "🚀 Starting UEEX Company Versioning API..."

# Build and start containers
docker-compose up -d --build

echo "✅ Containers started successfully!"
echo ""
echo "📋 Next steps:"
echo "1. Install dependencies:"
echo "   docker-compose exec app composer install"
echo ""
echo "2. Wait for database and run migrations:"
echo "   docker-compose exec app php artisan migrate:fresh --seed --force"
echo ""
echo "3. Test the API:"
echo "   curl http://localhost:8080/api/company/37027819/versions"
echo ""
echo "🌐 Services will be available at:"
echo "   - API: http://localhost:8080"
echo "   - Swagger docs: http://localhost:8080/api/documentation"
echo "   - phpMyAdmin: http://localhost:8081"