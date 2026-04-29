<?php
require __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

// ----------------------------------------------------------------
// GET — list punches / filter by team / filter by user today
// ----------------------------------------------------------------
if ($method === 'GET') {
    $teamId = $_GET['team_id'] ?? null;
    $userId = $_GET['user_id'] ?? null;
    $today  = isset($_GET['today']) ? true : false;

    $sql = "SELECT id, user_id, user_full_name, team_id, kind, location, at_time, validated, late, created_at FROM punch_records WHERE 1=1";
    $params = [];

    if ($teamId) {
        $sql .= " AND team_id = ?";
        $params[] = $teamId;
    }
    if ($userId) {
        $sql .= " AND user_id = ?";
        $params[] = $userId;
    }
    if ($today) {
        $sql .= " AND DATE(at_time) = CURDATE()";
    }
    $sql .= " ORDER BY at_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Map snake_case -> camelCase for front-end compatibility
    $records = array_map(function ($r) {
        return [
            'id'           => $r['id'],
            'userId'       => $r['user_id'],
            'userFullName' => $r['user_full_name'],
            'teamId'       => $r['team_id'],
            'kind'         => $r['kind'],
            'location'     => $r['location'],
            'at'           => $r['at_time'],
            'validated'    => (bool) $r['validated'],
            'late'         => (bool) $r['late'],
            'createdAt'    => $r['created_at'],
        ];
    }, $rows);

    json(['success' => true, 'punches' => $records]);
}

// ----------------------------------------------------------------
// POST — create punch
// ----------------------------------------------------------------
if ($method === 'POST') {
    $data = input();
    $id = $data['id'] ?? bin2hex(random_bytes(18));

    $stmt = $pdo->prepare("INSERT INTO punch_records (id, user_id, user_full_name, team_id, kind, location, at_time, validated, late, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $id,
        $data['user_id'] ?? '',
        $data['user_full_name'] ?? '',
        $data['team_id'] ?? 'dsi',
        $data['kind'] ?? 'in',
        $data['location'] ?? 'onsite',
        $data['at_time'] ?? date('c'),
        $data['validated'] ? 1 : 0,
        $data['late'] ? 1 : 0,
    ]);

    json(['success' => true, 'punch' => [
        'id'           => $id,
        'userId'       => $data['user_id'] ?? '',
        'userFullName' => $data['user_full_name'] ?? '',
        'teamId'       => $data['team_id'] ?? 'dsi',
        'kind'         => $data['kind'] ?? 'in',
        'location'     => $data['location'] ?? 'onsite',
        'at'           => $data['at_time'] ?? date('c'),
        'validated'    => (bool) ($data['validated'] ?? false),
        'late'         => (bool) ($data['late'] ?? false),
        'createdAt'    => date('c'),
    ]]);
}

// ----------------------------------------------------------------
// PATCH — update punch
// ----------------------------------------------------------------
if ($method === 'PATCH') {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        json(['success' => false, 'error' => 'Missing id'], 400);
    }
    $data = input();

    $fields = [];
    $params = [];
    if (array_key_exists('at', $data)) {
        $fields[] = 'at_time = ?';
        $params[] = $data['at'];
    }
    if (array_key_exists('validated', $data)) {
        $fields[] = 'validated = ?';
        $params[] = $data['validated'] ? 1 : 0;
    }
    if (array_key_exists('late', $data)) {
        $fields[] = 'late = ?';
        $params[] = $data['late'] ? 1 : 0;
    }
    if (array_key_exists('kind', $data)) {
        $fields[] = 'kind = ?';
        $params[] = $data['kind'];
    }
    if (array_key_exists('location', $data)) {
        $fields[] = 'location = ?';
        $params[] = $data['location'];
    }

    if (!$fields) {
        json(['success' => false, 'error' => 'No fields to update'], 400);
    }

    $sql = "UPDATE punch_records SET " . implode(', ', $fields) . " WHERE id = ?";
    $params[] = $id;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json(['success' => true]);
}

json(['success' => false, 'error' => 'Method not allowed'], 405);

