<?php
require_once __DIR__ . '/../model/AssemblyModel.php';

class AssemblyController
{
    private $assemblyModel;
    private $db; // Add this

    public function __construct()
    {
        $this->db = new DatabaseClass(); // Store as property
        $this->assemblyModel = new AssemblyModel($this->db);
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
    public function getAllModelData_assigned()
    {
        global $input;
        $full_name = $input['full_name'] ?? null;
        $model = $_GET['model'] ?? null; // ✅ get model from query
        $results = $this->assemblyModel->getAllModelData_assigned($full_name, $model);
        echo json_encode([
            'success' => true,
            'items' => $results
        ]);
    }
    public function getAllAssemblyData()
    {
        $model = $_GET['model'] ?? '';
        $assemblyData = $this->assemblyModel->getAllAssemblyData($model);
        echo json_encode($assemblyData);
    }
    public function getData_toassign()
    {
        $model = $_GET['model'] ?? '';
        $now = new DateTime();
        $cutoff = (clone $now)->modify('-3 days')->format('Y-m-d H:i:s');

        // Fetch data
        $data = $this->assemblyModel->getData_toassign($cutoff, $model);

        $filteredDelivery = [];
        foreach ($data['delivery'] as $row) {
            $createdAt = new DateTime($row['created_at']);
            $createdDate = (clone $createdAt)->setTime(0, 0);
            $unlockTime = (clone $createdDate)->modify('+1 day +6 hours');

            // Include only if the current time is past the unlock time
            if ($createdAt->format('Y-m-d') < $now->format('Y-m-d') || $now >= $unlockTime) {
                $filteredDelivery[] = $row;
            }
        }

        $filteredAssembly = [];
        foreach ($data['assembly'] as $row) {
            $createdAt = new DateTime($row['created_at']);
            $createdDate = (clone $createdAt)->setTime(0, 0);
            $unlockTime = (clone $createdDate)->modify('+1 day +6 hours');

            if ($createdAt->format('Y-m-d') < $now->format('Y-m-d') || $now >= $unlockTime) {
                $filteredAssembly[] = $row;
            }
        }

        echo json_encode([
            "success"  => true,
            "delivery" => $data['delivery'],
            "assembly" => $data['assembly'],
        ]);
    }

    public function getAllData_assigned()
    {
        $model = $_GET['model'] ?? '';
        $cutoff = date('Y-m-d H:i:s', strtotime('-2 days'));

        $data = $this->assemblyModel->getAllData_assigned($cutoff, $model);

        echo json_encode([
            "success"  => true,
            "delivery" => $data["delivery"],
            "assembly" => $data["assembly"]
        ]);
    }
    public function getMaterialComponent()
    {
        global $input;
        $material_no = $input['material_no'];

        $users = $this->assemblyModel->getMaterialComponent($material_no);

        echo json_encode($users);
    }
    public function getSpecificData_assigned()
    {
        global $input;
        $full_name = $input['full_name'] ?? null;
        $model = $_GET['model'] ?? null; // ✅ get model from query
        $results = $this->assemblyModel->getSpecificData_assigned($full_name, $model);
        echo json_encode([
            'success' => true,
            'items' => $results
        ]);
    }
    public function assignOperator()
    {
        global $input;
        $itemId = (int)$input['id'];
        $person = trim($input['person_incharge']);
        $byOrder = (int)$input['by_order'];

        $this->assemblyModel->assignOperator($itemId, $person, $byOrder);

        echo json_encode(['success' => true]);
    }

    public function timeinOperator()
    {
        global $input;
        try {
            $this->db->beginTransaction();


            // Get pending quantity
            $pending_quantity = $this->assemblyModel->getLatestPendingQuantity($input['reference_no'])
                ?: (int)($input['total_qty'] ?? 0);

            // Add computed/default values without redeclaring everything
            $input['pending_quantity'] = $pending_quantity;
            $input['time_in'] = date('Y-m-d H:i:s');
            $input['status']  = 'pending';
            $input['section'] = 'assembly';

            // Insert directly using input
            $this->assemblyModel->insertAssemblyRecord($input);

            $this->db->commit();
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => ' Error: ' . $e->getMessage()];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    public function timeoutOperator()
    {
        global $input;
        try {
            $this->db->beginTransaction();


            // Compute pending quantity and other defaults
            $pending_quantity = $this->assemblyModel->getLatestPendingQuantity($input['reference_no'])
                ?: (int)($input['total_qty'] ?? 0);
            $pending_quantity -= $input['inputQty'] ?? 0;
            $time_out = date('Y-m-d H:i:s');
            $status_assembly = ($pending_quantity > 0) ? 'pending' : 'done';
            $section_assembly = ($pending_quantity > 0) ? 'assembly' : 'qc';

            // Prepare arrays for model methods
            $assemblyData = $input;
            $assemblyData['pending_quantity'] = $pending_quantity;
            $assemblyData['time_out'] = $time_out;
            $assemblyData['assembly_section'] = $input['assembly_section'] ?? null;
            $assemblyData['assembly_process'] = $input['assembly_process'] ?? '';
            $assemblyData['assembly_section_no'] = $input['assembly_section_no'] ?? null;
            $assemblyData['status'] = $status_assembly;
            $assemblyData['section'] = $section_assembly;
            $assemblyData['cycle_time'] = $input['cycle_time'] ?? null;

            $assemblyConditionsData = $input;
            $assemblyConditionsData['pending_quantity'] = $pending_quantity;
            $assemblyConditionsData['time_out'] = $time_out;
            $assemblyConditionsData['status'] = $status_assembly;
            $assemblyConditionsData['section'] = $section_assembly;

            // Update timeout
            $this->assemblyModel->updateAssemblyListTimeout($assemblyConditionsData);

            // Update delivery form pending
            $currentPending = $this->assemblyModel->getPendingAssembly($input['id']);
            $basePending = isset($currentPending['assembly_pending']) && $currentPending['assembly_pending'] !== null
                ? (int)$currentPending['assembly_pending']
                : (int)$currentPending['total_quantity'];
            $remainingPending = max(0, $basePending - (int)($input['inputQty'] ?? 0));
            $this->assemblyModel->UpdateDeliveryFormPending($input['id'], $remainingPending);

            // Check duplication & move to QC
            if (!empty($input['reference_no'])) {
                $total_process = $input['total_process'] ?? null;
                $process_no = $input['process_no'] ?? null;
                $done_quantity = $input['done_quantity'] ?? 0;
                $manpower = $input['manpower'] ?? null;
                $material_no = $input['material_no'] ?? null;
                $material_description = $input['material_description'] ?? null;
                $lot_no = $input['lot_no'] ?? null;
                $sub_component = $input['sub_component'] ?? null;
                $assembly_section = $input['assembly_section'] ?? null;
                $reference_no = $input['reference_no'];

                if (($total_process === $process_no) && $done_quantity > 0) {
                    $result = $this->assemblyModel->checkDuplicate(
                        $material_no,
                        $material_description,
                        $lot_no,
                        $manpower,
                        $input['assembly_process'] ?? '',
                        $sub_component,
                        $reference_no,
                        $assembly_section,
                        $process_no
                    );

                    $totalDone = 0;
                    $totalQuantity = 0;
                    $shouldDuplicate = false;
                    $allDone = false;
                    $manpowerMatched = count($result) === (int)$manpower;

                    if (!empty($result)) {
                        $completedCount = 0;
                        foreach ($result as $row) {
                            $done = (int)$row['total_done'];
                            $required = (int)$row['total_required'];
                            $ref = $row['reference_no'];
                            $totalDone += $done;
                            $totalQuantity = max($totalQuantity, $required);

                            if ($ref === $reference_no && $done < $required) {
                                $shouldDuplicate = true;
                            }
                            if ($done >= $required) $completedCount++;
                        }
                        if ($manpowerMatched && $completedCount === count($result)) {
                            $allDone = true;
                        }
                    } else {
                        $shouldDuplicate = true;
                    }

                    if ($shouldDuplicate && $done_quantity > 0) {
                        $this->assemblyModel->duplicateDeliveryFormWithPendingUpdate(
                            $input['id'],
                            $time_out,
                            $remainingPending,
                            $assembly_section,
                            $input['assembly_section_no'] ?? null,
                            $reference_no,
                            $process_no
                        );
                    } else if ($manpowerMatched && $allDone) {
                        $this->assemblyModel->deductComponentInventory(
                            $material_no,
                            $reference_no,
                            $totalQuantity,
                            $time_out,
                            $manpower,
                            $input['model'] ?? null
                        );

                        $qcPayload = [
                            'model' => $input['model'] ?? null,
                            'variant' => $input['variant'] ?? null,
                            'shift' => $input['shift'] ?? null,
                            'lot_no' => $lot_no,
                            'date_needed' => $input['date_needed'] ?? null,
                            'reference_no' => $reference_no,
                            'material_no' => $material_no,
                            'material_description' => $material_description,
                            'total_quantity' => $input['total_qty'] ?? 0,
                            'done_quantity' => $totalQuantity,
                            'created_at' => date('Y-m-d H:i:s', strtotime($time_out . ' -1 day')),
                            'assembly_section' => $assembly_section,
                            'cycle_time' => $input['cycle_time'] ?? null,
                            'pi_kbn_quantity' => $input['pi_kbn_quantity'] ?? null,
                            'pi_kbn_pieces' => $input['pi_kbn_pieces'] ?? null,
                            'fuel_type' => $input['fuel_type'] ?? null,
                            'customer_id' => $input['customer_id'] ?? null
                        ];

                        $this->assemblyModel->moveToQCList($qcPayload);
                    }
                } else {
                    $result = $this->assemblyModel->getTotalDoneAndRequired($reference_no, $assembly_section, $process_no);
                    if ($result) {
                        $totalDone = (int)$result['total_done'];
                        $totalQuantity = (int)$result['total_required'];
                        if ($totalDone !== $totalQuantity) {
                            $this->assemblyModel->duplicateDeliveryFormWithPendingUpdate(
                                $input['id'],
                                $time_out,
                                $remainingPending,
                                $assembly_section,
                                $input['assembly_section_no'] ?? null,
                                $reference_no,
                                $process_no
                            );
                        } else {
                            $this->assemblyModel->updatePendingQuantity($reference_no, $process_no);
                        }
                    }
                }
            }

            $this->db->commit();
            echo json_encode(['success' => true, 'message' => 'Time out successfully']);
        } catch (\PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'DB Error: ' . $e->getMessage()];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    public function reset_timein()
    {
        global $input;

        $id               = $input['id'] ?? '';
        $role             = strtolower(trim($input['role'] ?? ''));
        $section          = strtolower(trim($input['section'] ?? ''));
        $specific_section = strtolower(trim($input['specific_section'] ?? ''));
        $assemblySection  = strtolower(trim($input['assembly_section'] ?? ''));

        $supervisorId     = trim($input['supervisor_id'] ?? '');
        $supervisorName   = trim($input['supervisor_name'] ?? '');

        try {
            $authPassed = false;

            // ✅ 1. Check supervisor exists
            $user = $this->assemblyModel->getUserByIdAndName($supervisorId, $supervisorName);
            if (!$user) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'User not recognized.'
                ]);
                exit;
            }

            // Decode stored section arrays
            $userSections = json_decode($user['section'] ?? '[]', true);
            $userSpecificSections = json_decode($user['specific_section'] ?? '[]', true);

            // Normalize to lowercase
            $userSections = array_map('strtolower', (array)$userSections);
            $userSpecificSections = array_map('strtolower', (array)$userSpecificSections);

            $dbRole = strtolower(trim($user['role'] ?? ''));

            // ✅ 2. Role + section validation
            if ($dbRole === 'administrator') {
                $authPassed = true;
            } elseif (in_array($dbRole, ['supervisor', 'line leader'], true)) {
                // Must belong to the same section and specific section
                if (in_array($section, $userSections, true) && in_array($specific_section, $userSpecificSections, true)) {
                    $authPassed = true;
                }
            }

            if (!$authPassed) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized reset attempt — role or section mismatch.'
                ]);
                exit;
            }

            // ✅ 3. Proceed to reset based on assembly section
            if ($assemblySection === 'finishing') {
                $success = $this->assemblyModel->resetFinishing($id);
            } else {
                $success = $this->assemblyModel->resetAssembly($id);
            }

            echo json_encode([
                'success' => $success,
                'message' => $success
                    ? "Inventory reset for ID $id"
                    : 'Reset failed.'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'DB Error: ' . $e->getMessage()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
