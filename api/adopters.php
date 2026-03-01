<?php
// api/adopters.php – Real-time adopter search endpoint
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit;
}
require_once '../db.php';

$search = trim($_GET['search'] ?? '');
$params = [];
$where  = '1=1';
if ($search !== '') {
    $where = '(fname LIKE :s OR lname LIKE :s OR phone LIKE :s OR address LIKE :s)';
    $params[':s'] = '%'.$search.'%';
}

try {
    $total = $pdo->prepare("SELECT COUNT(*) FROM adopters WHERE $where");
    $total->execute($params);

    $stmt = $pdo->prepare("SELECT adopterId, fname, lname, phone, address, preference, created_at FROM adopters WHERE $where ORDER BY created_at DESC LIMIT 50");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '';
    if (count($rows) > 0) {
        foreach ($rows as $a) {
            $pref = htmlspecialchars($a['preference'] ?: 'None specified');
            $date = date('M d, Y', strtotime($a['created_at']));
            $html .= '<tr>
                <td><strong>'.htmlspecialchars($a['fname'].' '.$a['lname']).'</strong></td>
                <td style="color:var(--p);font-weight:600">'.htmlspecialchars($a['phone']).'</td>
                <td style="color:var(--text2)">'.htmlspecialchars($a['address']).'</td>
                <td><span class="bdg bgray">'.$pref.'</span></td>
                <td style="color:var(--text2)">'.$date.'</td>
                <td><div style="display:flex;gap:5px">
                    <a href="record_adoption.php?adopter_id='.$a['adopterId'].'" class="btn btn-b btn-sm" title="Process Adoption"><i class="fas fa-house-heart"></i> Process</a>
                </div></td></tr>';
        }
    } else {
        $html = '<tr><td colspan="6"><div class="empty"><i class="fas fa-search"></i><p>No adopters found.</p></div></td></tr>';
    }

    echo json_encode(['html' => $html, 'total' => (int)$total->fetchColumn()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
