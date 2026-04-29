<?php
require __DIR__ . '/config.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];

// ----------------------------------------------------------------
// GET  — list users / find by email / get session
// ----------------------------------------------------------------
if ($method === 'GET') {
    // Session check
    if (($_GET['action'] ?? '') === 'session') {
        if (!empty($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT id, full_name, email, team_id, role, created_at FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            json(['success' => true, 'user' => $user ?: null]);
        }
        json(['success' => true, 'user' => null]);
    }

    // Find by email
    if (!empty($_GET['email'])) {
        $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, team_id, role, created_at FROM users WHERE email = ?");
        $stmt->execute([$_GET['email']]);
        $user = $stmt->fetch();
        json(['success' => true, 'user' => $user ?: null]);
    }

    // List all
    $stmt = $pdo->query("SELECT id, full_name, email, team_id, role, created_at FROM users ORDER BY created_at DESC");
    json(['success' => true, 'users' => $stmt->fetchAll()]);
}

// ----------------------------------------------------------------
// POST — create user / login / set session
// ----------------------------------------------------------------
if ($method === 'POST') {
    $data = input();

    // Set session (after login)
    if (($_GET['action'] ?? '') === 'session') {
        if (!empty($data['user_id'])) {
            $_SESSION['user_id'] = $data['user_id'];
            json(['success' => true]);
        }
        unset($_SESSION['user_id']);
        json(['success' => true]);
    }

    // Login
    if (($_GET['action'] ?? '') === 'login') {
        if (empty($data['email']) || empty($data['password'])) {
            json(['success' => false, 'error' => 'Email and password required'], 400);
        }
        $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, team_id, role, created_at FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            json(['success' => false, 'error' => 'Invalid credentials'], 401);
        }
        $_SESSION['user_id'] = $user['id'];
        unset($user['password_hash']);
        json(['success' => true, 'user' => $user]);
    }

    // Create user
    $id = $data['id'] ?? bin2hex(random_bytes(18));
    $hash = password_hash($data['password'], PASSWORD_BCRYPT);
    try {
        $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, password_hash, team_id, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $id,
            $data['full_name'] ?? '',
            $data['email'] ?? '',
            $hash,
            $data['team_id'] ?? 'dsi',
            $data['role'] ?? 'member',
        ]);
        json(['success' => true, 'user' => [
            'id' => $id,
            'full_name' => $data['full_name'] ?? '',
            'email' => $data['email'] ?? '',
            'team_id' => $data['team_id'] ?? 'dsi',
            'role' => $data['role'] ?? 'member',
            'created_at' => date('c'),
        ]]);
    } catch (PDOException $e) {
        json(['success' => false, 'error' => 'Email already exists'], 409);
    }
}

// ----------------------------------------------------------------
// DELETE — delete user / clear session
// ----------------------------------------------------------------
if ($method === 'DELETE') {
    if (($_GET['action'] ?? '') === 'session') {
        unset($_SESSION['user_id']);
        json(['success' => true]);
    }

    $id = $_GET['id'] ?? '';
    if (!$id) {
        json(['success' => false, 'error' => 'Missing id'], 400);
    }
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    json(['success' => true]);
}

json(['success' => false, 'error' => 'Method not allowed'], 405);

