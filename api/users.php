<?php
// api/users.php – Real-time user search endpoint (admin only)
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit;
}
require_once '../db.php';

$search = trim($_GET['search'] ?? '');
$params = [];
$where  = '1=1';
if ($search !== '') {
    $where = '(fullname LIKE :s OR username LIKE :s OR email LIKE :s OR role LIKE :s)';
    $params[':s'] = '%'.$search.'%';
}

try {
    $stmt = $pdo->prepare("SELECT uid, username, fullname, email, role, created_at FROM users WHERE $where ORDER BY created_at DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '';
    if (count($rows) > 0) {
        foreach ($rows as $u) {
            $av       = strtoupper(substr($u['fullname'], 0, 1));
            $roleClass= $u['role'] === 'admin' ? 'badge-admin' : 'badge-staff';
            $roleHtml = $u['role'] === 'admin' ? '<span class="bdg badge-admin">Admin</span>' : '<span class="bdg badge-staff">Staff</span>';
            $date     = date('M d, Y', strtotime($u['created_at']));
            $delete   = ($u['uid'] !== $_SESSION['uid'])
                ? '<a href="delete_user.php?id='.$u['uid'].'" class="btn btn-r btn-sm" onclick="return confirm(\'Delete this user?\')"><i class="fas fa-trash"></i></a>'
                : '';
            $html .= '<tr>
                <td><div style="display:flex;align-items:center;gap:10px;">
                    <div class="user-av">'.$av.'</div>
                    <strong>'.htmlspecialchars($u['fullname']).'</strong>
                </div></td>
                <td style="color:var(--text2)">'.htmlspecialchars($u['username']).'</td>
                <td style="color:var(--text2)">'.htmlspecialchars($u['email']).'</td>
                <td>'.$roleHtml.'</td>
                <td style="color:var(--text2)">'.$date.'</td>
                <td><div style="display:flex;gap:5px">
                    <a href="edit_user.php?id='.$u['uid'].'" class="btn btn-y btn-sm"><i class="fas fa-pen"></i></a>
                    '.$delete.'
                </div></td></tr>';
        }
    } else {
        $html = '<tr><td colspan="6"><div class="empty"><i class="fas fa-users"></i><p>No users found.</p></div></td></tr>';
    }

    echo json_encode(['html' => $html, 'total' => count($rows)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
