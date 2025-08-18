<?php
require_once __DIR__ . '/../../backend/src/config.php';
require_once __DIR__ . '/../../backend/src/db.php';
require_once __DIR__ . '/../../backend/src/utils.php';
require_once __DIR__ . '/../../backend/src/auth.php';

allow_cors();
start_session_if_needed();