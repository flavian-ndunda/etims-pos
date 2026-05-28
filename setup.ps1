# eTIMS POS — One-Click Setup Script for Windows PowerShell
# Run this ONCE after copying this folder into a fresh Laravel 11/12/13 project
# Usage: .\setup.ps1

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  eTIMS POS Setup" -ForegroundColor Cyan
Write-Host "  KRA eTIMS + M-Pesa Integration" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Step 1 - Allow Composer plugins
Write-Host "Step 1: Configuring Composer plugins..." -ForegroundColor Yellow
composer config allow-plugins.pestphp/pest-plugin true
composer config allow-plugins.php-http/discovery true
Write-Host "Done." -ForegroundColor Green

# Step 2 - Install dependencies
Write-Host ""
Write-Host "Step 2: Installing PHP dependencies..." -ForegroundColor Yellow
composer install
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: composer install failed. Check the error above." -ForegroundColor Red
    exit 1
}
Write-Host "Done." -ForegroundColor Green

# Step 3 - Environment setup
Write-Host ""
Write-Host "Step 3: Setting up environment..." -ForegroundColor Yellow
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host ".env created from .env.example" -ForegroundColor Green
} else {
    Write-Host ".env already exists - skipping" -ForegroundColor Gray
}

# Step 4 - Generate app key
Write-Host ""
Write-Host "Step 4: Generating application key..." -ForegroundColor Yellow
php artisan key:generate --ansi
Write-Host "Done." -ForegroundColor Green

# Step 5 - Create SQLite database
Write-Host ""
Write-Host "Step 5: Creating SQLite database..." -ForegroundColor Yellow
if (-not (Test-Path "database\database.sqlite")) {
    New-Item -ItemType File -Path "database\database.sqlite" | Out-Null
    Write-Host "database.sqlite created" -ForegroundColor Green
} else {
    Write-Host "database.sqlite already exists - skipping" -ForegroundColor Gray
}

# Step 6 - Publish SDK assets
Write-Host ""
Write-Host "Step 6: Publishing SDK config and migrations..." -ForegroundColor Yellow
php artisan vendor:publish --tag=etims-config --force
php artisan vendor:publish --tag=etims-migrations --force
Write-Host "Done." -ForegroundColor Green

# Step 7 - Run migrations
Write-Host ""
Write-Host "Step 7: Running database migrations..." -ForegroundColor Yellow
php artisan migrate --force
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: migrations failed." -ForegroundColor Red
    exit 1
}
Write-Host "Done." -ForegroundColor Green

# Step 8 - Seed demo data
Write-Host ""
Write-Host "Step 8: Seeding demo data (products, users)..." -ForegroundColor Yellow
php artisan db:seed --force
Write-Host "Done." -ForegroundColor Green

# Step 9 - Install Node dependencies and build assets
Write-Host ""
Write-Host "Step 9: Installing frontend assets..." -ForegroundColor Yellow
if (Test-Path "package.json") {
    npm install
    npm run build
    Write-Host "Frontend assets built." -ForegroundColor Green
} else {
    Write-Host "No package.json found - skipping frontend build" -ForegroundColor Gray
}

# Step 10 - Create queue table
Write-Host ""
Write-Host "Step 10: Creating queue tables..." -ForegroundColor Yellow
php artisan queue:table 2>$null
php artisan migrate --force
Write-Host "Done." -ForegroundColor Green

# Done!
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  Setup Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host ""
Write-Host "  1. Start the web server:" -ForegroundColor White
Write-Host "     php artisan serve" -ForegroundColor Yellow
Write-Host ""
Write-Host "  2. In a SECOND terminal, start the queue worker:" -ForegroundColor White
Write-Host "     php artisan queue:work --queue=etims,default --tries=5" -ForegroundColor Yellow
Write-Host ""
Write-Host "  3. Open your browser:" -ForegroundColor White
Write-Host "     http://localhost:8000" -ForegroundColor Yellow
Write-Host ""
Write-Host "  Login credentials:" -ForegroundColor White
Write-Host "  Email:    admin@demo.co.ke" -ForegroundColor Cyan
Write-Host "  Password: password" -ForegroundColor Cyan
Write-Host ""
Write-Host "  POS Terminal: http://localhost:8000/pos" -ForegroundColor Cyan
Write-Host "  Dashboard:    http://localhost:8000/dashboard" -ForegroundColor Cyan
Write-Host "  M-Pesa:       http://localhost:8000/mpesa" -ForegroundColor Cyan
Write-Host ""
