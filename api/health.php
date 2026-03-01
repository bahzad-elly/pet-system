<?php
// api/health.php – Live medical records & vaccinations
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit;
}
require_once '../db.php';

$animal_id = intval($_GET['animal_id'] ?? 0);
if ($animal_id === 0) { http_response_code(400); echo json_encode(['error'=>'Missing animal_id']); exit; }

try {
    $med = $pdo->prepare("SELECT * FROM medical_record WHERE animal_id = :id ORDER BY created_at DESC");
    $med->execute([':id' => $animal_id]);
    $medRows = $med->fetchAll(PDO::FETCH_ASSOC);

    $vac = $pdo->prepare("SELECT av.*, vt.vaccine_name FROM animal_vaccination av JOIN vaccination_types vt ON av.vtype_id=vt.vtype_id WHERE av.animal_id=:id ORDER BY av.date DESC");
    $vac->execute([':id' => $animal_id]);
    $vacRows = $vac->fetchAll(PDO::FETCH_ASSOC);

    // Build medical HTML
    $medHtml = '';
    if (count($medRows) > 0) {
        foreach ($medRows as $m) {
            $medHtml .= '<tr>
                <td style="color:var(--text2);white-space:nowrap">'.date('Y-m-d', strtotime($m['created_at'])).'</td>
                <td><strong>'.htmlspecialchars($m['visit_type']).'</strong><br>
                    <span style="color:var(--text2);font-size:.75rem">Dx: '.htmlspecialchars($m['diagnoses']).'</span><br>
                    <span style="color:var(--text2);font-size:.75rem">Tx: '.htmlspecialchars($m['treatment']).'</span></td>
                <td>'.htmlspecialchars($m['treatedBy']).'</td></tr>';
        }
    } else {
        $medHtml = '<tr><td colspan="3"><div class="empty">No medical records found.</div></td></tr>';
    }

    // Build vaccination HTML
    $vacHtml = '';
    if (count($vacRows) > 0) {
        foreach ($vacRows as $v) {
            $next = $v['nextDate'] ? htmlspecialchars($v['nextDate']) : '<span style="color:var(--text2)">N/A</span>';
            $vacHtml .= '<tr>
                <td><strong>'.htmlspecialchars($v['vaccine_name']).'</strong></td>
                <td>'.htmlspecialchars($v['date']).'</td>
                <td style="color:var(--r);font-weight:700">'.$next.'</td></tr>';
        }
    } else {
        $vacHtml = '<tr><td colspan="3"><div class="empty">No vaccinations recorded.</div></td></tr>';
    }

    echo json_encode(['med_html' => $medHtml, 'vac_html' => $vacHtml, 'med_count' => count($medRows), 'vac_count' => count($vacRows)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
