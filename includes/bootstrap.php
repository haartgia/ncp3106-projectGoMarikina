<?php
/**
 * App Bootstrap (pages)
 *
 * Starts session/auth, opens DB connection, and loads shared helpers.
 * Include this at the top of page scripts to avoid repeated requires.
 */

require_once __DIR__ . '/../config/auth.php';   // session + auth helpers
require_once __DIR__ . '/../config/db.php';     // $conn + get_db_connection()
require_once __DIR__ . '/helpers.php';          // UI/formatting helpers
