# eTIMS POS — Complete Laravel Application

> Offline-first POS with KRA eTIMS fiscalization and M-Pesa payments.
> Built for Kenyan retail businesses.

## What's Included

| Feature | Status |
|---|---|
| POS terminal with product search | ✅ |
| KRA eTIMS invoice submission (sync + async) | ✅ |
| Queue-based offline sync | ✅ |
| M-Pesa STK Push | ✅ |
| M-Pesa manual transaction verification | ✅ |
| Failed invoice recovery dashboard | ✅ |
| KRA fiscal receipt with QR code | ✅ |
| Product management | ✅ |
| Sync status dashboard | ✅ |

---

## Installation (Windows)

### Requirements
- PHP 8.2+
- Composer
- Node.js (optional — for building CSS)

### Step 1 — Create a fresh Laravel project

```powershell
composer create-project laravel/laravel etims-pos
cd etims-pos
```

### Step 2 — Copy these files into the project

Copy everything from this folder into your `etims-pos` folder, replacing existing files.

### Step 3 — Run the setup script

```powershell
.\setup.ps1
```

This does everything automatically:
- Installs dependencies
- Creates .env
- Generates app key
- Creates SQLite database
- Publishes SDK config and migrations
- Runs all migrations
- Seeds 15 demo products

### Step 4 — Start the application

**Terminal 1 — Web server:**
```powershell
php artisan serve
```

**Terminal 2 — Queue worker** (processes KRA submissions):
```powershell
php artisan queue:work --queue=etims,default --tries=5
```

Open: **http://localhost:8000**

**Login:** admin@demo.co.ke / password

---

## Manual Installation (if setup.ps1 fails)

```powershell
composer config allow-plugins.pestphp/pest-plugin true
composer install
copy .env.example .env
php artisan key:generate
New-Item -ItemType File -Path database\database.sqlite
php artisan vendor:publish --tag=etims-config
php artisan vendor:publish --tag=etims-migrations
php artisan migrate --seed
php artisan queue:table
php artisan migrate
```

---

## Configuration

### KRA eTIMS (.env)

```dotenv
ETIMS_MODE=sandbox
ETIMS_PIN=your-kra-pin
ETIMS_BRANCH_ID=00
ETIMS_DEVICE_SERIAL=your-device-serial
ETIMS_SECRET=your-api-secret
```

### M-Pesa (.env)

```dotenv
MPESA_SANDBOX=true
MPESA_CONSUMER_KEY=your-key
MPESA_CONSUMER_SECRET=your-secret
MPESA_SHORTCODE=174379
```

Get M-Pesa credentials from: https://developer.safaricom.co.ke

---

## How Offline Mode Works

1. **No internet** → Sales saved to SQLite, invoices queued locally
2. **Internet returns** → Queue worker submits all pending invoices to KRA
3. **KRA confirms** → Sale updated with receipt number and QR code

The queue worker must be running for sync to happen.

---

## URLs

| Page | URL |
|---|---|
| Dashboard | http://localhost:8000/dashboard |
| POS Terminal | http://localhost:8000/pos |
| Invoices | http://localhost:8000/invoices |
| Failed Invoices | http://localhost:8000/invoices/failed |
| M-Pesa Payments | http://localhost:8000/mpesa |
| Sync Status | http://localhost:8000/sync |
| Products | http://localhost:8000/products |
