# Smart Parking Slot Booking System

A full-stack system for booking parking slots with an interactive map, admin zone management, AI-based peak time prediction, Dijkstra-based nearest available slot suggestion, and QR-based entry/exit.

## Tech Stack
- Frontend: HTML, CSS, JavaScript
- Backend: PHP (PDO) + MySQL
- AI: Simple regression-based peak prediction (PHP)
- Algorithm: Dijkstra’s algorithm for shortest path to nearest available slot
- Bonus: QR-based entry/exit (tokenized, HMAC-signed)

## Quick Start

### Prerequisites
- PHP 8.1+
- MySQL 8+

### 1) Configure environment variables
Create a `.env` file in the project root:

```
DB_HOST=127.0.0.1
DB_NAME=smart_parking
DB_USER=root
DB_PASS=your_password
APP_SECRET=change_this_to_a_random_long_secret
APP_BASE_URL=http://localhost:8000
```

### 2) Create database and load schema

```
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS smart_parking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p smart_parking < db/schema.sql
```

(Optional) Seed sample data:
```
mysql -u root -p smart_parking < db/seed.sql
```

Note: The seed creates sample zones/slots and a simple graph, but no admin user. Register a user via the UI, then promote it to admin:
```
mysql -u root -p smart_parking -e "UPDATE users SET role='admin' WHERE email='your@email.com';"
```

### 3) Run the PHP built-in server

```
php -S localhost:8000 -t public
```

Open `http://localhost:8000` in your browser.

## Features
- User registration/login (sessions)
- Admin zone and slot management (basic endpoints)
- Book/cancel bookings; conflict-safe
- Interactive map of zones/slots
- Nearest available slot suggestion using Dijkstra
- Peak hour prediction chart (simple regression over historical occupancy)
- QR codes for entry/exit; verification endpoint for gates

## Project Structure
```
public/
  index.html
  app.js
  styles.css
  gate.html
  api/
    _bootstrap.php
    auth.php
    zones.php
    bookings.php
    dijkstra.php
    predict.php
    qr.php
backend/
  src/
    config.php
    db.php
    auth.php
    utils.php
    dijkstra.php
    prediction.php
    repositories/
      Users.php
      Zones.php
      Slots.php
      Edges.php
      Bookings.php
      QRTokens.php
      Analytics.php
    services/
      BookingService.php
      QRService.php
      ZoneService.php
      GraphService.php
      PredictionService.php
      AuthService.php

db/
  schema.sql
  seed.sql
```

## Notes
- Security hardening (rate limits, CSRF, strict CORS) minimized for demo. Enable HTTPS, fine-tune CORS, and enforce stricter auth for production.
- Replace `APP_SECRET` with a strong secret.

## License
MIT