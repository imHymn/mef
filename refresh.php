<?php
// Show errors (for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


require_once __DIR__ . './../mes/api/_database/db_connection.php';

$db = new DatabaseClass();
try {

    $sql1 = "UPDATE components_inventory SET actual_inventory = 300, rm_stocks = 0";
    $db->Update($sql1);

    $sql2 = "UPDATE material_inventory SET quantity = 200";
    $db->Update($sql2);

    $sql3 = "UPDATE material_inventory 
                 SET quantity = 10000 
                 WHERE material_description IN ('CLIP 25', 'CLIP 60')";
    $db->Update($sql3);

    $sql4 = "
        UPDATE components_inventory
        SET 
            actual_inventory   = 10000,
            maximum_inventory  = 5400,
            normal             = 4320,
            reorder            = 3240,
            minimum            = 2160,
            critical           = 1080
        WHERE components_name IN ('CLIP 25', 'CLIP 60')
    ";
    $db->Update($sql4);
    // 🔹 Reset inventories for specific models (MILLIARD, APS, KOMYO)
    $sql5 = "UPDATE components_inventory 
                 SET actual_inventory = 0 
                 WHERE model IN ('MILLIARD', 'APS', 'KOMYO')";
    $db->Update($sql5);

    $sql6 = "UPDATE material_inventory 
                 SET quantity = 0 
                 WHERE model IN ('MILLIARD', 'APS', 'KOMYO')";
    $db->Update($sql6);

    $sql7 = "
       UPDATE `components_inventory` SET maximum_inventory=450*usage_type,normal=360*usage_type,reorder=270*usage_type,minimum=180*usage_type,critical=90*usage_type;
 
    ";
    $db->Update($sql7);
    // Truncate tables
    $tablesToTruncate = [
        'delivery_form',
        'delivery_history',
        'assembly_list',
        'customer_form',
        'issued_rawmaterials',
        'qc_list',
        'stamping',
        'rm_warehouse'
    ];
    foreach ($tablesToTruncate as $table) {
        $db->Update("TRUNCATE TABLE `$table`");
    }

    echo json_encode(['success' => true, 'message' => 'Tables updated and truncated successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
