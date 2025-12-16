<?php
header('Content-Type: application/json');
require __DIR__ . '/db_connection.php';

try {
    // Get and validate parameters
    $location = isset($_GET['location']) ? trim($_GET['location']) : null;
    $venue_type = isset($_GET['venue_type']) ? trim($_GET['venue_type']) : null;
    $price_range = isset($_GET['price_range']) ? trim($_GET['price_range']) : null;
    $capacity = isset($_GET['capacity']) ? trim($_GET['capacity']) : null;

    // Base query
    $query = "SELECT * FROM venues WHERE 1=1";
    $params = [];

    // Location filter
    if ($location) {
        $query .= " AND location LIKE CONCAT('%', :location, '%')";
        $params[':location'] = $location;
    }

    // Venue type filter
    if ($venue_type) {
        $query .= " AND venue_type = :venue_type";
        $params[':venue_type'] = str_replace('_', ' ', $venue_type);
    }

    // Price range filter
    if ($price_range) {
        // Handle the + symbol properly (encoded as %2B in URL)
        $price_range = str_replace('%2B', '+', $price_range);
        
        if ($price_range === '5000+') {
            $query .= " AND (veg_price >= 5000 OR non_veg_price >= 5000)";
        } else {
            $rangeParts = explode('-', $price_range);
            if (count($rangeParts) === 2) {
                $query .= " AND (
                    (veg_price BETWEEN :min_price AND :max_price) OR 
                    (non_veg_price BETWEEN :min_price AND :max_price)
                )";
                $params[':min_price'] = (float)$rangeParts[0];
                $params[':max_price'] = (float)$rangeParts[1];
            }
        }
    }

    // Capacity filter
    if ($capacity) {
        // Handle the + symbol properly
        $capacity = str_replace('%2B', '+', $capacity);
        
        if ($capacity === '1000+') {
            $query .= " AND max_capacity >= 1000";
        } else {
            $capacityParts = explode('-', $capacity);
            if (count($capacityParts) === 2) {
                $query .= " AND (
                    (min_capacity <= :max_cap AND max_capacity >= :min_cap)
                )";
                $params[':min_cap'] = (int)$capacityParts[0];
                $params[':max_cap'] = (int)$capacityParts[1];
            }
        }
    }

    // Prepare and execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($results),
        'data' => $results
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request',
        'message' => $e->getMessage()
    ]);
}