Write-Host "=============================================" -ForegroundColor Cyan
Write-Host " Monochrome News Flash - Backend Setup Script " -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

if (!(Get-Command composer -ErrorAction SilentlyContinue)) {
    Write-Host "[ERROR] Composer is not installed or not in PATH." -ForegroundColor Red
    Write-Host "Please install Composer from https://getcomposer.org/ and try again." -ForegroundColor Yellow
    exit 1
}

if (!(Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "[ERROR] PHP is not installed or not in PATH." -ForegroundColor Red
    Write-Host "Please install PHP 8.1+ and try again." -ForegroundColor Yellow
    exit 1
}

if (!(Test-Path ".env")) {
    Write-Host "[INFO] Copying .env.example to .env..." -ForegroundColor Green
    Copy-Item .env.example .env
} else {
    Write-Host "[INFO] .env file already exists. Skipping copy." -ForegroundColor Yellow
}

Write-Host "[INFO] Installing Composer dependencies..." -ForegroundColor Green
composer install

Write-Host "[INFO] Generating Application Key..." -ForegroundColor Green
php artisan key:generate

Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host " Setup Phase 1 Complete! " -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Open the .env file and configure your database settings:" -ForegroundColor White
Write-Host "   DB_CONNECTION=mysql" -ForegroundColor Gray
Write-Host "   DB_HOST=127.0.0.1" -ForegroundColor Gray
Write-Host "   DB_PORT=3306" -ForegroundColor Gray
Write-Host "   DB_DATABASE=monochrome_news" -ForegroundColor Gray
Write-Host "   DB_USERNAME=root" -ForegroundColor Gray
Write-Host "   DB_PASSWORD=your_password" -ForegroundColor Gray
Write-Host "2. Create the empty database 'monochrome_news' in your MySQL server." -ForegroundColor White
Write-Host "3. Run the following command to migrate and seed the database:" -ForegroundColor White
Write-Host "   php artisan migrate:fresh --seed" -ForegroundColor Cyan
Write-Host "4. Start the server:" -ForegroundColor White
Write-Host "   php artisan serve" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
