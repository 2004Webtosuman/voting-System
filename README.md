## Smart Parking Slot Booking System

Tech Stack: Frontend (HTML/CSS/JS), Backend (PHP + MySQL)

Features:
- Interactive parking map with slot booking
- User authentication (register/login)
- Admin management for zones, slots, and graph edges
- Dijkstra’s algorithm to find the nearest available slot from an entrance
- Simple linear regression to predict peak parking times (next 24h)
- QR-based entry/exit using native BarcodeDetector (fallback to manual code)

### Project Structure

```
config/           # App and DB configuration
models/           # PHP models and helpers
api/              # REST-like endpoints
public/           # Frontend static files and index
sql/              # Database schema and seed data
```

### Quick Start

1) Requirements
- PHP 8+
- MySQL 8+ (or MariaDB 10.5+)

2) Database Setup
- Create a database, e.g., `smart_parking`
- Update `config/config.php` with DB credentials
- Import schema and optional seed:

```bash
mysql -u <user> -p smart_parking < sql/schema.sql
mysql -u <user> -p smart_parking < sql/seed.sql
```

3) Start the PHP dev server

```bash
php -S 0.0.0.0:8000 -t public public/router.php
```

Open http://localhost:8000 in your browser.

### Admin Account
- The very first registered user is promoted to admin automatically.

### Notes
- QR generation uses Google Chart API for image rendering; scanning uses the browser’s `BarcodeDetector` when available, otherwise manual entry.
- The regression predictor computes per-zone occupancy predictions for the next 24 hours using a simple least-squares trend on recent hourly aggregates.

