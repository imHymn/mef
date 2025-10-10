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
}
