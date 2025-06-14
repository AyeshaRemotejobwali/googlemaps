<?php
require 'db.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Get input data
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $lat = floatval($data['lat'] ?? 0);
    $lng = floatval($data['lng'] ?? 0);

    // Validate input
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Location name is required']);
        exit;
    }
    if ($lat == 0 || $lng == 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid coordinates']);
        exit;
    }

    // Prepare and execute query
    $stmt = $pdo->prepare("INSERT INTO locations (name, lat, lng) VALUES (:name, :lat, :lng)");
    $result = $stmt->execute([
        ':name' => $name,
        ':lat' => $lat,
        ':lng' => $lng
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Location saved successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save location']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'General error: ' . $e->getMessage()]);
}
?>
