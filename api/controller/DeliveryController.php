<?php
require_once __DIR__ . '/../model/DeliveryModel.php';

class DeliveryController
{
    private $deliveryModel;

    public function __construct()
    {
        $db = new DatabaseClass();
        $this->deliveryModel = new DeliveryModel($db);
    }
    public function getPendingDelivery()
    {

        try {

            $model = $_GET['model'] ?? '';
            $now = new DateTime();
            $cutoff = (clone $now)->modify('-5 days')->format('Y-m-d H:i:s');

            $results = $this->deliveryModel->getPendingDelivery($cutoff, $model);
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
        } catch (PDOException $e) {
            echo "DB Error: " . $e->getMessage();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }
    public function getDeliveryHistory()
    {
        $model = $_GET['model'] ?? '';
        $deliveredHistory = $this->deliveryModel->getDeliveryHistory($model);

        echo json_encode($deliveredHistory);
    }
    public function getTruck()
    {
        $trucks = $this->deliveryModel->getTruck();
        echo json_encode($trucks);
    }
    public function sku_delivery()
    {
        global $input;
        $input['created_at'] = $input['created_at'] ?? date('Y-m-d H:i:s');
        $input['updated_at'] = $input['updated_at'] ?? date('Y-m-d H:i:s');
        $input['status']     = $input['status'] ?? 'pending';
        $input['section']    = $input['section'] ?? 'DELIVERY';
        try {
            $result = $this->deliveryModel->sku_delivery($input);

            echo json_encode([
                'status'  => $result > 0 ? 'success' : 'error',
                'message' => $result > 0 ? 'Record inserted' : 'No record was inserted',
                'upsert'  => [
                    'material_no'          => $input['material_no'] ?? '',
                    'material_description' => $input['material_description'] ?? '',
                    'quantity'             => $input['qty_allocated'] ?? 0,
                    'reference_no'         => $input['reference_no'] ?? '',
                    'created_at'           => $input['created_at'],
                    'process'              => $input['process'] ?? null,
                    'model'                => $input['model'] ?? '',
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    public function component_delivery()
    {
        global $input;

        // Default values (only set if missing)
        $input['created_at']     = $input['created_at']     ?? date('Y-m-d H:i:s');
        $input['updated_at']     = $input['updated_at']     ?? date('Y-m-d H:i:s');
        $input['status']         = $input['status']         ?? 'pending';
        $input['section']        = $input['section']        ?? 'DELIVERY';
        $input['supplement_order'] = (int)($input['supplement_order'] ?? 0);
        $input['qty_allocated']  = (int)($input['qty_allocated'] ?? 0);
        $input['total_quantity'] = (int)($input['total_quantity'] ?? 0);

        try {
            // Call model
            $result = $this->deliveryModel->component_delivery($input);

            echo json_encode([
                'status'  => $result > 0 ? 'success' : 'error',
                'message' => $result > 0 ? 'Record inserted' : 'No record was inserted',
                'upsert'  => [
                    'material_no'          => $input['material_no'] ?? '',
                    'material_description' => $input['material_description'] ?? '',
                    'quantity'             => $input['qty_allocated'],
                    'reference_no'         => $input['reference_no'] ?? '',
                    'created_at'           => $input['created_at'],
                    'process'              => $input['process'] ?? null,
                    'model'                => $input['model'] ?? '',
                    'shift'                => $input['shift'] ?? '',
                    'lot_no'               => $input['lot_no'] ?? '',
                    'date_needed'          => $input['date_needed'] ?? null,
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
