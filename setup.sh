#!/bin/bash

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}=============================================${NC}"
echo -e "${CYAN} Monochrome News Flash - Backend Setup Script ${NC}"
echo -e "${CYAN}=============================================${NC}"
echo ""

if ! command -v composer &> /dev/null; then
    echo -e "${RED}[ERROR] Composer is not installed or not in PATH.${NC}"
    echo -e "${YELLOW}Please install Composer from https://getcomposer.org/ and try again.${NC}"
    exit 1
fi

if ! command -v php &> /dev/null; then
    echo -e "${RED}[ERROR] PHP is not installed or not in PATH.${NC}"
    echo -e "${YELLOW}Please install PHP 8.1+ and try again.${NC}"
    exit 1
fi

if [ ! -f .env ]; then
    echo -e "${GREEN}[INFO] Copying .env.example to .env...${NC}"
    cp .env.example .env
else
    echo -e "${YELLOW}[INFO] .env file already exists. Skipping copy.${NC}"
fi

echo -e "${GREEN}[INFO] Installing Composer dependencies...${NC}"
composer install

echo -e "${GREEN}[INFO] Generating Application Key...${NC}"
php artisan key:generate

echo ""
echo -e "${CYAN}=============================================${NC}"
echo -e "${GREEN} Setup Phase 1 Complete! ${NC}"
echo -e "${CYAN}=============================================${NC}"
echo -e "${YELLOW}Next Steps:${NC}"
echo -e "1. Open the .env file and configure your database settings:"
echo -e "   ${NC}DB_CONNECTION=mysql"
echo -e "   ${NC}DB_HOST=127.0.0.1"
echo -e "   ${NC}DB_PORT=3306"
echo -e "   ${NC}DB_DATABASE=monochrome_news"
echo -e "   ${NC}DB_USERNAME=root"
echo -e "   ${NC}DB_PASSWORD=your_password"
echo -e "2. Create the empty database 'monochrome_news' in your MySQL server."
echo -e "3. Run the following command to migrate and seed the database:"
echo -e "   ${CYAN}php artisan migrate:fresh --seed${NC}"
echo -e "4. Start the server:"
echo -e "   ${CYAN}php artisan serve${NC}"
echo -e "${CYAN}=============================================${NC}"
