<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';

$user = current_user();
if ($user) {
    echo json_encode(['success' => true, 'user' => [
        'id' => $user['id'] ?? null,
        'email' => $user['email'] ?? null,
        'name' => $user['name'] ?? null,
        'role' => $user['role'] ?? null,
    ]]);
} else {
    echo json_encode(['success' => true, 'user' => null]);
}
