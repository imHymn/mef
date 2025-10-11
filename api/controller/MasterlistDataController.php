<?php
require_once __DIR__ . '/../model/MasterlistDataModel.php';

class MasterlistDataController
{
    private $masterlistDataModel;

    public function __construct()
    {
        $db = new DatabaseClass();
        $this->masterlistDataModel = new MasterlistDataModel($db);
    }
    public function getRMData()
    {
        $model = $_GET['model'] ?? '';

        $rmData = $this->masterlistDataModel->getRMData($model);
        echo json_encode($rmData);
    }
    public function getSkuMaterialNo()
    {
        $model = $_GET['model'] ?? null;
        $skuMaterialNo = $this->masterlistDataModel->getSkuMaterialNo($model);
        echo json_encode($skuMaterialNo);
    }
    public function getSKUData()
    {
        $model = $_GET['model'] ?? null;
        $skuData = $this->masterlistDataModel->getSKUData($model);
        echo json_encode($skuData);
    }
    public function getComponentData()
    {
        $model = $_GET['model'] ?? null;
        $componentData = $this->masterlistDataModel->getComponentData($model);
        echo json_encode($componentData);
    }
    public function updateSKU()
    {
        global $input;
        $result = $this->masterlistDataModel->updateSKU($input);
        echo json_encode($result);
    }
    public function addSKU()
    {
        global $input;
        $result = $this->masterlistDataModel->addSKU($input);

        if (isset($result['success']) && $result['success'] === true) {
            echo json_encode(['success' => true, 'message' => $result['message']]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Unknown error occurred']);
        }
    }
    public function addComponent()
    {
        global $input;
        $result = $this->masterlistDataModel->addComponent($input);

        if (isset($result['success']) && $result['success'] === true) {
            echo json_encode(['success' => true, 'message' => $result['message']]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Unknown error occurred']);
        }
    }
    public function addRM()
    {
        global $input;
        $result = $this->masterlistDataModel->addRM($input);

        if (isset($result['success']) && $result['success'] === true) {
            echo json_encode(['success' => true, 'message' => $result['message']]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Unknown error occurred']);
        }
    }
    public function deleteRM()
    {
        global $input;
        $id = $input['id'];
        $deleted = $this->masterlistDataModel->deleteRM($id);

        if ($deleted) {
            echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete or SKU not found']);
        }
    }
    public function updateComponent()
    {
        global $input;
        $result = $this->masterlistDataModel->updateComponent($input);
        if (isset($result['error'])) {
            echo json_encode(['success' => false, 'message' => $result['error']]);
            return;
        }
        echo json_encode(['success' => true, 'message' => 'Component updated successfully']);
    }
    public function updateRM()
    {
        global $input;
        $result = $this->masterlistDataModel->updateRM($input);
        if (isset($result['error'])) {
            echo json_encode(['success' => false, 'message' => $result['error']]);
            return;
        }
        echo json_encode(['success' => true, 'message' => 'Component updated successfully']);
    }
    public function deleteSKU()
    {
        global $input;
        $id = $input['id'];
        $deleted = $this->masterlistDataModel->deleteSKU($id);

        if ($deleted) {
            echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete or SKU not found']);
        }
    }
    public function deleteComponent()
    {
        global $input;
        $id = $input['id'];
        $deleted = $this->masterlistDataModel->deleteComponent($id);

        if ($deleted) {
            echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete or component not found']);
        }
    }
    public function getRMComponent()
    {
        $model = $_GET['model'] ?? null;
        $rmComponents = $this->masterlistDataModel->getRMComponent($model);
        echo json_encode($rmComponents);
    }
}
