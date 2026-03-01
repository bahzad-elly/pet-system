<?php
// api/notifications.php — Live notifications from last 12 hours
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit;
}
require_once '../db.php';

// "Seen" IDs are passed from the client so we know what's unread
$seen_ids = json_decode($_GET['seen'] ?? '[]', true);
if (!is_array($seen_ids)) $seen_ids = [];

try {
    // Fetch activity from last 12 hours
    $stmt = $pdo->prepare("
        SELECT a.logid, u.fullname, u.role,
               a.actiontype, a.targettable, a.targetid, a.details, a.created_at
        FROM user_activity_log a
        JOIN users u ON a.uid = u.uid
        WHERE a.created_at >= NOW() - INTERVAL 12 HOUR
        ORDER BY a.created_at DESC
        LIMIT 25
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items   = [];
    $unread  = 0;
    $now     = time();

    foreach ($rows as $r) {
        $id        = $r['logid'];
        $isNew     = !in_array($id, $seen_ids);
        if ($isNew) $unread++;

        // Icon & color based on action type
        $type = strtolower($r['actiontype']);
        if (str_contains($type, 'create') || str_contains($type, 'add')) {
            $icon = 'fa-plus-circle'; $color = '#10b981'; $bg = 'rgba(16,185,129,.15)';
        } elseif (str_contains($type, 'update') || str_contains($type, 'edit')) {
            $icon = 'fa-pen-to-square'; $color = '#8b5cf6'; $bg = 'rgba(139,92,246,.15)';
        } elseif (str_contains($type, 'delete')) {
            $icon = 'fa-trash'; $color = '#ef4444'; $bg = 'rgba(239,68,68,.15)';
        } elseif (str_contains($type, 'login')) {
            $icon = 'fa-right-to-bracket'; $color = '#3b82f6'; $bg = 'rgba(59,130,246,.15)';
        } else {
            $icon = 'fa-bolt'; $color = '#f59e0b'; $bg = 'rgba(245,158,11,.15)';
        }

        // Human-friendly time ago
        $ts   = strtotime($r['created_at']);
        $diff = $now - $ts;
        if ($diff < 60)        $ago = 'just now';
        elseif ($diff < 3600)  $ago = floor($diff/60)   .'m ago';
        elseif ($diff < 86400) $ago = floor($diff/3600)  .'h ago';
        else                   $ago = date('M d', $ts);

        // Friendly title
        $table = ucfirst(str_replace('_', ' ', $r['targettable']));
        $title = $r['details'] ?: ($r['actiontype'].' on '.$table);

        $items[] = [
            'id'      => $id,
            'icon'    => $icon,
            'color'   => $color,
            'bg'      => $bg,
            'title'   => htmlspecialchars($title),
            'sub'     => htmlspecialchars($r['fullname']).' · '.$ago,
            'isNew'   => $isNew,
            'ts'      => $r['created_at'],
        ];
    }

    echo json_encode([
        'items'  => $items,
        'total'  => count($rows),
        'unread' => $unread,
    ]);

} catch (PDOException $e) {
    // Table might not exist yet — return empty gracefully
    echo json_encode(['items'=>[], 'total'=>0, 'unread'=>0]);
}
