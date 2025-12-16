<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate required fields
$required = ['name', 'email', 'phone', 'venue', 'date', 'guests'];
$missing = [];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields',
        'missing' => $missing
    ]);
    exit;
}

// Validate email
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Prepare SQL with named parameters
    $sql = "INSERT INTO enquiries (name, email, phone, venue, event_date, guests, message, created_at) 
            VALUES (:name, :email, :phone, :venue, :date, :guests, :message, NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    $params = [
        ':name' => htmlspecialchars($_POST['name']),
        ':email' => htmlspecialchars($_POST['email']),
        ':phone' => htmlspecialchars($_POST['phone']),
        ':venue' => htmlspecialchars($_POST['venue']),
        ':date' => $_POST['date'],
        ':guests' => $_POST['guests'],
        ':message' => !empty($_POST['message']) ? htmlspecialchars($_POST['message']) : null
    ];
    
    // Execute the statement
    if ($stmt->execute($params)) {
        echo json_encode([
            'success' => true,
            'message' => 'Enquiry submitted successfully',
            'id' => $pdo->lastInsertId()
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database insert failed',
            'error' => $errorInfo
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
}