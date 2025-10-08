<?php
class StampingModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function getUserByIdAndName($userId, $name)
    {
        $sql = "SELECT * FROM users WHERE user_id = :user_id AND name = :name LIMIT 1";
        $params = [
            ':user_id' => $userId,
            ':name'    => $name
        ];

        $user = $this->db->SelectOne($sql, $params);
        return $user ?: null;
    }
    public function getLatestReferenceNo()
    {
        // Step 1: Get today's date in Ymd format (e.g. 20251008)
        $todayPrefix = date('Ymd');

        // Step 2: Query to get the latest reference_no for today
        $sql = "SELECT reference_no 
            FROM issued_rawmaterials 
            WHERE reference_no LIKE :prefix 
            ORDER BY reference_no DESC 
            LIMIT 1";

        $result = $this->db->SelectOne($sql, [':prefix' => $todayPrefix . '-%']);

        // Step 3: If found, increment the numeric part
        if ($result && isset($result['reference_no'])) {
            $lastRef = $result['reference_no']; // e.g. 20251008-0002
            $parts = explode('-', $lastRef);
            $lastNum = isset($parts[1]) ? intval($parts[1]) : 0;
            $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
            $newRef = $todayPrefix . '-' . $newNum;
        } else {
            // Step 4: If no existing reference for today, start from 0001
            $newRef = $todayPrefix . '-0001';
        }

        return $newRef;
    }

    public function resetStamping($id)
    {
        $updateSql = "UPDATE stamping SET time_in = NULL, time_out = NULL, person_incharge = NULL WHERE id = :id";
        $this->db->Update($updateSql, [':id' => $id]);

        return true;
    }
    public function getSpecificData_assigned($full_name, $model)
    {
        $sql = " SELECT * FROM stamping WHERE person_incharge =:person AND time_out IS NULL AND model = :model ";
        return $this->db->Select($sql, [':model' => $model, ':person' => $full_name]);
    }
    public function getAllModelData_assigned($full_name, $model)
    {
        $sql = "SELECT * FROM stamping WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY) AND person_incharge=:person AND time_out is NULL ";
        return $this->db->Select($sql, [':person' => $full_name]);
    }
    public function assignOperator($itemId, $person, $byOrder)
    {
        $sql = "UPDATE stamping SET person_incharge = :person, by_order = :by_order WHERE id = :id";
        return $this->db->Update($sql, [':person' => $person, ':by_order' => $byOrder, ':id' => $itemId]);
    }
    public function getData_toassign($model)
    {
        $sql = " SELECT * FROM stamping WHERE person_incharge IS NULL AND model = :model ";
        return $this->db->Select($sql, [':model' => $model]);
    }
    public function getAllData_assigned($model)
    {
        $sql = "SELECT * FROM stamping WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY) AND model = :model AND person_incharge IS NOT NULL";
        return $this->db->Select($sql, ['model' => $model]);
    }
    public function getMachines()
    {
        return $this->db->Select("SELECT DISTINCT* FROM machine ");
    }
    public function getComponentStatus($data)
    {
        $sql = "SELECT stage_name,section,stage, status FROM stamping 
                WHERE material_no = :material_no 
                AND components_name = :components_name AND batch=:batch
                ORDER BY stage ASC";
        $params = [
            ':material_no' => $data['material_no'],
            ':components_name' => $data['components_name'],
            ':batch' => $data['batch']
        ];
        return $this->db->Select($sql, $params);
    }
    public function getComponentInventory($model)
    {
        return $this->db->Select("SELECT * FROM `components_inventory` WHERE `model` = :model", ['model' => $model]);
    }
    public function getAllStampingData($model)
    {
        $stampingData = $this->db->Select("SELECT * FROM `stamping` WHERE status = 'done' AND LOWER(section) NOT IN ('finishing', 'spot welding') 
        AND model = :model", [':model' => $model]);
        $reworkData = $this->db->Select("SELECT * FROM `rework_stamping` WHERE stamping_timeout IS NOT NULL AND model = :model", [':model' => $model]);
        return array_merge($stampingData, $reworkData);
    }
    public function timeinOperator($data)
    {
        $hasManpower = isset($data['manpower']) && $data['manpower'] !== null;

        $sql = "UPDATE `stamping` SET person_incharge = :name, time_in = :timein, status = :status, remarks = :remarks WHERE id = :id AND duplicated = :duplicated";

        // if ($hasManpower) {
        //     $sql .= " WHERE reference_no = :reference_no AND stage = :stage AND pair = :pair AND duplicated = :duplicated";
        // } else {
        //     $sql .= " WHERE id = :id AND duplicated = :duplicated";
        // }

        $params = [
            ':name'     => $data['name'],
            ':timein'   => date('Y-m-d H:i:s'),
            ':status'   => 'ongoing',
            ':remarks'  => $data['remarks'] === '' ? null : $data['remarks'],
            ':duplicated'     => $data['duplicated'],
            ':id' => $data['id'],

        ];

        // if ($hasManpower) {
        //     $params[':reference_no'] = $data['reference_no'];
        //     $params[':stage']        = $data['stage'];
        //     $params[':pair']         = $data['pair'];
        //     $params[':duplicated']         = $data['duplicated'];
        // } else {
        //     $params[':id'] = $data['id'];
        //     $params[':duplicated']         = $data['duplicated'];
        // }

        return $this->db->Update($sql, $params);
    }
    public function updateStampingTimeout(array $data): bool
    {
        $timeout = date('Y-m-d H:i:s');
        $inputQty = $data['inputQuantity'];

        // If manpower is present, update 2 rows with same quantity
        if (!empty($data['manpower']) && !empty($data['pair'])) {
            $pendingQuantity = ($data['pending_quantity'] > 0)
                ? $data['pending_quantity'] - $inputQty
                : $data['total_quantity'] - $inputQty;

            $sql = "UPDATE `stamping` 
            SET person_incharge = :name,
                time_out = :timeout,
                status = 'done',
                quantity = :quantity,
                pending_quantity = :pending_quantity,
                updated_at = :updated_at
            WHERE reference_no = :reference_no 
              AND stage = :stage 
              AND pair = :pair 
              AND duplicated = :duplicated ";

            $commonParams = [
                ':name'             => $data['name'],
                ':timeout'          => $timeout,
                ':pending_quantity' => $pendingQuantity,
                ':updated_at'       => $timeout,
                ':reference_no'     => $data['reference_no'],
                ':stage'            => $data['stage'],
                ':pair'             => $data['pair'],
                ':duplicated'       => $data['duplicated']
            ];

            // Both rows get same $inputQty
            $params1 = $commonParams + [':quantity' => $inputQty];
            $params2 = $commonParams + [':quantity' => $inputQty];

            $result1 = $this->db->Update($sql, $params1);
            $result2 = $this->db->Update($sql, $params2);

            return $result1 && $result2;
        } else {
            $pendingQuantity = ($data['pending_quantity'] > 0)
                ? $data['pending_quantity'] - $inputQty
                : $data['total_quantity'] - $inputQty;

            $sql = "UPDATE `stamping` 
            SET person_incharge = :name,
                time_out = :timeout,
                status = 'done',
                quantity = :inputQuantity,
                pending_quantity = :pending_quantity,
                updated_at = :updated_at
            WHERE id = :id AND duplicated = :duplicated";

            $params = [
                ':name'             => $data['name'],
                ':timeout'          => $timeout,
                ':inputQuantity'    => $inputQty,
                ':pending_quantity' => $pendingQuantity,
                ':updated_at'       => $timeout,
                ':id'               => $data['id'],
                ':duplicated'       => $data['duplicated']
            ];

            return $this->db->Update($sql, $params);
        }
    }
    public function getStampingByReferenceStagePair(string $referenceNo, string $stage, ?string $pair, string $duplicated): array
    {
        $sql = "SELECT * FROM stamping 
            WHERE reference_no = :reference_no 
              AND stage = :stage 
              AND pair = :pair AND duplicated=:duplicated
            ORDER BY id ASC";
        return $this->db->Select($sql, [
            ':reference_no' => $referenceNo,
            ':stage' => $stage,
            ':duplicated' => $duplicated,
            ':pair' => $pair
        ]);
    }
    public function getStampingById(int $id): ?array
    {
        return $this->db->SelectOne("SELECT * FROM stamping WHERE id = :id", [':id' => $id]);
    }
    public function getQuantityStats(string $referenceNo, ?string $stage = null, ?string $pair = null): ?array
    {
        $sql = "
        SELECT SUM(quantity) as total_quantity_done, MAX(total_quantity) as max_total_quantity
        FROM stamping
        WHERE reference_no = :reference_no 
    ";

        $params = [':reference_no' => $referenceNo];

        if ($stage !== null && $pair !== null) {
            $sql .= " AND stage = :stage AND pair = :pair";
            $params[':stage'] = $stage;
            $params[':pair'] = $pair;
        }

        return $this->db->SelectOne($sql, $params);
    }
    public function getPairRows(string $referenceNo, string $stage, string $pair, string $duplicated): array
    {
        $sql = "
        SELECT * FROM stamping
        WHERE reference_no = :reference_no
        AND stage = :stage
        AND pair = :pair AND duplicated=:duplicated
    ";
        return $this->db->Select($sql, [
            ':reference_no' => $referenceNo,
            ':stage' => $stage,
            ':pair' => $pair,
            ':duplicated' => $duplicated
        ]);
    }
    public function duplicateIfNotDone(array $row, int $inputQuantity): int
    {
        $modifyCallback = function ($r) use ($inputQuantity) {
            return [
                'reference_no' => $r['reference_no'],
                'material_no' => $r['material_no'],
                'components_name' => $r['components_name'],
                'process_quantity' => $r['process_quantity'],
                'total_quantity' => $r['total_quantity'],
                'pending_quantity' => $r['pending_quantity'],
                'stage' => $r['stage'],
                'stage_name' => $r['stage_name'],
                'section' => $r['section'],
                'batch' => $r['batch'],
                'time_in' => null,
                'time_out' => null,
                'status' => 'pending',
                'person_incharge' => $r['person_incharge'],
                'created_at' => $r['created_at'],
                'cycle_time' => $r['cycle_time'],
                'pair' => $r['pair'],
                'machine_name' => $r['machine_name'],
                'manpower' => $r['manpower'],
                'model' => $r['model'],
                'fuel_type' => $r['fuel_type'],
                'duplicated' => $r['duplicated'] + 1,
                'updated_at' => null,
                'customer_id' => $r['customer_id'],
                'by_order' => $r['by_order']
            ];
        };

        $insertSql = "INSERT INTO stamping (
            reference_no, material_no, components_name, process_quantity, total_quantity, pending_quantity,
            stage, stage_name, section, batch, time_in, time_out, status,fuel_type,customer_id,by_order,
            person_incharge, created_at, updated_at,cycle_time,pair,machine_name,manpower,duplicated,model
        ) VALUES (
            :reference_no, :material_no, :components_name, :process_quantity, :total_quantity, :pending_quantity,
            :stage, :stage_name, :section, :batch, :time_in, :time_out, :status,:fuel_type,:customer_id,:by_order,
            :person_incharge, :created_at, :updated_at,:cycle_time,:pair,:machine_name,:manpower,:duplicated,:model
        )";

        return $this->db->DuplicateAndModify("SELECT * FROM stamping WHERE id = :id", [':id' => $row['id']], $modifyCallback, $insertSql);
    }
    public function areAllStagesDone(string $materialNo, string $componentName, int $processQty, int $totalQty, int $batch): bool
    {
        for ($stage = 1; $stage <= $processQty; $stage++) {
            $sql = "SELECT SUM(pending_quantity) as pending_stage_qty
                FROM stamping
                WHERE material_no = :material_no
                  AND components_name = :component_name
                  AND stage = :stage
                  AND batch = :batch";

            $result = $this->db->SelectOne($sql, [
                ':material_no' => $materialNo,
                ':component_name' => $componentName,
                ':stage' => $stage,
                ':batch' => $batch
            ]);

            $pendingQty = (int)($result['pending_stage_qty'] ?? 0);

            // If any stage still has pending_quantity > 0, return false
            if ($pendingQty > 0) {
                return false;
            }
        }

        return true;
    }

    public function getDateNeededByReference($referenceNo)
    {
        $sql = "SELECT date_needed 
            FROM delivery_history 
            WHERE reference_no = :reference_no 
         ";

        return $this->db->SelectOne($sql, [':reference_no' => $referenceNo])['date_needed'] ?? null;
    }
    public function moveToQCList(array $data): ?array
    {
        $qty = (int)$data['total_quantity'];
        $insertedIds = [];

        $baseRef = $data['reference_no']; // e.g., 20250902-0002

        $sqlInsert = "INSERT INTO qc_list
        (model, shift, lot_no, date_needed, reference_no, customer_id, material_no, material_description, 
         total_quantity, status, section, assembly_section, created_at, variant, cycle_time, 
         pi_kbn_quantity, pi_kbn_pieces, fuel_type, part_type)
    VALUES 
        (:model, :shift, :lot_no, :date_needed, :reference_no, :customer_id, :material_no, :material_description, 
         :total_quantity, :status, :section, :assembly_section, :created_at, :variant, :cycle_time, 
         :pi_kbn_quantity, :pi_kbn_pieces, :fuel_type, :part_type)";

        $piQty    = (int)$data['pi_kbn_quantity'];   // e.g. 20
        $piPieces = (int)$data['pi_kbn_pieces'];     // e.g. 5
        $suffix   = 1; // start at -0001

        // ✅ CASE 1: PI batching always takes priority
        if (!empty($piQty) && !empty($piPieces)) {
            for ($i = 0; $i < $piPieces; $i++) {
                $newRef = $baseRef . '-' . str_pad($suffix++, 4, '0', STR_PAD_LEFT);

                $params = [
                    ':model' => $data['model'],
                    ':shift' => $data['shift'],
                    ':lot_no' => $data['lot_no'],
                    ':variant' => $data['variant'],
                    ':date_needed' => $data['date_needed'],
                    ':reference_no' => $newRef,
                    ':customer_id' => $data['customer_id'],
                    ':material_no' => $data['material_no'],
                    ':material_description' => $data['material_description'],
                    ':assembly_section' => $data['assembly_section'],
                    ':total_quantity' => $piQty,
                    ':status' => 'pending',
                    ':section' => 'qc',
                    ':created_at' => $data['created_at'],
                    ':cycle_time' => $data['cycle_time'],
                    ':fuel_type' => $data['fuel_type'],
                    ':pi_kbn_quantity' => $piQty,
                    ':pi_kbn_pieces'   => $piPieces,
                    ':part_type' => 'stamping',
                ];

                $insertedId = $this->db->Insert($sqlInsert, $params);
                if ($insertedId) $insertedIds[] = $insertedId;
            }

            // ✅ CASE 2: STAMPING section with qty >= 60 → single record (no batching)
        } elseif ($qty >= 60) {
            $newRef = $baseRef . '-' . str_pad($suffix++, 4, '0', STR_PAD_LEFT);

            $params = [
                ':model' => $data['model'],
                ':shift' => $data['shift'],
                ':lot_no' => $data['lot_no'],
                ':variant' => $data['variant'],
                ':date_needed' => $data['date_needed'],
                ':reference_no' => $newRef,
                ':customer_id' => $data['customer_id'],
                ':material_no' => $data['material_no'],
                ':material_description' => $data['material_description'],
                ':assembly_section' => $data['assembly_section'],
                ':total_quantity' => $qty,
                ':status' => 'pending',
                ':section' => 'qc',
                ':created_at' => $data['created_at'],
                ':cycle_time' => $data['cycle_time'],
                ':fuel_type' => $data['fuel_type'],
                ':pi_kbn_quantity' => $piQty,
                ':pi_kbn_pieces'   => $piPieces,
                ':part_type' => 'stamping',
            ];

            $insertedId = $this->db->Insert($sqlInsert, $params);
            if ($insertedId) $insertedIds[] = $insertedId;

            // ✅ CASE 3: Normal batching (30 per batch, remainder last)
        } elseif ($qty < 60) {
            $batchQty = 30;
            $batches = intdiv($qty, $batchQty);
            $remainder = $qty % $batchQty;

            for ($i = 0; $i < $batches; $i++) {
                $newRef = $baseRef . '-' . str_pad($suffix++, 4, '0', STR_PAD_LEFT);

                $params = [
                    ':model' => $data['model'],
                    ':shift' => $data['shift'],
                    ':lot_no' => $data['lot_no'],
                    ':variant' => $data['variant'],
                    ':date_needed' => $data['date_needed'],
                    ':reference_no' => $newRef,
                    ':customer_id' => $data['customer_id'],
                    ':material_no' => $data['material_no'],
                    ':material_description' => $data['material_description'],
                    ':assembly_section' => $data['assembly_section'],
                    ':total_quantity' => $batchQty,
                    ':status' => 'pending',
                    ':section' => 'qc',
                    ':created_at' => $data['created_at'],
                    ':cycle_time' => $data['cycle_time'],
                    ':fuel_type' => $data['fuel_type'],
                    ':pi_kbn_quantity' => $piQty,
                    ':pi_kbn_pieces'   => $piPieces,
                    ':part_type' => 'stamping',
                ];

                $insertedId = $this->db->Insert($sqlInsert, $params);
                if ($insertedId) $insertedIds[] = $insertedId;
            }

            if ($remainder > 0) {
                $newRef = $baseRef . '-' . str_pad($suffix++, 4, '0', STR_PAD_LEFT);

                $params[':reference_no']   = $newRef;
                $params[':total_quantity'] = $remainder;

                $insertedId = $this->db->Insert($sqlInsert, $params);
                if ($insertedId) $insertedIds[] = $insertedId;
            }

            // ✅ CASE 4: Qty < 60 → single record
        } else {
            $newRef = $baseRef . '-' . str_pad($suffix++, 4, '0', STR_PAD_LEFT);

            $params = [
                ':model' => $data['model'],
                ':shift' => $data['shift'],
                ':lot_no' => $data['lot_no'],
                ':variant' => $data['variant'],
                ':date_needed' => $data['date_needed'],
                ':reference_no' => $newRef,
                ':customer_id' => $data['customer_id'],
                ':material_no' => $data['material_no'],
                ':material_description' => $data['material_description'],
                ':assembly_section' => $data['assembly_section'],
                ':total_quantity' => $qty,
                ':status' => 'pending',
                ':section' => 'qc',
                ':created_at' => $data['created_at'],
                ':cycle_time' => $data['cycle_time'],
                ':fuel_type' => $data['fuel_type'],
                ':pi_kbn_quantity' => $piQty,
                ':pi_kbn_pieces'   => $piPieces,
            ];

            $insertedId = $this->db->Insert($sqlInsert, $params);
            if ($insertedId) $insertedIds[] = $insertedId;
        }

        // ✅ Update delivery_form
        $sqlUpdateDelivery = "UPDATE delivery_form 
              SET section = 'QC' 
              WHERE reference_no = :reference_no";
        $this->db->Update($sqlUpdateDelivery, [':reference_no' => $baseRef]);

        // ✅ Update assembly_list
        $sqlUpdateAssembly = "UPDATE assembly_list 
              SET status = 'done', section = 'qc' 
              WHERE reference_no = :reference_no";
        $this->db->Update($sqlUpdateAssembly, [':reference_no' => $baseRef]);

        return [
            'insertedIds' => $insertedIds,
            'baseRef' => $baseRef,
            'qty' => $qty,
            'piQty' => $piQty,
            'piPieces' => $piPieces
        ];
    }
    public function updateIssueRawMaterial(string $materialNo, string $componentName, int $quantity): void
    {
        // Update only the first matching issued_rawmaterials row (oldest with rm_quantity & delivered_at)
        $this->db->Update("
        UPDATE issued_rawmaterials 
        SET process = 'done'
        WHERE id = (
            SELECT id FROM (
                SELECT id FROM issued_rawmaterials
                WHERE material_no = :material_no
                  AND component_name = :component_name
                  AND rm_quantity IS NOT NULL
                  AND delivered_at IS NOT NULL
                  AND process IS NULL
                ORDER BY issued_at ASC
                LIMIT 1
            ) AS sub
        )
    ", [
            ':material_no' => $materialNo,
            ':component_name' => $componentName
        ]);

        // Update all matching rm_warehouse entries (this part stays as-is)
        $this->db->Update("
        UPDATE rm_warehouse 
        SET status = 'done'
        WHERE material_no = :material_no AND component_name = :component_name
    ", [
            ':material_no' => $materialNo,
            ':component_name' => $componentName
        ]);
    }
    public function updateComponentsRMStock(string $materialNo, string $componentName, int $quantity): void
    {
        $isClip = in_array(
            $componentName,
            ['CLIP 25', 'CLIP 60', 'NUT WELD', 'NUT WELD (6)', 'NUT WELD (8)', 'NUT WELD (10)', 'NUT WELD (11.112)', 'NUT WELD (12)'],
            true
        );

        // ✅ Components Inventory
        if ($isClip) {
            $updateInventorySql = " UPDATE components_inventory SET rm_stocks = 0 WHERE components_name = :component_name ";
            $inventoryParams = [':component_name' => $componentName];
        } else {
            $updateInventorySql = " UPDATE components_inventory SET rm_stocks = 0 WHERE material_no = :material_no ";
            $inventoryParams = [':material_no' => $materialNo];

            $resetInventorySql = "
            UPDATE components_inventory
            SET actual_inventory = 0
            WHERE material_no = :material_no
              AND process_quantity IS NULL
        ";
            $this->db->Update($resetInventorySql, [':material_no' => $materialNo]);
        }
        $this->db->Update($updateInventorySql, $inventoryParams);

        // ✅ RM Warehouse
        if ($isClip) {
            $updateWarehouseSql = " UPDATE rm_warehouse SET status = 'done' WHERE component_name = :component_name ";
            $warehouseParams = [':component_name' => $componentName];
        } else {
            $updateWarehouseSql = " UPDATE rm_warehouse SET status = 'done' WHERE material_no = :material_no ";
            $warehouseParams = [':material_no' => $materialNo];
        }
        $this->db->Update($updateWarehouseSql, $warehouseParams);
    }
}
