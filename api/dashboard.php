<?php
// api/dashboard.php – Live dashboard stats endpoint
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit;
}
require_once '../db.php';

try {
    $stats = [
        'total'     => (int)$pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn(),
        'available' => (int)$pdo->query("SELECT COUNT(*) FROM animals WHERE status='Available'")->fetchColumn(),
        'adopted'   => (int)$pdo->query("SELECT COUNT(*) FROM animals WHERE status='Adopted'")->fetchColumn(),
        'pending'   => (int)$pdo->query("SELECT COUNT(*) FROM animals WHERE status='Pending'")->fetchColumn(),
        'medical'   => (int)$pdo->query("SELECT COUNT(*) FROM animals WHERE status='Medical Care'")->fetchColumn(),
        'total_adopters'  => (int)$pdo->query("SELECT COUNT(*) FROM adopters")->fetchColumn(),
        'total_adoptions' => (int)$pdo->query("SELECT COUNT(*) FROM adoption")->fetchColumn(),
    ];

    // Recent activity
    $activity = [];
    try {
        $act = $pdo->query("SELECT u.fullname, a.actiontype, a.targettable, a.details, a.created_at
                            FROM user_activity_log a JOIN users u ON a.uid=u.uid
                            ORDER BY a.created_at DESC LIMIT 5");
        $activity = $act->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // Recent animals
    $recent = $pdo->query("SELECT name, species, breed, age, status FROM animals ORDER BY animal_id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // Upcoming vaccines
    $vaccines = [];
    try {
        $vaccines = $pdo->query("SELECT an.name as animal_name, vt.vaccine_name, av.nextDate
                                 FROM animal_vaccination av
                                 JOIN animals an ON av.animal_id=an.animal_id
                                 JOIN vaccination_types vt ON av.vtype_id=vt.vtype_id
                                 WHERE av.nextDate IS NOT NULL ORDER BY av.nextDate ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    echo json_encode(['stats' => $stats, 'activity' => $activity, 'recent' => $recent, 'vaccines' => $vaccines]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
