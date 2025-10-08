<?php
require_once __DIR__ . '/../model/StampingModel.php';

class StampingController
{
    private $stampingModel;
    private $db;
    public function __construct()
    {
        $this->db = new DatabaseClass();            // now stored in property
        $this->stampingModel = new StampingModel($this->db);
    }
    public function normalizeArray($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = [$value];
            }
        }
        return array_map(function ($v) {
            return strtolower(trim($v));
        }, (array)$value);
    }
    public function getLatestReferenceNo()
    {
        $referenceNo = $this->stampingModel->getLatestReferenceNo();
        echo json_encode($referenceNo);
    }
    public function reset_timein()
    {
        global $input;

        $id              = $input['id'] ?? '';
        $role            = strtolower(trim($input['role'] ?? ''));
        $section         = strtolower(trim($input['section'] ?? ''));
        $assemblySection = strtolower(trim($input['assembly_section'] ?? ''));
        $production      = $this->normalizeArray($input['section'] ?? []);
        $productionLoc   = $this->normalizeArray($input['specific_section'] ?? []);
        $supervisorId    = trim($input['supervisor_id'] ?? '');
        $supervisorName  = trim($input['supervisor_name'] ?? '');

        try {

            $authPassed = false;

            // ✅ Check supervisor actually exists in users table
            $user = $this->stampingModel->getUserByIdAndName($supervisorId, $supervisorName);
            if (!$user) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Supervisor not recognized']);
                exit;
            }

            if ($role === 'administrator') {
                $authPassed = true;
            } elseif ($role === 'supervisor' || $role === 'line leader') {
                $authPassed = true;
            }

            if (!$authPassed) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized reset attempt']);
                exit;
            }


            $success = $this->stampingModel->resetStamping($id); // call new function


            echo json_encode([
                'success' => $success,
                'message' => $success
                    ? "Inventory reset for ID $id"
                    : 'Reset failed'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    public function getAllModelData_assigned()
    {
        global $input;
        $full_name = $input['full_name'] ?? null;
        $model = $_GET['model'] ?? null; // ✅ get model from query

        $results = $this->stampingModel->getAllModelData_assigned($full_name, $model);
        echo json_encode(['success' => true, 'items' => $results]);
    }
    public function getAllData_assigned()
    {
        $model = $_GET['model'] ?? null;
        $data = $this->stampingModel->getAllData_assigned($model);
        echo json_encode($data);
    }
    public function getComponentInventory()
    {
        $model = $_GET['model'] ?? '';
        $components = $this->stampingModel->getComponentInventory($model);

        echo json_encode($components);
    }
    public function getAllStampingData()
    {
        $model = $_GET['model'] ?? '';
        $stampingData = $this->stampingModel->getAllStampingData($model);
        echo json_encode($stampingData);
    }
    public function getSpecificData_assigned()
    {
        global $input;
        $full_name = $input['full_name'] ?? null;
        $model = $_GET['model'] ?? null; // ✅ get model from query

        $results = $this->stampingModel->getSpecificData_assigned($full_name, $model);
        echo json_encode(['success' => true, 'items' => $results]);
    }

    public function assignOperator()
    {
        global $input;
        $itemId = (int)$input['id'];
        $person = trim($input['person_incharge']);
        $byOrder = (int)$input['by_order'];

        $this->stampingModel->assignOperator($itemId, $person, $byOrder);

        echo json_encode(['success' => true]);
    }

    public function getData_toassign()
    {
        $model = $_GET['model'] ?? '';

        $data = $this->stampingModel->getData_toassign($model);
        echo json_encode($data);
    }
    public function getMachines()
    {
        $machines = $this->stampingModel->getMachines();
        echo json_encode($machines);
    }
    public function getComponentStatus()
    {

        global $input;
        $data = [
            'material_no' => $input['material_no'] ?? null,
            'components_name' => $input['components_name'] ?? null,
            'batch' => $input['batch'] ?? null
        ];

        $stages = $this->stampingModel->getComponentStatus($data);

        echo json_encode([
            'status' => 'success',
            'stages' => $stages
        ]);
    }
    public function timeinOperator()
    {
        global $input;
        $result = $this->stampingModel->timeinOperator($input);
        echo json_encode([
            'status' => 'success',
            'message' => 'Stage updated successfully',
            'result' => $result
        ]);
    }
    public function timeoutOperator()
    {
        global $input;
        try {

            $this->db->beginTransaction();

            $this->stampingModel->updateStampingTimeout($input);
            $model = $input['model'] ?? null;
            $cycle_time = $input['cycle_time'] ?? null;
            $reference_no = $input['reference_no'] ?? null;
            $responseData = [];

            if (!empty($input['manpower']) && !empty($input['pair'])) {
                $rows = $this->stampingModel->getStampingByReferenceStagePair(
                    $input['reference_no'],
                    $input['stage'],
                    $input['pair'],
                    $input['duplicated']
                );

                if (count($rows) !== 2) {
                    throw new Exception("Expected 2 stamping records for pair, got " . count($rows));
                }

                $row = $rows[0];
                $rowIds = array_column($rows, 'id');
            } else {
                $row = $this->stampingModel->getStampingById($input['id']);
                if (!$row) {
                    throw new Exception("No stamping record found for ID: {$input['id']}");
                }
            }

            $stats = !empty($input['manpower']) && !empty($input['pair'])
                ? $this->stampingModel->getQuantityStats($row['reference_no'], $row['stage'], $row['pair'])
                : $this->stampingModel->getQuantityStats($row['reference_no']);

            $totalDone = (int)($stats['total_quantity_done'] ?? 0);
            $maxTotal = (int)($stats['max_total_quantity'] ?? 0);
            if (!empty($input['manpower']) && !empty($input['pair'])) $maxTotal *= 2;

            if ($totalDone < $maxTotal) {
                if (!empty($input['manpower']) && !empty($input['pair'])) {
                    $pairRows = $this->stampingModel->getPairRows($row['reference_no'], $row['stage'], $row['pair'], $row['duplicated']);
                    foreach ($pairRows as $r) {
                        $this->stampingModel->duplicateIfNotDone($r, $input['inputQuantity']);
                    }
                } else {
                    $this->stampingModel->duplicateIfNotDone($row, $input['inputQuantity']);
                }

                $responseData = [
                    'action' => 'duplicated',
                    'row_id' => $row['id'],
                    'inputQuantity' => $input['inputQuantity'],
                    'totalDone' => $totalDone,
                    'maxTotal' => $maxTotal
                ];
            } else {
                $allDone = $this->stampingModel->areAllStagesDone(
                    $row['material_no'],
                    $row['components_name'],
                    (int)$row['process_quantity'],
                    (int)$row['total_quantity'],
                    (int)$row['batch'],
                    $row['stage'],
                );

                if ($allDone) {
                    $rowsToProcess = !empty($input['manpower']) && !empty($input['pair']) ? $rows : [$row];

                    foreach ($rowsToProcess as $r) {
                        $dateNeeded = $this->stampingModel->getDateNeededByReference($input['customer_id']);

                        $qcPayload = [
                            'model' => $model,
                            'variant' => $variant ?? null,
                            'shift' => $null ?? null,
                            'lot_no' => $null ?? null,
                            'date_needed' => $dateNeeded,
                            'reference_no' => $input['customer_id'],
                            'customer_id' => $input['customer_id'],
                            'material_no' =>  $r['material_no'],
                            'material_description' =>  $r['components_name'],
                            'total_quantity' => $r['total_quantity'],
                            'done_quantity' => $r['total_quantity'],
                            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                            'assembly_section' => 'STAMPING',
                            'cycle_time' => $cycle_time,
                            'pi_kbn_quantity' => null,
                            'pi_kbn_pieces' => null,
                            'fuel_type' => $null ?? null
                        ];

                        $this->stampingModel->moveToQCList($qcPayload);
                        $this->stampingModel->updateIssueRawMaterial($r['material_no'], $r['components_name'], $r['total_quantity']);
                        $this->stampingModel->updateComponentsRMStock($r['material_no'], $r['components_name'], $r['total_quantity']);
                    }

                    $responseData = [
                        'action' => 'moved_to_qc',
                        'rows' => array_map(fn($r) => [
                            'material_no' => $r['material_no'],
                            'material_description' => $r['components_name'],
                            'total_quantity' => $r['total_quantity'],
                            'date_needed' => $dateNeeded
                        ], $rowsToProcess)
                    ];
                }
            }

            $this->db->commit();

            echo json_encode([
                'status' => 'success',
                'message' => 'Stamping timeout processed',
                'data' => $responseData
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }



    // FOR MILLIARD APS AND KOMYO MODEL CONDITION
    // $warehouseData = [
    //     'reference_no'         => $input['customer_id'],
    //     'material_no'          => $row['material_no'],
    //     'material_description' => $row['components_name'],
    //     'model'                => $input['model'],
    //     'total_good'           => $row['total_quantity'],
    //     'total_quantity'       => $row['total_quantity'],
    //     'shift'                => $input['shift'] ?? null,
    //     'lot_no'               => $input['lot_no'] ?? null,
    //     'date_needed'          => $dateNeeded,
    //     'created_at'           => date('Y-m-d H:i:s'),
    //     'new_section'          => 'warehouse',
    //     'new_status'           => 'done',
    // ];

    // if (in_array(strtoupper($input['model']), ['MILLIARD', 'APS', 'KOMYO'])) {
    //     $model->moveToFGWarehouse($warehouseData);
    // }
}
