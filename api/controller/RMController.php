<?php
require_once __DIR__ . '/../model/RMModel.php';

class RMController
{
    private $rmModel;
    private $db;

    public function __construct()
    {
        $this->db = new DatabaseClass();
        $this->rmModel = new RMModel($this->db);
    }
    public function getIssuedComponents()
    {

        $model = $_GET['model'] ?? '';

        $results = $this->rmModel->getIssuedComponents($model);

        echo json_encode([
            'status' => 'success',
            'data' => $results
        ]);
    }
    public function getIssuedHistory()
    {

        $model = $_GET['model'] ?? '';

        $results = $this->rmModel->getIssuedHistory($model);

        echo json_encode([
            'status' => 'success',
            'data' => $results
        ]);
    }
    public function getRMStocks()
    {
        global $input;
        $rmStocks = $this->rmModel->getRMStocks(
            $input['material_no'],
            $input['component_name']
        );

        echo json_encode([
            'status' => 'success',
            'rm_stocks' => $rmStocks
        ]);
    }

    public function issueRM()
    {
        global $input;
        try {
            $created_at = date('Y-m-d H:i:s');

            try {
                $this->db->beginTransaction();

                $fuelType = $this->rmModel->getFuelTypeByReference($input['reference_no']);
                $pairedMaterials = $this->rmModel->getMaterialsWithSamePair(
                    $input['material_no'],
                    $input['component_name']
                );

                if (empty($pairedMaterials)) {
                    $pairedMaterials = [[
                        'material_no'     => $input['material_no'],
                        'component_name'  => $input['component_name'],
                        'pair'            => null
                    ]];
                }

                $this->rmModel->updateComponentInventoryStatusByPairList(
                    $pairedMaterials,
                    $input['quantity']
                );

                $dateToday = date('Ymd');
                $prefix    = $dateToday . '-%';

                $sqlCount     = "SELECT COUNT(*) as count FROM stamping WHERE reference_no LIKE :prefix";
                $countResult  = $this->db->SelectOne($sqlCount, [':prefix' => $prefix]);
                $existingCount = $countResult ? (int)$countResult['count'] : 0;

                $batch = null;
                $flattenedStages = null;

                foreach ($pairedMaterials as $pair) {
                    $pairMaterialNo     = $pair['material_no'];
                    $pairComponentName  = $pair['component_name'];
                    $pairValue          = $pair['pair'] ?? null;

                    $rmReferenceNo = $dateToday . '-' . str_pad($existingCount + 1, 4, '0', STR_PAD_LEFT);

                    $this->rmModel->insertIntoRMWarehouse([
                        'material_no'      => $pairMaterialNo,
                        'component_name'   => $pairComponentName,
                        'process_quantity' => $input['process_quantity'],
                        'quantity'         => $input['quantity'],
                        'created_at'       => $created_at,
                        'reference_no'     => $rmReferenceNo,
                        'model'            => $input['model']
                    ]);

                    if ($input['type'] === 'supplied') {
                        $this->rmModel->updateInventoryAndWarehouse($pairMaterialNo, $pairComponentName, $input['quantity']);
                    } else {
                        $nextBatch = $this->rmModel->getNextStampingBatch($pairMaterialNo, $pairComponentName);
                        if (!$batch) $batch = $nextBatch;

                        $decodedStageGroup = json_decode($input['stage_name'], true);
                        if (!is_array($decodedStageGroup)) {
                            throw new Exception('Invalid stage_name JSON structure');
                        }

                        // 🔽 Inline flattenStages logic
                        $flattenedStages = [];
                        foreach ($decodedStageGroup as $entry) {
                            if (!is_array($entry)) continue;

                            if (!isset($entry['section'], $entry['stages']) || !is_array($entry['stages'])) continue;

                            foreach ($entry['stages'] as $stageName => $data) {
                                if (is_array($data)) {
                                    $flattenedEntry = [
                                        'stage_name'   => $stageName,
                                        'section'      => $entry['section'],
                                        'cycle_time'   => $data['cycle'] ?? 0,
                                        'machine_name' => $data['machine'] ?? null,
                                    ];

                                    if (isset($data['manpower'])) {
                                        $flattenedEntry['manpower'] = $data['manpower'];
                                    }

                                    $flattenedStages[] = $flattenedEntry;
                                } else {
                                    $flattenedStages[] = [
                                        'stage_name'   => $stageName,
                                        'section'      => $entry['section'],
                                        'cycle_time'   => is_numeric($data) ? floatval($data) : 0,
                                        'machine_name' => null
                                    ];
                                }
                            }
                        }
                        // 🔼 End inline flatten

                        $result3 = $this->rmModel->insertStampingStages([
                            'reference_no'     => $input['reference_no'],
                            'material_no'      => $pairMaterialNo,
                            'component_name'   => $pairComponentName,
                            'process_quantity' => $input['process_quantity'],
                            'quantity'         => $input['quantity'],
                            'created_at'       => $created_at,
                            'pair'             => $pairValue,
                            'model'            => $input['model'],
                            'fuel_type'        => $fuelType
                        ], $flattenedStages, $existingCount, $dateToday, $nextBatch);

                        if ($result3 !== true) {
                            throw new Exception($result3);
                        }
                    }
                }

                $this->rmModel->updateIssuedRawmaterials($input['id'], $input['quantity']);

                $this->db->commit();

                echo json_encode([
                    'status'         => 'success',
                    'message'        => 'All paired records inserted and updated successfully',
                    'quantity'       => $input['quantity'],
                    'id'             => $input['id'],
                    'paired'         => $pairedMaterials,
                    'flattenedStages' => $flattenedStages,
                    'stage_name'     => $input['stage_name']
                ]);
            } catch (Exception $e) {
                $this->db->rollBack();
                echo json_encode(['status' => 'error', 'message' => "Error: " . $e->getMessage()]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Validation error: ' . $e->getMessage()]);
        }
    }
}
