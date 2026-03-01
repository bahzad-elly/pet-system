<?php
// api/health_save.php – AJAX endpoint to save medical records & vaccinations
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit;
}
require_once '../db.php';

$action    = $_POST['form_action'] ?? '';
$animal_id = intval($_POST['animal_id'] ?? 0);

if (!$animal_id) { http_response_code(400); echo json_encode(['error'=>'Missing animal_id']); exit; }

try {
    if ($action === 'add_medical') {
        $visit_type = trim($_POST['visit_type'] ?? '');
        $diagnoses  = trim($_POST['diagnoses']  ?? '');
        $treatment  = trim($_POST['treatment']  ?? '');
        $treatedBy  = trim($_POST['treatedBy']  ?? '');
        if (empty($visit_type) || empty($treatment)) {
            echo json_encode(['error'=>'Visit type and treatment are required']); exit;
        }
        $pdo->prepare("INSERT INTO medical_record (animal_id, visit_type, diagnoses, treatment, treatedBy) VALUES (:a,:b,:c,:d,:e)")
            ->execute([':a'=>$animal_id,':b'=>$visit_type,':c'=>$diagnoses,':d'=>$treatment,':e'=>$treatedBy]);

        $pdo->prepare("INSERT INTO user_activity_log (uid, actiontype, targettable, targetid, details) VALUES (:u,'Create','medical_record',:t,'Added medical visit')")
            ->execute([':u'=>$_SESSION['uid'],':t'=>$pdo->lastInsertId()]);

        echo json_encode(['success'=>true, 'message'=>'Medical record saved']);

    } elseif ($action === 'add_vaccine') {
        $vtype_id = intval($_POST['vtype_id'] ?? 0);
        $date     = $_POST['date']   ?? '';
        $nextDate = !empty($_POST['nextDate']) ? $_POST['nextDate'] : null;
        if (!$vtype_id || !$date) {
            echo json_encode(['error'=>'Vaccine type and date are required']); exit;
        }
        $pdo->prepare("INSERT INTO animal_vaccination (animal_id, vtype_id, date, nextDate, userId) VALUES (:a,:b,:c,:d,:e)")
            ->execute([':a'=>$animal_id,':b'=>$vtype_id,':c'=>$date,':d'=>$nextDate,':e'=>$_SESSION['uid']]);

        echo json_encode(['success'=>true, 'message'=>'Vaccination saved']);

    } else {
        http_response_code(400);
        echo json_encode(['error'=>'Unknown action']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error'=>'Database error: '.$e->getMessage()]);
}
