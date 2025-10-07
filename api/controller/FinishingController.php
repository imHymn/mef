<?php
require_once __DIR__ . '/../model/FinishingModel.php';

class FinishingController
{
    private $finishingModel;
    private $db;
    public function __construct()
    {
        $db = new DatabaseClass();
        $this->finishingModel = new FinishingModel($db);
    }
    public function getSpecificData_assigned()
    {
        global $input;
        $full_name = $input['full_name'] ?? null;
        $model = $_GET['model'] ?? null; // ✅ get model from query
        $results = $this->finishingModel->getSpecificData_assigned($full_name, $model);
        echo json_encode([
            'success' => true,
            'items' => $results
        ]);
    }
    public function getData_toassign()
    {
        $model = $_GET['model'] ?? null; // ✅ get model from query
        $results = $this->finishingModel->getData_toassign($model);
        echo json_encode([
            'success' => true,
            'items' => $results
        ]);
    }
    public function getAllData_assigned()
    {
        global $input;
        $model = $_GET['model'] ?? null; // ✅ get model from query
        $results = $this->finishingModel->getAllData_assigned($model);
        echo json_encode([
            'success' => true,
            'items' => $results
        ]);
    }
    public function getAllModelData_assigned()
    {
        global $input;
        $full_name = $input['full_name'] ?? null;
        $results = $this->finishingModel->getAllModelData_assigned($full_name);
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

        $this->finishingModel->assignOperator($itemId, $person, $byOrder);
        echo json_encode(['success' => true]);
    }
    public function timeinOperator()
    {
        global $input;
        $id = $input['id'] ?? null;
        $full_name = $input['full_name'] ?? null;
        $time_in = date('Y-m-d H:i:s');


        $result = $this->finishingModel->timeinOperator($id, $full_name, $time_in);
        if ($result !== true) {
            throw new Exception($result);
        }

        echo json_encode(['success' => true, 'message' => 'Update successful.']);
    }
    public function timeoutOperator()
    {
        global $input;
        try {

            // Always compute derived fields directly inside input
            $input['time_out'] = date('Y-m-d H:i:s');

            $quantity = (int)($input['quantity'] ?? 0);
            $inputQty = (int)($input['inputQty'] ?? 0);
            $assembly_pending_quantity = $input['assembly_pending_quantity'] ?? null;

            if ($assembly_pending_quantity === null) {
                $input['assembly_pending_quantity'] = $quantity - $inputQty;
            } else {
                $input['assembly_pending_quantity'] = $assembly_pending_quantity - $inputQty;
            }

            // ✅ Pass raw $input directly to model
            $updated = $this->finishingModel->updateReworkFinishingTimeout($input);

            $insertedCount = 0;

            if (!empty($input['reference_no'])) {
                $result = $this->finishingModel->getGroupedFinishingByReference(
                    $input['reference_no'],
                    $input['rework_no'] ?? null
                );

                if ($result) {
                    $total_rework   = (int)$result['total_rework'];
                    $total_replace  = (int)$result['total_replace'];
                    $total          = $total_rework + $total_replace;
                    $total_quantity = (int)$result['total_quantity'];
                    $material_no    = $result['material_no'];

                    if ($total === $total_quantity) {
                        try {
                            $excludedModels = ['MILLIARD', 'APS', 'KOMYO'];
                            $model = strtoupper($input['model'] ?? '');

                            // ✅ Update component inventory if applicable
                            if ($material_no && $total_replace > 0 && !in_array($model, $excludedModels, true)) {
                                $this->finishingModel->updateComponentInventoryAfterReplace(
                                    $material_no,
                                    $total_replace,
                                    $input['time_out'],
                                    $input['reference_no']
                                );
                            }

                            // ✅ Mark done & insert QC
                            $this->finishingModel->markReworkFinishingAsDone($input['reference_no']);
                            $this->finishingModel->insertReworkQC(
                                $input['reference_no'],
                                $result,
                                $total,
                                $input['time_out'],
                                $input['assembly_section'] ?? null,
                                $input['cycle_time'] ?? null
                            );
                        } catch (Exception $e) {
                            $this->db->rollback();
                            error_log("Rework finalization failed: " . $e->getMessage());

                            echo json_encode([
                                'success' => false,
                                'message' => 'Rework finalization failed.',
                                'error' => $e->getMessage()
                            ]);
                            exit;
                        }
                    } else {
                        $insertedCount = $this->finishingModel->duplicateReworkFinishing(
                            $input['id'],
                            $input['rework_no'] ?? null,
                            $input['replace'] ?? null,
                            $input['rework'] ?? null,
                            $input['inputQty'] ?? 0,
                            $input['time_out'],
                            $input['cycle_time'] ?? null
                        );
                    }
                }
            }


            echo json_encode([
                'success' => true,
                'message' => 'Update and optional duplication completed successfully.',
                'insertedCount' => $insertedCount,
                'assembly' => $input['assembly_pending_quantity'],
                'rework' => $input['rework'] ?? null,
                'replace' => $input['replace'] ?? null,
                'result' => $result ?? null
            ]);
        } catch (Exception $e) {

            error_log("Error during duplication: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Operation failed: ' . $e->getMessage()
            ]);
        }
    }
}
