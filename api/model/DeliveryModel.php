<?php
class DeliveryModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function getPendingDelivery($cutoff, $model)
    {
        $sql = " SELECT * FROM delivery_history WHERE date_loaded IS NULL AND created_at >= :cutoff  AND model=:model ";
        return $this->db->Select($sql, [':cutoff' => $cutoff, ':model' => $model]);
    }
    public function getTruck()
    {
        return $this->db->Select("SELECT * FROM truck");
    }
    public function getDeliveryHistory($model)
    {
        $sql = "SELECT * FROM delivery_history WHERE date_loaded IS NOT NULL AND model = :model";
        return $this->db->Select($sql, [':model' => $model]);
    }
    public function sku_delivery($input)
    {

        $existing = $this->db->SelectOne(
            "SELECT total_quantity FROM delivery_history WHERE id = :id",
            [':id' => $input['id']]
        );

        if (!$existing) {
            throw new Exception("Delivery record not found.");
        }

        $existingTotalQty   = (int)$existing['total_quantity'];
        $remainingTotalQty  = $existingTotalQty - (int)$input['qty_allocated'];

        if ($input['qty_allocated'] > $existingTotalQty) {
            throw new Exception("Allocated quantity exceeds total quantity.");
        }

        $affectedRows = 0;

        if (!empty($input['process']) && strtolower($input['process']) === 'stamping') {
            $this->updateComponentsStock(
                $input['material_no'],
                $input['material_description'],
                $input['qty_allocated'],
                $input['reference_no'],
                $input['created_at'],
                $input['process'],
                $input['model']
            );
        }

        // 3️⃣ Prevent negative stock in material_inventory
        if (in_array(strtoupper($input['model']), ['32XD', 'VIOS', 'INNOVA'])) {
            $updated = $this->db->Update(
                "UPDATE material_inventory
             SET quantity = CAST(quantity AS SIGNED) - :deduct
             WHERE material_no = :material_no
               AND CAST(quantity AS SIGNED) >= :deduct",
                [
                    ':deduct'      => $input['qty_allocated'],
                    ':material_no' => $input['material_no']
                ]
            );
        } else {
            $updated = $this->db->Update(
                "UPDATE material_inventory
             SET quantity = CAST(quantity AS SIGNED) - :deduct
             WHERE material_no = :material_no
               AND material_description = :material_description
               AND CAST(quantity AS SIGNED) >= :deduct",
                [
                    ':deduct'               => $input['qty_allocated'],
                    ':material_no'          => $input['material_no'],
                    ':material_description' => $input['material_description']
                ]
            );
        }

        if ($updated === 0) {
            throw new Exception("Insufficient stock for {$input['material_description']} ({$input['material_no']}). Cannot deduct {$input['qty_allocated']}.");
        }
        $affectedRows += $updated;

        // 4️⃣ Update current delivery row → DONE
        $updatedDelivery = $this->db->Update(
            "UPDATE delivery_history 
         SET quantity = :quantity, total_quantity = :qty_allocated, action = 'DONE', 
             status = 'DONE', truck = :truck, updated_at = NOW(), date_loaded = NOW() 
         WHERE id = :id",
            [
                ':quantity'      => $input['qty_allocated'],
                ':qty_allocated' => $input['qty_allocated'],
                ':truck'         => $input['truck'],
                ':id'            => $input['id']
            ]
        );
        $affectedRows += $updatedDelivery;

        // 5️⃣ If any total_quantity remains, insert a new pending row
        if ($remainingTotalQty > 0) {
            $datePart = substr($input['reference_no'], 0, 8);

            $latest = $this->db->SelectOne(
                "SELECT reference_no FROM delivery_history
             WHERE reference_no LIKE :prefix
             ORDER BY reference_no DESC
             LIMIT 1",
                [':prefix' => $datePart . '-%']
            );

            $nextSeq = 1;
            if ($latest) {
                $lastSeq = (int)substr($latest['reference_no'], -4);
                $nextSeq = $lastSeq + 1;
            }

            $newRef = $datePart . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            $inserted = $this->db->Insert(
                "INSERT INTO delivery_history (
                reference_no, model, material_no, material_description,
                quantity, supplement_order, total_quantity, status, section, truck,
                shift, lot_no, process, created_at, updated_at, date_needed, date_loaded, active
            ) VALUES (
                :reference_no, :model, :material_no, :material_description,
                0, :supplement_order, :total_quantity, 'pending', :section, NULL,
                :shift, :lot_no, :process, :created_at, :updated_at, :date_needed, NULL, :active
            )",
                [
                    ':reference_no'         => $newRef,
                    ':model'           => $input['model'],
                    ':material_no'          => $input['material_no'],
                    ':material_description' => $input['material_description'],
                    ':supplement_order'     => $input['supplement_order'],
                    ':total_quantity'       => $remainingTotalQty,
                    ':section'              => $input['section'],
                    ':shift'                => $input['shift'] !== '' ? $input['shift'] : null,
                    ':lot_no'               => $input['lot_no'] !== '' ? $input['lot_no'] : null,
                    ':process'              => $input['process'] !== '' ? $input['process'] : null,
                    ':created_at'           => $input['created_at'],
                    ':updated_at'           => $input['updated_at'],
                    ':date_needed'          => $input['date_needed'],
                    ':active'               => 1
                ]
            );

            $affectedRows += $inserted;
        }

        return $affectedRows; // ✅ real affected rows
    }
    private function updateComponentsStock(
        string $materialNo,
        string $materialDescription,
        int    $qtyAllocated,
        string $referenceNo,
        string $createdAt,
        string $process,
        string $model
    ): void {

        $targetComponents = [
            'CLIP 25',
            'CLIP 60',
            'NUT WELD',
            'NUT WELD (6)',
            'NUT WELD (8)',
            'NUT WELD (10)',
            'NUT WELD (11.112)',
            'NUT WELD (12)',
            'REINFORCEMENT'
        ]; // special cases by description

        $targetModel = ['VALERIE', 'PNR', 'APS', 'MILLIARD', 'KOMYO']; // model special case (material_no only)

        $dualMatchModel = ['INNOVA', '32XD', 'SEDAN', 'HATCHBACK']; // use material_no + components_name

        if (in_array($materialDescription, $targetComponents, true)) {
            // Case 1: Priority by components_name
            $updated = $this->db->Update(
                "UPDATE components_inventory
             SET actual_inventory = actual_inventory - :deduct
             WHERE components_name = :components_name
               AND actual_inventory >= :deduct",
                [
                    ':deduct'          => $qtyAllocated,
                    ':components_name' => $materialDescription,
                ]
            );

            if ($updated === 0) {
                throw new \Exception("Insufficient stock for {$materialDescription}. Cannot deduct {$qtyAllocated}.");
            }

            $components = $this->db->Select(
                "SELECT * 
             FROM components_inventory
             WHERE components_name = :components_name",
                [':components_name' => $materialDescription]
            );
        } elseif (in_array(strtoupper($model), $targetModel, true)) {
            // Case 2: Special models → by material_no
            $updated = $this->db->Update(
                "UPDATE components_inventory
             SET actual_inventory = actual_inventory - :deduct
             WHERE material_no = :material_no
               AND actual_inventory >= :deduct",
                [
                    ':deduct'      => $qtyAllocated,
                    ':material_no' => $materialNo,
                ]
            );

            if ($updated === 0) {
                throw new \Exception("Insufficient stock for {$materialDescription} ({$materialNo}). Cannot deduct {$qtyAllocated}.");
            }

            $components = $this->db->Select(
                "SELECT * FROM components_inventory
             WHERE material_no = :material_no",
                [':material_no' => $materialNo]
            );
        } elseif (in_array(strtoupper($model), $dualMatchModel, true)) {
            // Case 3: INNOVA, 32XD, SEDAN, HATCHBACK → require BOTH material_no + components_name
            $updated = $this->db->Update(
                "UPDATE components_inventory
             SET actual_inventory = actual_inventory - :deduct
             WHERE material_no = :material_no
               AND components_name = :components_name
               AND actual_inventory >= :deduct",
                [
                    ':deduct'          => $qtyAllocated,
                    ':material_no'     => $materialNo,
                    ':components_name' => $materialDescription,
                ]
            );

            if ($updated === 0) {
                throw new \Exception("Insufficient stock for {$materialDescription} ({$materialNo}). Cannot deduct {$qtyAllocated}.");
            }

            $components = $this->db->Select(
                "SELECT * FROM components_inventory
             WHERE material_no = :material_no
               AND components_name = :components_name",
                [
                    ':material_no'     => $materialNo,
                    ':components_name' => $materialDescription
                ]
            );
        } else {
            // Case 4: Default → by material_no only
            $updated = $this->db->Update(
                "UPDATE components_inventory
             SET actual_inventory = actual_inventory - :deduct
             WHERE material_no = :material_no
               AND actual_inventory >= :deduct",
                [
                    ':deduct'      => $qtyAllocated,
                    ':material_no' => $materialNo,
                ]
            );

            if ($updated === 0) {
                throw new \Exception("Insufficient stock for {$materialDescription} ({$materialNo}). Cannot deduct {$qtyAllocated}.");
            }

            $components = $this->db->Select(
                "SELECT * FROM components_inventory
             WHERE material_no = :material_no",
                [':material_no' => $materialNo]
            );
        }

        if (empty($components)) {
            throw new \Exception("No component inventory rows found for {$materialDescription}");
        }

        foreach ($components as $component) {
            $newStock  = (int) $component['actual_inventory'];
            $newStatus = $this->determineStatus($component, $newStock);

            if (!in_array($newStatus, ['Normal', 'Maximum'], true)) {
                $upsertResult = $this->upsertIssuedRawMaterial(
                    $materialNo,
                    $component['components_name'],
                    $newStock,
                    $newStatus,
                    $referenceNo,
                    $process,
                    $model
                );
                error_log("Upsert result: " . ($upsertResult ? 'success' : 'fail'));
                if (!$upsertResult) {
                    throw new \Exception("Failed to upsert issued raw material for {$component['components_name']}.");
                }
            }
        }
    }
    private function determineStatus(array $component, int $stock): string
    {
        $critical = (int) $component['critical'];
        $minimum  = (int) $component['minimum'];
        $reorder  = (int) $component['reorder'];
        $normal   = (int) $component['normal'];
        $max      = (int) $component['maximum_inventory'];

        return match (true) {
            $stock <= $critical                        => 'Critical',
            $stock <= $minimum && $stock > $critical   => 'Minimum',
            $stock <= $reorder && $stock > $minimum    => 'Reorder',
            $stock > $reorder && $stock <= $max        => 'Normal',
            $stock > $max                              => 'Maximum',
        };
    }


    public function upsertIssuedRawMaterial(
        string $materialNo,
        string $componentName,
        int    $quantity,
        string $status,
        string $referenceNo,
        string $process,
        string $model
    ): array {

        $debug = []; // collect logs

        try {
            $debug[] = "UPSERT CALLED with: " . json_encode([
                'material_no'    => $materialNo,
                'component_name' => $componentName,
                'quantity'       => $quantity,
                'status'         => $status,
                'reference_no'   => $referenceNo,
                'process'        => $process,
                'model'          => $model
            ]);

            // --- SELECT
            $query = "
            SELECT ir.id, ci.process
            FROM issued_rawmaterials ir
            INNER JOIN components_inventory ci 
                ON ci.material_no = ir.material_no
               AND ci.components_name = ir.component_name
            WHERE ir.component_name = :component_name
              AND ir.material_no    = :material_no
        ";

            $params = [
                ':component_name' => $componentName,
                ':material_no'    => $materialNo,
            ];

            $existing = $this->db->SelectOne($query, $params);
            $debug[] = "SELECT params: " . json_encode($params);
            $debug[] = "SELECT result: " . json_encode($existing);

            if ($existing && isset($existing['id'])) {
                $ciProcess = $existing['process'] ?? $process;
                if (strtolower((string) $ciProcess) !== 'supplied') {
                    $ciProcess = null;
                }

                $updateParams = [
                    ':quantity' => $quantity,
                    ':status'   => $status,
                    ':type'     => $ciProcess,
                    ':id'       => $existing['id'],
                ];
                $updated = $this->db->Update(
                    "UPDATE issued_rawmaterials
                 SET quantity = :quantity,
                     status   = :status,
                     type     = :type
                 WHERE id = :id",
                    $updateParams
                );
                $debug[] = "UPDATE params: " . json_encode($updateParams);
                $debug[] = "UPDATE result: " . var_export($updated, true);

                return ['success' => $updated !== false, 'debug' => $debug];
            }

            // --- INSERT
            $ciRow = $this->db->SelectOne(
                "SELECT process 
             FROM components_inventory
             WHERE material_no = :material_no
               AND components_name = :component_name
             LIMIT 1",
                [
                    ':material_no'    => $materialNo,
                    ':component_name' => $componentName,
                ]
            );

            $debug[] = "CI Row: " . json_encode($ciRow);

            $ciProcess = $ciRow['process'] ?? $process;
            $ciProcess = trim((string) $ciProcess);
            if (strtolower($ciProcess) !== 'supplied') {
                $ciProcess = null;
            }

            $insertParams = [
                ':material_no'    => $materialNo,
                ':component_name' => $componentName,
                ':quantity'       => $quantity,
                ':status'         => $status,
                ':reference_no'   => $referenceNo,
                ':model'          => $model,
                ':type'           => $ciProcess,
            ];

            $inserted = $this->db->Insert(
                "INSERT INTO issued_rawmaterials (
                material_no, component_name, quantity,
                status, reference_no, issued_at, model, type
             ) VALUES (
                :material_no, :component_name, :quantity,
                :status, :reference_no, NOW(), :model, :type
             )",
                $insertParams
            );

            $debug[] = "INSERT params: " . json_encode($insertParams);
            $debug[] = "INSERT result: " . var_export($inserted, true);

            return ['success' => $inserted !== false, 'debug' => $debug];
        } catch (\Exception $e) {
            $debug[] = "ERROR: " . $e->getMessage();
            return ['success' => false, 'debug' => $debug];
        }
    }

    public function component_delivery(array $input): int
    {
        $id                  = (int)($input['id'] ?? 0);
        $truck               = $input['truck'] ?? '';
        $material_no         = $input['material_no'] ?? '';
        $model          = $input['model'] ?? '';
        $material_description = $input['material_description'] ?? '';
        $total_quantity      = (int)($input['total_quantity'] ?? 0);
        $qty_allocated       = (int)($input['qty_allocated'] ?? 0);
        $reference_no        = $input['reference_no'] ?? '';
        $supplement_order    = (int)($input['supplement_order'] ?? 0);
        $status              = $input['status'] ?? 'pending';
        $section             = $input['section'] ?? 'DELIVERY';
        $shift               = $input['shift'] ?? '';
        $lot_no              = $input['lot_no'] ?? '';
        $process             = $input['process'] ?? null;
        $created_at          = $input['created_at'] ?? date('Y-m-d H:i:s');
        $updated_at          = $input['updated_at'] ?? date('Y-m-d H:i:s');
        $date_needed         = $input['date_needed'] ?? null;
        $existing = $this->db->SelectOne(
            "SELECT total_quantity FROM delivery_history WHERE id = :id",
            [':id' => $id]
        );

        if (!$existing) {
            throw new Exception("Delivery record not found.");
        }

        $existingTotalQty   = (int)$existing['total_quantity'];
        $remainingTotalQty  = $existingTotalQty - $qty_allocated;

        if ($qty_allocated > $existingTotalQty) {
            throw new Exception("Allocated quantity exceeds total quantity.");
        }

        $affectedRows = 0;


        // if ($model && in_array(strtoupper($model), ['VALERIE', 'PNR'])) {
        //     $this->updateComponentsStock(
        //         $material_no,
        //         $material_description,
        //         $qty_allocated,
        //         $reference_no,
        //         $created_at,
        //         $process,
        //         $model
        //     );
        // }


        if ($model && in_array(strtoupper($model), ['VALERIE', 'PNR'])) {
            $this->updateComponentsStock(
                $material_no,
                $material_description,
                $qty_allocated,
                $reference_no,
                $created_at,
                $process,
                $model
            );
        } else {
            $updated = $this->db->Update(
                "UPDATE components_inventory
         SET actual_inventory = CAST(actual_inventory AS SIGNED) - :deduct
         WHERE material_no = :material_no
           AND components_name = :material_description",
                [
                    ':deduct'               => $qty_allocated,
                    ':material_no'          => $material_no,
                    ':material_description' => $material_description
                ]
            );

            if ($updated === 0) {
                throw new Exception("Insufficient stock for {$material_description} ({$material_no}). Cannot deduct {$qty_allocated}.");
            }
            $affectedRows += $updated;
        }


        // 4️⃣ Update the current delivery row to mark it as DONE
        $updatedDelivery = $this->db->Update(
            "UPDATE delivery_history 
         SET quantity = :quantity, total_quantity = :qty_allocated, action = 'DONE', 
             status = 'DONE', truck = :truck, updated_at = NOW(), date_loaded = NOW() 
         WHERE id = :id",
            [
                ':quantity'       => $qty_allocated,
                ':qty_allocated'  => $qty_allocated,
                ':truck'          => $truck,
                ':id'             => $id
            ]
        );
        $affectedRows += $updatedDelivery;

        // 5️⃣ If any total_quantity remains, insert a new pending row
        if ($remainingTotalQty > 0) {
            $datePart = substr($reference_no, 0, 8);

            $latest = $this->db->SelectOne(
                "SELECT reference_no FROM delivery_history
             WHERE reference_no LIKE :prefix
             ORDER BY reference_no DESC
             LIMIT 1",
                [':prefix' => $datePart . '-%']
            );

            $nextSeq = 1;
            if ($latest) {
                $lastSeq = (int)substr($latest['reference_no'], -4);
                $nextSeq = $lastSeq + 1;
            }

            $newRef = $datePart . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            $inserted = $this->db->Insert(
                "INSERT INTO delivery_history (
                reference_no, model, material_no, material_description,
                quantity, supplement_order, total_quantity, status, section, truck,
                shift, lot_no, process, created_at, updated_at, date_needed, date_loaded, active
            ) VALUES (
                :reference_no, :model, :material_no, :material_description,
                0, :supplement_order, :total_quantity, 'pending', :section, NULL,
                :shift, :lot_no, :process, :created_at, :updated_at, :date_needed, NULL, :active
            )",
                [
                    ':reference_no'         => $reference_no,
                    ':model'           => $model,
                    ':material_no'          => $material_no,
                    ':material_description' => $material_description,
                    ':supplement_order'     => $supplement_order,
                    ':total_quantity'       => $remainingTotalQty,
                    ':section'              => $section,
                    ':shift'                => $shift !== '' ? $shift : null,
                    ':lot_no'               => $lot_no !== '' ? $lot_no : null,
                    ':process'              => $process !== '' ? $process : null,
                    ':created_at'           => $created_at,
                    ':updated_at'           => $updated_at,
                    ':date_needed'          => $date_needed,
                    ':active'               => 1
                ]
            );

            $affectedRows += $inserted;
        }

        return $affectedRows; // ✅ return real affected rows
    }
}
