<?php
// delete_animal.php
session_start();
require_once 'db.php';

// Security Check: Must be logged in AND must be an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    die("Access denied. Only administrators can delete records.");
}

if (isset($_GET['id'])) {
    $animal_id = intval($_GET['id']);

    try {
        // First, get the photo filename so we can delete the image file from the server
        $stmt = $pdo->prepare("SELECT photo, name FROM animals WHERE animal_id = :id");
        $stmt->execute([':id' => $animal_id]);
        $animal = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($animal) {
            // Delete the actual record from the database
            $delete_stmt = $pdo->prepare("DELETE FROM animals WHERE animal_id = :id");
            if ($delete_stmt->execute([':id' => $animal_id])) {
                
                // If there was a photo, delete the file from the uploads folder
                if (!empty($animal['photo']) && file_exists('uploads/' . $animal['photo'])) {
                    unlink('uploads/' . $animal['photo']);
                }

                // Log the deletion operation
                $log_sql = "INSERT INTO user_activity_log (uid, actiontype, targettable, targetid, details) VALUES (:uid, 'Delete', 'animals', :targetid, 'Deleted animal record: " . $animal['name'] . "')";
                $log_stmt = $pdo->prepare($log_sql);
                $log_stmt->execute([':uid' => $_SESSION['uid'], ':targetid' => $animal_id]);

                // Redirect back to the view page
                header("location: view_animals.php");
                exit;
            }
        } else {
            die("Animal not found.");
        }
    } catch (PDOException $e) {
        // If an animal is linked to medical records or adoptions, foreign key constraints might block deletion
        die("Error deleting record: " . $e->getMessage() . "<br><br><a href='view_animals.php'>Go Back</a>");
    }
} else {
    die("No ID provided.");
}
?>