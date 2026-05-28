# eTIMS POS

Offline-first Laravel POS system for Kenyan businesses with KRA eTIMS fiscalization and M-Pesa payments.

## Features

- POS terminal with product search and cart
- KRA eTIMS invoice submission (sync + async queue)
- Offline mode — sales saved locally, synced when internet returns
- M-Pesa STK Push payments
- M-Pesa manual transaction verification
- Failed invoice recovery dashboard
- KRA fiscal receipt with QR code
- Product and category management

## Requirements

- PHP 8.2+
- Composer
- Node.js (optional)

## Installation

```bash
git clone https://github.com/flavian-ndunda/etims-pos.git
cd etims-pos
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan vendor:publish --tag=etims-config
php artisan vendor:publish --tag=etims-migrations
php artisan migrate --seed
```

## Running

Terminal 1:
```bash
php artisan serve
```

Terminal 2:
```bash
php artisan queue:work --queue=etims,default --tries=5
```

Open http://localhost:8000 — login: admin@demo.co.ke / password

## Configuration

Copy `.env.example` to `.env` and set:

```dotenv
ETIMS_MODE=sandbox
ETIMS_PIN=your-kra-pin
ETIMS_DEVICE_SERIAL=your-device-serial
ETIMS_SECRET=your-api-secret

MPESA_CONSUMER_KEY=your-key
MPESA_CONSUMER_SECRET=your-secret
MPESA_SHORTCODE=174379
```

## SDK

This project uses [flavytech/laravel-etims](https://github.com/flavian-ndunda/laravel-etims)
— the open-source Laravel SDK for KRA eTIMS integration.

## License

MIT