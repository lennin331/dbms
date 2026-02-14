<?php
// modules/transactions/get_prescription_items.php
require_once '../../config/database.php';

$id = $_GET['id'] ?? 0;

// Get prescription items
$items = $db->query("
    SELECT pd.*, d.drug_name 
    FROM prescription_details pd
    JOIN drugs d ON pd.drug_id = d.drug_id
    WHERE pd.prescription_id = $id
");

$response = ['items' => []];

while($item = $items->fetch_assoc()) {
    // Get available batches for this drug
    $batches = $db->query("
        SELECT inventory_id, batch_number, quantity, expiry_date 
        FROM inventory 
        WHERE drug_id = {$item['drug_id']} 
        AND quantity > 0 
        AND expiry_date > CURDATE()
        ORDER BY expiry_date ASC
    ");
    
    $batch_list = [];
    while($batch = $batches->fetch_assoc()) {
        $batch_list[] = $batch;
    }
    
    $item['batches'] = $batch_list;
    $response['items'][] = $item;
}

header('Content-Type: application/json');
echo json_encode($response);
?>