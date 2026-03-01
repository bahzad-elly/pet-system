<?php
// api/animals.php – Real-time animal search & filter endpoint
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit;
}

require_once '../db.php';

$search  = trim($_GET['search']  ?? '');
$status  = trim($_GET['status']  ?? '');
$species = trim($_GET['species'] ?? '');
$page    = max(1, intval($_GET['page'] ?? 1));
$limit   = intval($_GET['limit'] ?? 25);
$offset  = ($page - 1) * $limit;

$where   = ['1=1'];
$params  = [];

if ($search !== '') {
    $where[] = '(name LIKE :s OR breed LIKE :s OR species LIKE :s)';
    $params[':s'] = '%'.$search.'%';
}
if ($status !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}
if ($species !== '') {
    $where[] = 'species LIKE :species';
    $params[':species'] = '%'.$species.'%';
}

$whereStr = implode(' AND ', $where);

try {
    $total = $pdo->prepare("SELECT COUNT(*) FROM animals WHERE $whereStr");
    $total->execute($params);
    $totalRows = (int)$total->fetchColumn();

    $stmt = $pdo->prepare("SELECT animal_id, name, gender, species, breed, age, status, photo, dateadded FROM animals WHERE $whereStr ORDER BY dateadded DESC LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Status badge map
    $badgeMap = [
        'Available'   => '<span class="bdg bg">Available</span>',
        'Adopted'     => '<span class="bdg bb">Adopted</span>',
        'Pending'     => '<span class="bdg by">Pending</span>',
        'Medical Care'=> '<span class="bdg bpu">Medical Care</span>',
    ];

    $html = '';
    if (count($rows) > 0) {
        foreach ($rows as $a) {
            $badge = $badgeMap[$a['status']] ?? '<span class="bdg bgray">'.htmlspecialchars($a['status']).'</span>';
            $photo = (!empty($a['photo']) && file_exists('../uploads/'.$a['photo']))
                ? '<img src="uploads/'.htmlspecialchars($a['photo']).'" class="animal-photo" alt="Photo">'
                : '<div class="no-photo">🐾</div>';
            $isAdmin = ($_SESSION['role'] === 'admin')
                ? '<a href="delete_animal.php?id='.$a['animal_id'].'" class="btn btn-r btn-sm" onclick="return confirm(\'Delete?\')"><i class="fas fa-trash"></i></a>'
                : '';
            $html .= '<tr data-id="'.$a['animal_id'].'">
                <td>'.$photo.'</td>
                <td><strong>'.htmlspecialchars($a['name']).'</strong></td>
                <td>'.htmlspecialchars($a['species']).'<br><span style="color:var(--text2);font-size:.78rem">'.htmlspecialchars($a['breed']?: '—').'</span></td>
                <td>'.htmlspecialchars($a['age']?: '?').' yrs<br><span style="color:var(--text2);font-size:.78rem">'.htmlspecialchars($a['gender']).'</span></td>
                <td>'.$badge.'</td>
                <td><div style="display:flex;gap:5px;flex-wrap:wrap;">
                    <a href="edit_animal.php?id='.$a['animal_id'].'" class="btn btn-y btn-sm"><i class="fas fa-pen"></i></a>
                    <a href="animal_health.php?id='.$a['animal_id'].'" class="btn btn-b btn-sm"><i class="fas fa-stethoscope"></i></a>
                    '.$isAdmin.'
                </div></td></tr>';
        }
    } else {
        $html = '<tr><td colspan="6"><div class="empty"><i class="fas fa-paw"></i><p>No animals found.</p></div></td></tr>';
    }

    echo json_encode([
        'html'  => $html,
        'total' => $totalRows,
        'page'  => $page,
        'pages' => max(1, ceil($totalRows / $limit)),
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
