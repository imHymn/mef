<?php
require_once __DIR__ . '/../model/PlannerModel.php';

class PlannerController
{
    private $plannerModel;
    private $db;

    public function __construct()
    {
        $this->db = new DatabaseClass();
        $this->plannerModel = new PlannerModel($this->db);
    }
    public function getPreviousLot()
    {
        $model = $_GET['model'];
        $sql = $this->plannerModel->getPreviousLot($model);

        echo json_encode($sql);
    }
    public function getMaterial()
    {
        $model = trim($_GET['model']);
        $customerName = trim($_GET['customer_name']);

        $components = $this->plannerModel->getMaterial($model, $customerName);

        echo json_encode($components);
    }
    public function deleteMultipleForm()
    {

        try {
            // Extract and validate input
            $column = $input['column'] ?? '';
            $value = $input['value'] ?? '';

            $this->db->beginTransaction();


            $this->plannerModel->deleteMultipleForm($column, $value);

            $this->db->commit();
            echo json_encode([
                'success' => true,
                'message' => "Deleted entries where `$column` = '$value'."
            ]);
            exit;
        } catch (Exception $e) {

            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while deleting entries.',
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
    public function submitForm()
    {
        global $input;
        $currentDateTime = date('Y-m-d H:i:s');
        $model = $input[0]['model'] ?? null;
        $today = date('Ymd');

        try {
            $this->db->beginTransaction();
            $response = $this->plannerModel->submitForm($input, $today, $currentDateTime);
            $this->db->commit();
            echo json_encode($response);

            exit;
        } catch (Exception $e) {
            $this->db->rollback();
            echo json_encode([
                'status' => 'error',
                'message' => 'An error occurred while processing the delivery form.',
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
    public function getComponents()
    {
        $model = trim($_GET['model']);
        $customerName = trim($_GET['customer_name']);

        $components = $this->plannerModel->getComponents($model, $customerName);

        echo json_encode($components);
    }
    public function submitForm_allCustomer()
    {
        global $input;
        $currentDateTime = date('Y-m-d H:i:s');
        $today = date('Ymd');


        try {
            $componentInventory = $this->plannerModel->recheckComponentInventory($input);

            $results = [];
            $lastNumber = (int)substr($this->plannerModel->selectCustomerReferenceNo($today), -4);

            foreach ($input as $index => $item) {
                $available = $componentInventory[$index]['available_quantity'] ?? 0;
                $requested = $item['quantity'];

                // Always generate a reference no (no more sufficient/insufficient condition)
                $lastNumber++;
                $refNo = $today . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

                $input[$index]['reference_no'] = $refNo;
                // $input[$index]['available_quantity'] = $available;

                $results[] = [
                    'components_name'     => $item['components_name'],
                    'quantity_requested'  => $requested,
                    'available_quantity'  => $available,
                    'reference_no'        => $refNo
                ];
            }

            // Always process regardless of stock
            $processResult = $this->plannerModel->processCustomerForm($input, $currentDateTime);
            $insertedCount = $processResult['inserted'] ?? 0;

            if ($insertedCount > 0) {
                echo json_encode([
                    'status'   => 'success',
                    'message'  => 'Items processed (stock check bypassed).',
                    'data'     => $results,
                    'inserted' => $insertedCount
                ]);
            } else {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Items were processed but no records were inserted. Please verify your input.',
                    'data'    => $results
                ]);
            }
        } catch (Throwable $e) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'An unexpected error occurred.',
                'error'   => $e->getMessage()
            ]);
        }
    }
    public function submitForm_specificCustomer()
    {
        global $input;
        $currentDateTime = date('Y-m-d H:i:s');
        $today = date('Ymd');


        // recheck inventory (optional, just like old function)
        $componentInventory = $this->plannerModel->recheckComponentInventory($input);

        // get last reference number for today
        $lastNumber = (int)substr($this->plannerModel->selectCustomerReferenceNo($today), -4);

        $results = [];
        $seenMaterials = [];       // track processed material_no for processCustomerForm
        $uniqueInputForProcess = []; // only first occurrence per material_no

        foreach ($input as $index => &$item) {   // use reference so we can inject into $input
            $materialNo     = $item['material_no'] ?? null;
            $componentName  = $item['components_name'] ?? null;
            $quantity       = (int) ($item['quantity'] ?? 0);
            $status         = $item['status'] ?? 'pending';
            $process        = $item['process'] ?? '';
            $selectedModel  = $item['model'] ?? null;

            // Always increment last number and generate ref no
            $lastNumber++;
            $refNo = $today . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

            // inject into $input so processCustomerForm also gets it
            $item['reference_no'] = $refNo;

            // upsert raw material (always runs for all items)
            $success = $this->plannerModel->upsertIssuedRawMaterial(
                $materialNo,
                $componentName,
                $quantity,
                $status,
                $refNo,
                $process,
                $selectedModel
            );

            $available = $componentInventory[$index]['available_quantity'] ?? 0;

            $results[] = [
                'material_no'        => $materialNo,
                'components_name'    => $componentName,
                'quantity_requested' => $quantity,
                'available_quantity' => $available,
                'reference_no'       => $refNo,
                'success'            => $success
            ];

            // only add first occurrence of material_no to processCustomerForm
            if ($materialNo && !isset($seenMaterials[$materialNo])) {
                $seenMaterials[$materialNo] = true;
                $uniqueInputForProcess[] = $item;
            }
        }
        unset($item); // break reference

        // call processCustomerForm ONLY with unique materials
        $deliveryResults = $this->plannerModel->processCustomerForm($uniqueInputForProcess, $currentDateTime);

        echo json_encode([
            'success'          => true,
            'results'          => $results,
            'delivery_results' => $deliveryResults
        ]);
    }
    public function getFormHistory()
    {
        $model = $_GET['model'] ?? '';

        $formHistory = $this->plannerModel->getFormHistory($model);

        echo json_encode($formHistory);
    }
}
