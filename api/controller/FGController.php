<?php
require_once __DIR__ . '/../model/FGModel.php';

class FGController
{
    private $fgModel;
    private $db;
    public function __construct()
    {
        $this->db = new DatabaseClass();            // now stored in property
        $this->fgModel = new FGModel($this->db);
    }
    public function getReadyforPullOut()
    {
        $model = $_GET['model'] ?? '';
        $fgData = $this->fgModel->getReadyforPullOut($model);
        echo json_encode($fgData);
    }
    public function getPulledoutHistory()
    {
        $model = $_GET['model'] ?? '';
        $pulledHistory = $this->fgModel->getPulledoutHistory($model);
        echo json_encode($pulledHistory);
    }
    public function PullOut()
    {
        global $input;
        $data = [
            'id' => $input['id'] ?? null,
            'material_no' => $input['material_no'] ?? null,
            'material_description' => $input['material_description'] ?? null,
            'total_quantity' => $input['total_quantity'] ?? null,
            'reference_no' => $input['reference_no'] ?? null,
            'pulled_at' => date('Y-m-d H:i:s'),
            'part_type' => $input['part_type'] ?? null,
            'model' => $input['model'] ?? null
        ];


        try {
            $this->db->beginTransaction();

            $updatedWarehouse = $this->fgModel->markAsPulledFromFG($data['id'], $data['pulled_at']);
            $updatedDelivery  = $this->fgModel->markDeliveryFormAsDone($data['reference_no']);
            $updatedAssembly  = $this->fgModel->markAssemblyListAsDone($data['reference_no']);
            $updatedDeliveryHistory = $this->fgModel->markDeliveryHistoryasDone($data['reference_no']);

            // Decide which inventory update to apply
            $specialModels = ["KOMYO", "APS", "MILLIARD", "VALERIE", "PNR"];
            $modelKey = strtoupper(preg_replace('/[0-9]/', '', $data['model'] ?? ''));

            $updatedInventory = true; // default true so rollback check works
            if (in_array($modelKey, $specialModels, true) || $data['part_type'] === 'stamping') {
                $updateComponentsInventory = $this->fgModel->updateComponentsInventory(
                    $data['material_no'],
                    $data['material_description'],
                    $data['total_quantity']
                );
            } else {
                $updatedInventory = $this->fgModel->updateMaterialInventory(
                    $data['material_no'],
                    $data['material_description'],
                    $data['total_quantity']
                );
                $updateComponentsInventory = true; // skip components in this case
            }

            if ($updatedWarehouse && $updatedAssembly && $updatedInventory && $updatedDelivery && $updatedDeliveryHistory && $updateComponentsInventory) {
                $this->db->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Item marked as PULLED OUT'
                ]);
            } else {
                $this->db->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => '❌ Not all records were updated',
                    'debug' => [
                        'warehouse_updated' => $updatedWarehouse,
                        'assembly_updated' => $updatedAssembly,
                        'inventory_updated' => $updatedInventory,
                        'delivery_updated' => $updatedDelivery,
                        'components_updated' => $updateComponentsInventory
                    ]
                ]);
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => '❌ Error: ' . $e->getMessage()
            ]);
        }
    }

    public function getAllComponents()
    {
        $model = $_GET['model'] ?? '';
        $allComponents = $this->fgModel->getAllComponents($model);
        echo json_encode($allComponents);
    }
}
