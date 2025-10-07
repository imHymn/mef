<?php
class RMModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function getIssuedComponents($model)
    {
        $strictMaterialFilter = ($model === '32XD');

        $sql = "
    SELECT 
        i.*,
        MAX(ci.stage_name) AS stage_name,
        MAX(ci.process_quantity) AS process_quantity,
        MAX(ci.usage_type) AS usage_type,
        (
            SELECT JSON_ARRAYAGG(
                JSON_OBJECT(
                    'material_no', sub.material_no,
                    'material_description', sub.material_description,
                    'usage', sub.usage,
                    'component_name', sub.component_name
                )
            )
            FROM (
                SELECT DISTINCT 
                    r.material_no, 
                    r.material_description, 
                    r.usage, 
                    r.component_name
                FROM rawmaterials_inventory r
                WHERE r.component_name = i.component_name
                " . ($strictMaterialFilter ? "AND r.material_no = i.material_no" : "") . "
            ) AS sub
        ) AS raw_materials
    FROM issued_rawmaterials i
    LEFT JOIN components_inventory ci 
        ON ci.components_name = i.component_name
        AND (
            i.component_name IN ('CLIP 25', 'CLIP 60', 'NUT WELD', 'NUT WELD', 'NUT WELD (6)', 'NUT WELD (8)', 'NUT WELD (10)', 'NUT WELD (11.112)', 'NUT WELD (12)') 
            OR " . ($strictMaterialFilter ? "ci.material_no = i.material_no" : "ci.material_no = i.material_no") . "
        )
    WHERE i.delivered_at IS NULL
    AND i.model = :model
    GROUP BY i.id,i.reference_no
    ";

        return $this->db->Select($sql, ['model' => $model]);
    }
    public function getIssuedHistory($model)
    {
        $sql = "
    SELECT 
        i.*, 
        ci.stage_name, 
        ci.process_quantity,
        ci.usage_type,
        (
            SELECT JSON_ARRAYAGG(
                JSON_OBJECT(
                    'material_no', sub.material_no,
                    'material_description', sub.material_description,
                    'usage', sub.usage,
                    'component_name', sub.component_name
                )
            )
            FROM (
                SELECT DISTINCT 
                    r.material_no, 
                    r.material_description, 
                    r.usage, 
                    r.component_name
                FROM rawmaterials_inventory r
                WHERE r.component_name = i.component_name
            ) AS sub
        ) AS raw_materials
    FROM issued_rawmaterials i
    LEFT JOIN components_inventory ci 
        ON ci.components_name = i.component_name
        AND (
            i.component_name IN ('CLIP 25', 'CLIP 60', 'NUT WELD', 'NUT WELD', 'NUT WELD (6)', 'NUT WELD (8)', 'NUT WELD (10)', 'NUT WELD (11.112)', 'NUT WELD (12)', 'REINFORCEMENT') 
            OR ci.material_no = i.material_no
        )
    WHERE i.delivered_at IS NOT NULL
      AND i.model = :model
    ";

        return $this->db->Select($sql, ['model' => $model]);
    }
    public function getRMStocks(string $material_no, string $component_name): ?int
    {
        $sql = "SELECT rm_stocks 
            FROM components_inventory 
            WHERE material_no = :material_no 
              AND components_name = :component_name 
            LIMIT 1";

        $params = [
            ':material_no' => $material_no,
            ':component_name' => $component_name
        ];

        $result = $this->db->SelectOne($sql, $params);

        return $result ? (int) $result['rm_stocks'] : null;
    }
    public function getFuelTypeByReference(string $referenceNo): ?string
    {
        $sql = "SELECT fuel_type 
                FROM delivery_history 
                WHERE reference_no = :reference_no 
                LIMIT 1";

        $row = $this->db->SelectOne($sql, [':reference_no' => $referenceNo]);

        return $row['fuel_type'] ?? null;
    }
    public function getMaterialsWithSamePair($material_no, $component_name)
    {
        // Step 1: Get the `pair` value for the provided component + material
        $sql = "SELECT pair FROM rawmaterials_inventory 
            WHERE material_no = :material_no AND component_name = :component_name 
            LIMIT 1";

        $result = $this->db->SelectOne($sql, [
            'material_no' => $material_no,
            'component_name' => $component_name
        ]);

        if (!$result || empty($result['pair'])) {
            return []; // No pair value or not found
        }

        $pairValue = $result['pair'];

        // Step 2: Get all entries with the same pair (excluding the original one if needed)
        $sql = "SELECT * FROM rawmaterials_inventory WHERE pair = :pair";
        return $this->db->Select($sql, ['pair' => $pairValue]);
    }
    public function updateComponentInventoryStatusByPairList(array $pairedItems, int $rm_stocks): int
    {
        $updatedCount = 0;

        foreach ($pairedItems as $item) {
            $material_no = $item['material_no'];
            $component_name = $item['component_name'];
            $isClip = in_array($component_name, ['CLIP 25', 'CLIP 60', 'NUT WELD', 'NUT WELD', 'NUT WELD (6)', 'NUT WELD (8)', 'NUT WELD (10)', 'NUT WELD (11.112)', 'NUT WELD (12)', 'REINFORCEMENT']);

            $sql = "UPDATE components_inventory 
                SET status = :status, section = :section, rm_stocks = :rm_stocks";

            if ($isClip) {
                $sql .= " WHERE components_name = :component_name";
                $params = [
                    ':status' => 'done',
                    ':section' => 'stamping',
                    ':rm_stocks' => $rm_stocks,
                    ':component_name' => $component_name
                ];
            } else {
                $sql .= " WHERE material_no = :material_no AND components_name = :component_name";
                $params = [
                    ':status' => 'done',
                    ':section' => 'stamping',
                    ':rm_stocks' => $rm_stocks,
                    ':material_no' => $material_no,
                    ':component_name' => $component_name
                ];
            }

            $result = $this->db->Update($sql, $params);

            if ($result) {
                $updatedCount++;
            } else {
                error_log("Failed to update component: $material_no / $component_name");
            }
        }

        return $updatedCount;
    }
    public function insertIntoRMWarehouse(array $data): bool
    {
        $sql = "INSERT INTO `rm_warehouse` 
            (`material_no`, `component_name`, `process_quantity`, `quantity`, `status`, `created_at`, `reference_no`,model) 
            VALUES 
            (:material_no, :component_name, :process_quantity, :quantity, :status, :created_at, :reference_no,:model)";

        $params = [
            ':material_no' => $data['material_no'],
            ':component_name' => $data['component_name'],
            ':process_quantity' => $data['process_quantity'],
            ':quantity' => $data['quantity'],
            ':status' => 'pending',
            ':created_at' => $data['created_at'],
            ':reference_no' => $data['reference_no'],
            ':model' => $data['model']
        ];

        return $this->db->Update($sql, $params);
    }
    public function updateInventoryAndWarehouse(string $materialNo, string $componentName, int $quantity): void
    {
        $isClip = in_array($componentName, ['CLIP 25', 'CLIP 60', 'NUT WELD', 'NUT WELD', 'NUT WELD (6)', 'NUT WELD (8)', 'NUT WELD (10)', 'NUT WELD (11.112)', 'NUT WELD (12)', 'REINFORCEMENT'], true);

        // Update components_inventory
        $updateInventorySql = "
        UPDATE components_inventory 
        SET actual_inventory = actual_inventory + :quantity, rm_stocks = 0
        WHERE components_name = :component_name";
        $inventoryParams = [
            ':quantity'       => $quantity,
            ':component_name' => $componentName
        ];

        if (!$isClip) {
            $updateInventorySql .= " AND material_no = :material_no";
            $inventoryParams[':material_no'] = $materialNo;
        }

        $this->db->Update($updateInventorySql, $inventoryParams);

        // Update rm_warehouse
        $updateWarehouseSql = "
        UPDATE rm_warehouse 
        SET status = 'done'
        WHERE component_name = :component_name";
        $warehouseParams = [
            ':component_name' => $componentName
        ];

        if (!$isClip) {
            $updateWarehouseSql .= " AND material_no = :material_no";
            $warehouseParams[':material_no'] = $materialNo;
        }

        $this->db->Update($updateWarehouseSql, $warehouseParams);
    }
    public function getNextStampingBatch(string $material_no, string $component_name): int
    {
        $sql = "SELECT MAX(batch) as last_batch FROM stamping WHERE material_no = :material_no AND components_name = :components_name";
        $result = $this->db->SelectOne($sql, [
            ':material_no' => $material_no,
            ':components_name' => $component_name
        ]);

        return ($result && $result['last_batch']) ? ((int)$result['last_batch'] + 1) : 1;
    }
    public function insertStampingStages(array $data, array $flattenedStages, int $existingCount, string $dateToday): bool|string
    {
        $sql = "INSERT INTO `stamping` 
(`material_no`, `components_name`, `process_quantity`, `stage`, `stage_name`, `section`, 
`cycle_time`, `machine_name`, `manpower`, `total_quantity`, `pending_quantity`, `status`, 
`reference_no`, `created_at`, `batch`, `pair`,`duplicated`,model,fuel_type,customer_id)
VALUES 
(:material_no, :components_name, :process_quantity, :stage, :stage_name, :section, 
:cycle_time, :machine_name, :manpower, :total_quantity, :pending_quantity, :status, 
:reference_no, :created_at, :batch, :pair,:duplicated,:model,:fuel_type,:customer_id)
";

        // Extract these first
        $processQty = (int) $data['process_quantity'];
        $totalQty   = $data['quantity'];
        $materialNo = $data['material_no'];
        $componentName = $data['component_name'];
        $createdAt = $data['created_at'];
        $model = $data['model'];
        $customerID = $data['reference_no'];
        $nextBatch = $this->getNextBatchNumber() + 1;

        error_log(print_r($flattenedStages, true));

        for ($i = 0; $i < $processQty; $i++) {
            if (!isset($flattenedStages[$i])) {
                return "Flattened stage index $i is missing";
            }

            $stageIndex = $i + 1;
            $referenceNo = $dateToday . '-' . str_pad($existingCount + $stageIndex, 4, '0', STR_PAD_LEFT);
            $params = [
                ':material_no'       => $materialNo,
                ':components_name'   => $componentName,
                ':process_quantity'  => $processQty,
                ':stage'             => $stageIndex,
                ':stage_name'        => $flattenedStages[$i]['stage_name'],
                ':section'           => $flattenedStages[$i]['section'],
                ':cycle_time'        => $flattenedStages[$i]['cycle_time'] ?? 0,
                ':machine_name'      => $flattenedStages[$i]['machine_name'] ?? null,
                ':manpower'          => $flattenedStages[$i]['manpower'] ?? null,  // ✅ ADD THIS
                ':total_quantity'    => $totalQty,
                ':pending_quantity'  => $totalQty,
                ':status'            => 'pending',
                ':reference_no'      => $referenceNo,
                ':created_at'        => $createdAt,
                ':batch'             => $nextBatch,
                ':model'             => $model,
                ':customer_id'      => $customerID,
                ':fuel_type'        => $data['fuel_type'] ?? null,
                ':pair'              => $data['pair'] ?? null,
                ':duplicated'              => 1
            ];

            if (!$this->db->Insert($sql, $params)) {
                return false;
            }
        }

        return true;
    }
    public function updateIssuedRawmaterials($id, $quantity)
    {
        $sql = "UPDATE issued_rawmaterials 
            SET delivered_at = NOW() ,rm_quantity =:quantity,`status`=:status
            WHERE id = :id 
           ";

        $params = [
            ':id' => $id,
            'status' => 'done',
            ':quantity' => $quantity
        ];

        return $this->db->Update($sql, $params);
    }
    public function getNextBatchNumber(): int
    {
        $sql = "SELECT MAX(batch) as max_batch 
            FROM stamping ";



        $result = $this->db->SelectOne($sql);

        return $result && $result['max_batch'] !== null ? (int)$result['max_batch'] : 0;
    }
}
