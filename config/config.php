<?php
// Basic configuration for DB and app

// Update these to match your local DB
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'smart_parking';
const DB_USER = 'root';
const DB_PASS = '';

// App settings
const APP_NAME = 'Smart Parking';
const APP_ENV = 'local';
const APP_BASE_URL = 'http://localhost:8000';
const TOKEN_TTL_HOURS = 48; // auth token expiry

// CORS settings
const CORS_ALLOW_ORIGIN = '*';
const CORS_ALLOW_METHODS = 'GET, POST, PUT, PATCH, DELETE, OPTIONS';
const CORS_ALLOW_HEADERS = 'Content-Type, Authorization';

