<?php
require_once __DIR__ . '/../model/QCModel.php';

class QCController
{
    private $qcModel;
    private $db;

    public function __construct()
    {
        $this->db = new DatabaseClass(); // assign to property
        $this->qcModel = new QCModel($this->db);
    }

    public function getTodoList()
    {
        $now = new DateTime();
        $cutoff = (clone $now)->modify('-3 days')->format('Y-m-d H:i:s');
        $modelName = $_GET['model'] ?? '';

        $results = $this->qcModel->getTodoList($cutoff, $modelName);
        $filtered = [];

        foreach ($results as $row) {
            $createdAt = new DateTime($row['created_at']);
            $createdDate = (clone $createdAt)->setTime(0, 0);
            $unlockTime = (clone $createdDate)->modify('+1 day +6 hours');

            if ($createdAt->format('Y-m-d') < $now->format('Y-m-d') || $now >= $unlockTime) {
                $filtered[] = $row;
            }
        }
        echo json_encode($results);
    }
    public function timeinOperator()
    {
        global $input;
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? null;
        $time_in = date('Y-m-d H:i:s');

        $this->db->beginTransaction();

        $this->qcModel->timeinOperator($id, $name, $time_in);

        $this->db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Update and insert successful. Inventory updated.',
        ]);
    }
    public function timeoutOperator()
    {
        global $input;

        // Ensure pending_quantity is updated without redeclaring
        if (isset($input['pending_quantity'])) {
            $input['pending_quantity'] -= $input['quantity'] ?? 0;
        } elseif (!isset($input['pending_quantity'])) {
            $input['pending_quantity'] = ($input['total_quantity'] ?? 0) - ($input['quantity'] ?? 0);
        }


        $input['no_good'] = $input['nogood'] ?? 0;
        $input['time_out'] = $input['time_out'] ?? date('Y-m-d H:i:s');

        $process = strtolower($input['process'] ?? '');

        try {
            $this->db->beginTransaction();

            // Update QC list with current input
            $this->qcModel->updateQCListTimeout($input);

            $newStatus = '';
            $newSection = '';

            if (!empty($input['reference_no'])) {

                $result = $this->qcModel->getQCTotalSummary($input['reference_no']);

                if (!$result) {
                    echo json_encode(['success' => false, 'message' => 'No data found for that reference number.']);
                    $this->db->rollBack();
                    return;
                }

                $totalDone = (int)$result['total_done'];
                $totalPending = (int)$result['total_pending'];
                $totalQuantity = (int)$result['total_required'];
                $total_good = (int)$result['total_good'];
                $total_no_good = (int)$result['total_no_good'];
                $total_rework = (int)$result['total_rework'];
                $total_replace = (int)$result['total_replace'];

                // Move to rework if applicable
                if ($totalDone === $totalQuantity) {
                    if (($total_no_good > 0 && ($process === null || $process === '')) || ($total_no_good > 0 && $process === 'stamping')) {
                        $input['total_replace'] = $total_replace;
                        $input['total_rework'] = $total_rework;
                        $input['total_no_good'] = $total_no_good;
                        $this->qcModel->insertReworkAssembly($input);
                        $newStatus = 'pending';
                        $newSection = 'rework';
                    }

                    // Move to FG warehouse
                    $warehouseData = [
                        'customer_id' => $input['customer_id'] ?? null,
                        'reference_no' => $input['reference_no'],
                        'material_no' => $input['material_no'],
                        'material_description' => $input['material_description'],
                        'model' => $input['model'],
                        'total_good' => $total_good,
                        'total_quantity' => $totalQuantity,
                        'shift' => $input['shift'] ?? null,
                        'lot_no' => $input['lot_no'] ?? null,
                        'date_needed' => $input['date_needed'] ?? null,
                        'created_at' => $input['time_out'] ?? date('Y-m-d H:i:s'),
                        'new_section' => 'warehouse',
                        'new_status' => 'done',
                        'part_type' => $input['part_type'] ?? null,
                        'id' => $input['id'] ?? null,
                    ];
                    $this->qcModel->moveToFGWarehouse($warehouseData);

                    if (($input['no_good'] ?? 0) > 0) {
                        $newStatus = 'pending';
                        $newSection = 'rework';
                    } else {
                        $newStatus = 'pending';
                        $newSection = 'warehouse';
                    }
                } else {
                    if (($input['pending_quantity'] ?? 0) > 0) {
                        $this->qcModel->duplicatePendingQCRow($input['id'], $input['pending_quantity'], $input['time_out'], $input['process']);
                    }
                }

                $this->db->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Update and insert successful. Inventory updated.',

                ]);
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    public function getRework()
    {
        $model = $_GET['model'] ?? '';
        $reworkData = $this->qcModel->getRework($model);
        // echo "Got result\n";
        echo json_encode($reworkData);
    }
    public function timein_reworkOperator()
    {
        global $input;

        $id = $input['id'] ?? null;
        $full_name = $input['full_name'] ?? null;
        $time_in = date('Y-m-d H:i:s');
        $this->db->beginTransaction();

        $updated = $this->qcModel->timein_reworkOperator((int)$id, $full_name, $time_in);

        $this->db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Update successful.',
        ]);
    }
    public function timeout_reworkOperator()
    {
        global $input;
        $id = $input['id'] ?? null;
        $full_name = $input['full_name'] ?? null;
        $time_out = date('Y-m-d H:i:s');
        $inputQty = $input['inputQty'] ?? null;
        $no_good = $input['no_good'] ?? 0;
        $good = $input['good'] ?? 0;
        $reference_no = $input['reference_no'] ?? null;
        $quantity = $input['quantity'] ?? null;
        $qc_pending_quantity = $input['qc_pending_quantity'] ?? null;
        $rework_no = $input['rework_no'] ?? null;
        $process = $input['process'] ?? null;
        $assembly_section = $input['assembly_section'] ?? null;
        $cycle_time = $input['cycle_time'] ?? null;

        if (!$id || !$full_name || !$reference_no) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required data.']);
            exit;
        }

        $qc_pending_quantity = $qc_pending_quantity === null
            ? $quantity - $inputQty
            : $qc_pending_quantity - $inputQty;

        try {
            $this->db->beginTransaction();

            // 1. Update rework_qc timeout
            $this->qcModel->updateReworkQcTimeout([
                ':full_name' => $full_name,
                ':id' => $id,
                ':time_out' => $time_out,
                ':no_good' => $no_good,
                ':good' => $good,
                ':qc_pending_quantity' => $qc_pending_quantity
            ]);

            // 2. Get QC summary
            $summary = $this->qcModel->getQcSummaryByReference($reference_no, $rework_no);

            if (
                !$summary ||
                !isset($summary['total_good'], $summary['total_noGood'], $summary['total_quantity'])
            ) {
                throw new Exception("Invalid or incomplete summary data.");
            }

            $total_good = (int)$summary['total_good'];
            $total_noGood = (int)$summary['total_noGood'];
            $total_quantity = (int)$summary['total_quantity'];
            $total = $total_good + $total_noGood;
            $total_replace = (int)($summary['total_replace'] ?? 0);
            $total_rework = (int)($summary['total_rework'] ?? 0);
            // Prepare $data for rework insertion
            $data = [
                'id' => $id,
                'reference_no' => $reference_no,
                'model' => $summary['model'] ?? '',
                'material_no' => $summary['material_no'] ?? '',
                'material_description' => $summary['material_description'] ?? '',
                'shift' => $summary['shift'] ?? '',
                'lot_no' => $summary['lot_no'] ?? '',
                'date_needed' => $summary['date_needed'] ?? '',
                'time_out' => $time_out,
                'total_good' => $total_good,
                'total_replace' => $total_replace,
                'total_rework' => $total_rework,
                'total_no_good' => $total_noGood,
                'total_quantity' => $total_quantity,
                'assembly_section' => $assembly_section,
                'rework_no' => $rework_no,
                'process' => $process,
                'cycle_time' => $cycle_time
            ];


            if ($total === $total_quantity) {
                if ($total_noGood > 0 && ($process === null || $process === '')) {
                    $this->qcModel->insertReworkAssembly($data);
                } else  if ($total_noGood > 0 && $process === 'stamping') {
                    $this->qcModel->insertReworkStamping($data);
                }
                $this->qcModel->markQcReferenceDone($reference_no);
                $this->qcModel->updateDeliveryFormSection($reference_no);
                $this->qcModel->updateFgWarehouseQuantity($reference_no, $total_good);
            } else {
                $row = $this->qcModel->getReworkQcById($id);
                if ($row) {
                    $this->qcModel->duplicateReworkQc($row, $time_out, $data['rework_no'], $cycle_time);
                }
            }

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => "QC Timeout and rework handling completed.",
                'total' => $total,
                'total_quantity' => $total_quantity,

                'summary' => $summary

            ]);
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("QC Timeout error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => [$total, $total_quantity],]);
        }
    }
    public function getAllQCData()
    {
        $model = $_GET['model'] ?? '';
        $data = $this->qcModel->getAllQCData($model);

        echo json_encode([
            'success' => true,
            'qc' => $data['qc'],
            'rework' => $data['rework']
        ]);
    }
}
