<?php
/**
 * API Endpoint — Returns cause-wise donation totals as JSON.
 * Used by the frontend to display real-time raised amounts.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    $pdo = getDBConnection();

    $stmt = $pdo->query("
        SELECT cause,
               COALESCE(SUM(amount), 0) as raised,
               COUNT(*) as donors
        FROM donations
        GROUP BY cause
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build associative map: cause => { raised, donors }
    $data = [];
    foreach ($results as $row) {
        $data[$row['cause']] = [
            'raised' => floatval($row['raised']),
            'donors' => intval($row['donors']),
        ];
    }

    // Also return overall totals
    $totals = $pdo->query("
        SELECT COALESCE(SUM(amount), 0) as total_raised,
               COUNT(*) as total_donors
        FROM donations
    ")->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'causes'  => $data,
        'totals'  => [
            'raised' => floatval($totals['total_raised']),
            'donors' => intval($totals['total_donors']),
        ],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
