<?php
class PlannerModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function getPreviousLot($model)
    {
        $sql = "SELECT lot_no FROM delivery_form WHERE model = :model ORDER BY lot_no DESC LIMIT 1";
        return $this->db->Select($sql, [':model' => $model]);
    }
    public function getFormHistory($model)
    {
        $sql1 = "SELECT * FROM delivery_history WHERE model = :model";
        $deliveryForm = $this->db->Select($sql1, [':model' => $model]);

        $sql2 = "SELECT * FROM customer_form WHERE model = :model";
        $customerForm = $this->db->Select($sql2, [':model' => $model]);

        $sql3 = "SELECT * FROM qc_list WHERE model = :model";
        $qcForm = $this->db->Select($sql3, [':model' => $model]);

        return [
            'delivery_form' => $deliveryForm,
            'customer_form' => $customerForm,
            'qcForm' => $qcForm
        ];
    }
    public function deleteMultipleForm($column, $value)
    {
        $sql = "DELETE FROM delivery_form WHERE $column = :value";
        $this->db->Delete($sql, ['value' => $value]);

        $sql2 = "DELETE FROM delivery_history WHERE $column = :value";
        $this->db->Delete($sql2, ['value' => $value]);

        $sql3 = "DELETE FROM qc_list WHERE $column = :value";
        $this->db->Delete($sql3, ['value' => $value]);
    }
    public function getMaterial($model, $customer)
    {
        $sql = "SELECT * FROM material_inventory WHERE model = :model AND customer_name = :customer_name";
        $params = [':model'     => $model, ':customer_name'  => $customer,];
        return $this->db->Select($sql, $params);
    }
    public function submitForm($input, $today, $currentTime)
    {
        $insertedCount = 0;
        $qcCount = 0;
        $lastNumber = (int)substr($this->selectReferenceNo($today), -4);
        $inventoryList = $this->recheckInventory($input);

        foreach ($input as $index => $item) {
            $requiredQty = (int)$item['total_quantity'];
            $currentInventory = $inventoryList[$index]['quantity'] ?? 0;
            $item['fuel_type'] = $item['fuel_type'] ?? $item['fuelType'] ?? null;
            $process = $item['process'];

            // Decode and normalize input arrays
            $assemblySections = !empty($item['assembly_section']) ? json_decode($item['assembly_section'], true) : [];
            $subComponent     = !empty($item['sub_component'])     ? json_decode($item['sub_component'], true)     : [];
            $assemblyProcess  = !empty($item['assembly_process'])  ? json_decode($item['assembly_process'], true)  : [];
            $manpower         = !empty($item['manpower'])          ? json_decode($item['manpower'], true)          : [];

            $assemblySections = is_array($assemblySections) ? $assemblySections : [];
            $subComponent     = is_array($subComponent)     ? $subComponent     : [];
            $assemblyProcess  = is_array($assemblyProcess)  ? $assemblyProcess  : [];
            $manpower         = is_array($manpower)         ? $manpower         : [];

            $assemblySections = array_map([$this, 'normalizeQuotedValue'], $assemblySections);
            $subComponent     = array_map([$this, 'normalizeQuotedValue'], $subComponent);
            $assemblyProcess  = array_map([$this, 'normalizeQuotedValue'], $assemblyProcess);
            $manpower         = array_map([$this, 'normalizeQuotedValue'], $manpower);

            $assemblyProcessTime = json_decode($item['assemblyProcesstime'] ?? '[]', true);
            $assemblyProcessTime = is_array($assemblyProcessTime) ? $assemblyProcessTime : [];
            $assemblyProcessTime = array_map([$this, 'normalizeQuotedValue'], $assemblyProcessTime);

            $lastNumber++;
            $baseRef = $today . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

            // ✅ Always insert into delivery_history
            $this->processDeliveryHistory(
                $item,
                $baseRef,
                $today,
                $lastNumber,
                $currentTime,
                $assemblySections,
                $subComponent,
                $assemblyProcess,
                $process
            );
            $assignedRef = $this->getDeliveryReferenceNo($item, $currentTime);
            $customerId  = $this->getCustomerId($item, $currentTime);
            if ($process === 'stamping') {
                continue;
            }
            $insertedCount += $this->processAssemblyItem($item, $assignedRef, $customerId, $currentTime, $assemblySections, $subComponent, $assemblyProcess, $manpower, $assemblyProcessTime, $process);
        }

        return [
            'success'     => true,
            'inserted'    => $insertedCount,
            'qc_inserted' => $qcCount
        ];
    }
    private function processDeliveryHistory(
        array $item,
        string $baseRef,
        string $today,
        int &$lastNo,
        string $timestamp,
        array $sections,
        array $subComponents,
        array $assemblyProcesses,
        ?string $process
    ): int {
        $requiredQty   = (int)$item['total_quantity'];
        $supplementQty = isset($item['supplement_order']) ? (int)$item['supplement_order'] : 0;
        $inserted      = 0;

        $sql = "INSERT INTO delivery_history (
        reference_no, model, material_no, material_description,
        quantity, supplement_order, total_quantity, status, section, variant,
        shift, lot_no, created_at, updated_at, date_needed, process,
        pi_kbn_pieces, pi_kbn_quantity, fuel_type
    ) VALUES (
        :reference_no, :model, :material_no, :material_description,
        :quantity, :supplement_order, :total_quantity, :status, :section, :variant,
        :shift, :lot_no, :created_at, :updated_at, :date_needed, :process,
        :pi_kbn_pieces, :pi_kbn_quantity, :fuel_type
    )";

        $sharedParams = [
            ':model'           => $item['model'] ?? '',
            ':material_no'          => $item['material_no'],
            ':material_description' => $item['material_description'],
            ':status'               => $item['status'] ?? '',
            ':section'              => $item['section'] ?? '',
            ':variant'              => $item['variant'] ?? null,
            ':shift'                => $item['shift'] ?? '',
            ':created_at'           => $timestamp,
            ':updated_at'           => $timestamp,
            ':date_needed'          => $item['date_needed'],
            ':pi_kbn_pieces'        => $item['pi_kbn_pieces'] ?? 0,
            ':pi_kbn_quantity'      => $item['pi_kbn_quantity'] ?? 0,
            ':fuel_type'            => $item['fuel_type'] ?? null,
            ':process' => !empty($process) ? $process : null

        ];

        $piQty    = (int)($item['pi_kbn_quantity'] ?? 0);
        $piPieces = (int)($item['pi_kbn_pieces'] ?? 0);

        // 🔹 If PI splitting is active
        if ($piQty > 0 && $piPieces > 0) {
            for ($i = 0; $i < $piPieces; $i++) {
                $paramsPI = array_merge($sharedParams, [
                    ':reference_no'     => $today . '-' . str_pad(++$lastNo, 4, '0', STR_PAD_LEFT),
                    ':quantity'         => $piQty,
                    ':supplement_order' => 0,
                    ':total_quantity'   => $piQty,
                    ':lot_no'           => null,
                ]);

                if ($this->db->Insert($sql, $paramsPI) !== false) {
                    $inserted++;
                }
            }
        } else {
            // 🔹 Insert main row (normal mode)
            $paramsMain = array_merge($sharedParams, [
                ':reference_no'     => $baseRef,
                ':quantity'         => $requiredQty,
                ':supplement_order' => 0,
                ':total_quantity'   => $requiredQty,
                ':lot_no'           => $item['lot_no'] ?? null,
            ]);

            if ($this->db->Insert($sql, $paramsMain) !== false) {
                $inserted++;
            }
        }

        // 🔹 Insert supplement row if any
        if ($supplementQty > 0) {
            $paramsSupp = array_merge($sharedParams, [
                ':reference_no'     => $today . '-' . str_pad(++$lastNo, 4, '0', STR_PAD_LEFT),
                ':quantity'         => 0,
                ':supplement_order' => $supplementQty,
                ':total_quantity'   => $supplementQty,
                ':lot_no'           => 'S' . ($item['lot_no'] ?? null),
            ]);

            if ($this->db->Insert($sql, $paramsSupp) !== false) {
                $inserted++;
            }
        }

        return $inserted;
    }

    private function processAssemblyItem(
        array $item,
        string $baseRef,
        string $customerId,
        string $timestamp,
        array $sections,
        array $subComponents,
        array $processes,
        array $manpower,
        array $assemblyProcessTime,
        ?string $process
    ): int {
        $sql = "INSERT INTO delivery_form (
        reference_no, customer_id, model, material_no, material_description,
        quantity, supplement_order, total_quantity, status, section, variant,
        shift, lot_no, process, created_at, updated_at, date_needed, duplicated,
        assembly_section, assembly_section_no, assembly_process, sub_component,
        process_no, total_process, manpower, cycle_time, pi_kbn_quantity, pi_kbn_pieces, fuel_type
    ) VALUES (
        :reference_no, :customer_id, :model, :material_no, :material_description,
        :quantity, :supplement_order, :total_quantity, :status, :section, :variant,
        :shift, :lot_no, :process, :created_at, :updated_at, :date_needed, :duplicated,
        :assembly_section, :assembly_section_no, :assembly_process, :sub_component,
        :process_no, :total_process, :manpower, :cycle_time, :pi_kbn_quantity, :pi_kbn_pieces, :fuel_type
    )";
        $inserted = 0;
        $total = count($sections);
        $requiredQty = (int)$item['total_quantity'];

        // Loop over sections
        for ($i = 0; $i < $total; $i++) {
            $processManpower = max(1, (int)($manpower[$i] ?? 1));
            $processNo = $i + 1;

            // Only duplicate if manpower > 1
            $manpowerLoop = $processManpower > 1 ? $processManpower : 1;

            for ($m = 0; $m < $manpowerLoop; $m++) {
                $manpowerRef = $processManpower > 1 ? $baseRef . '-' . ($m + 1) : $baseRef;

                $quantityValue = $item['quantity'];

                $params = [
                    ':reference_no'         => $manpowerRef,
                    ':customer_id'          => $customerId,
                    ':model'                => $item['model'] ?? '',
                    ':material_no'          => $item['material_no'],
                    ':material_description' => $item['material_description'],
                    ':quantity'             => $quantityValue,
                    ':supplement_order'     => isset($item['supplement_order']) ? (int)$item['supplement_order'] : null,
                    ':total_quantity'       => $requiredQty,
                    ':status'               => 'pending',
                    ':section'              => 'DELIVERY',
                    ':variant'              => $item['variant'] ?? null,
                    ':shift'                => $item['shift'] ?? '',
                    ':lot_no'               => $item['lot_no'] ?? null,
                    ':process'              => $process ?? null,
                    ':created_at'           => $timestamp,
                    ':updated_at'           => $timestamp,
                    ':date_needed'          => $item['date_needed'],
                    ':duplicated'           => $processManpower > 1 ? 1 : 0,
                    ':assembly_section'     => $sections[$i] ?? null,
                    ':assembly_section_no'  => $total,
                    ':assembly_process'     => $processes[$i] ?? null,
                    ':sub_component'        => $subComponents[$i] ?? null,
                    ':process_no'           => $processNo,
                    ':total_process'        => $item['assemblyTotalprocess'],
                    ':manpower'             => $processManpower,
                    ':cycle_time'           => $assemblyProcessTime[$i] ?? null,
                    ':pi_kbn_quantity'      => $item['pi_kbn_quantity'] ?? 0,
                    ':pi_kbn_pieces'        => $item['pi_kbn_pieces'] ?? 0,
                    ':fuel_type'            => $item['fuel_type'] ?? null
                ];

                if ($this->db->Insert($sql, $params) !== false) {
                    $inserted++;
                }
            }
        }

        return $inserted;
    }

    private function getDeliveryReferenceNo(array $item, string $createdAt): ?string
    {
        $sql = "SELECT reference_no 
              FROM delivery_history
             WHERE material_no = :material_no
               AND material_description = :material_description
               AND model = :model
               AND created_at = :created_at
             ORDER BY id DESC
             ";

        return $this->db->SelectOne($sql, [
            'material_no'          => $item['material_no'],
            'material_description' => $item['material_description'],
            'model'                => $item['model'],
            'created_at'           => $createdAt
        ])['reference_no'] ?? null;
    }
    private function getCustomerId(array $item, string $createdAt): ?string
    {
        $sql = "SELECT reference_no 
              FROM delivery_history
             WHERE material_no = :material_no
               AND material_description = :material_description
               AND model = :model
               AND created_at = :created_at
             ORDER BY id ASC";  // keep consistent ordering

        $rows = $this->db->Select($sql, [
            'material_no'          => $item['material_no'],
            'material_description' => $item['material_description'],
            'model'                => $item['model'],
            'created_at'           => $createdAt
        ]);

        if (empty($rows)) {
            return null;
        }

        // Flatten into comma-separated list
        $refs = array_column($rows, 'reference_no');
        return implode(',', $refs);
    }
    public function selectReferenceNo(string $today): string
    {

        $sql = " SELECT reference_no FROM delivery_history WHERE reference_no LIKE :today_pattern ORDER BY CAST(SUBSTRING_INDEX(reference_no, '-', -1) AS UNSIGNED) DESC LIMIT 1 ";
        $result = $this->db->Select($sql, [':today_pattern' => $today . '-%']);

        if (!empty($result)) {
            $lastRef = $result[0]['reference_no'];
            $lastNumber = (int)substr($lastRef, -4); // Extract the last 4 digits
        } else {
            $lastNumber = 0;
        }

        $nextRef = $today . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        return $nextRef;
    }
    public function recheckInventory(array $input): array
    {
        $results = [];

        foreach ($input as $item) {
            $sql = "SELECT quantity FROM material_inventory WHERE material_no = :material_no AND material_description = :material_description AND model = :model LIMIT 1";
            $invParams = [
                ':material_no' => $item['material_no'],
                ':material_description' => $item['material_description'],
                ':model' => $item['model']
            ];

            $inventory = $this->db->Select($sql, $invParams);
            $results[] = [
                'material_no' => $item['material_no'],
                'material_description' => $item['material_description'],
                'model' => $item['model'],
                'quantity' => !empty($inventory) ? (int)$inventory[0]['quantity'] : null
            ];
        }
        return $results;
    }
    private function normalizeQuotedValue(?string $value): string
    {
        if ($value === null) return '';

        $value = trim($value);

        // Remove leading/trailing quotes: ", ', \" and escaped quotes
        $value = preg_replace('/^(\\\\[\'"]|[\'"])+|([\'"]|\\\\[\'"])+$/', '', $value);

        return trim($value);
    }
    public function getComponents(string $model, string $customerName): array
    {
        $sql = "SELECT * FROM components_inventory WHERE model = :customerName ORDER BY id ASC";
        return $this->db->Select($sql, [':customerName' => $customerName]);
    }

    public function recheckComponentInventory(array $input): array
    {
        $results = [];

        foreach ($input as $item) {
            $sql = "SELECT MAX(actual_inventory) AS quantity 
                FROM components_inventory 
                WHERE components_name = :components_name";

            $params = [
                ':components_name' => $item['components_name'],
            ];

            $inventory = $this->db->Select($sql, $params);
            $availableQty = isset($inventory[0]['quantity']) ? (int)$inventory[0]['quantity'] : 0;

            $resultItem = [
                'components_name' => $item['components_name'],
                'quantity_requested' => $item['quantity'],
                'available_quantity' => $availableQty,
            ];

            if ($availableQty < $item['quantity']) {
                $resultItem['status'] = 'insufficient';
                $resultItem['reason'] = $availableQty === 0
                    ? 'No stock available in components_inventory.'
                    : 'Requested quantity exceeds available stock.';
            } else {
                $resultItem['status'] = 'sufficient';
            }

            $results[] = $resultItem;
        }

        return $results;
    }
    public function selectCustomerReferenceNo(string $today): string
    {

        $sql = "
    SELECT reference_no 
    FROM delivery_history 
    WHERE reference_no LIKE :today_pattern
    ORDER BY CAST(SUBSTRING_INDEX(reference_no, '-', -1) AS UNSIGNED) DESC
    LIMIT 1
";
        $result = $this->db->Select($sql, [':today_pattern' => $today . '-%']);

        if (!empty($result)) {
            $lastRef = $result[0]['reference_no'];
            $lastNumber = (int)substr($lastRef, -4); // Extract the last 4 digits
        } else {
            $lastNumber = 0;
        }

        $nextRef = $today . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        return $nextRef;
    }
    public function processCustomerForm(array $input, string $currentDateTime): array
    {
        $insertedCount = 0;

        foreach ($input as $item) {
            $reference_no = $item['reference_no'] ?? null;

            // if (!$reference_no) {
            //     continue; // Skip if no reference number is provided
            // }

            $requiredQty  = (int)$item['total_quantity'];

            $params = [
                ':reference_no'    => $reference_no,
                ':model'      => $item['model'],
                ':material_no'     => $item['material_no'],
                ':components_name' => $item['components_name'],
                ':quantity'        => $item['quantity'],
                ':total_quantity'  => $requiredQty,
                ':status'          => $item['status'],
                ':section'         => $item['section'],
                ':created_at'      => $currentDateTime,
                ':updated_at'      => $currentDateTime,
                ':date_needed'     => $item['date_needed'],
                ':process'         => 'stamping'
            ];

            // Only insert into delivery_history
            $deliveryHistorySql = "
            INSERT INTO delivery_history (
                reference_no, model, material_no, material_description,
                quantity, total_quantity, status, section,
                created_at, updated_at, date_needed, process
            ) VALUES (
                :reference_no, :model, :material_no, :components_name,
                :quantity, :total_quantity, :status, :section,
                :created_at, :updated_at, :date_needed, :process
            )";

            if ($this->db->Insert($deliveryHistorySql, $params) !== false) {
                $insertedCount++;
            }
        }

        return [
            'success'  => true,
            'inserted' => $insertedCount
        ];
    }
    public function upsertIssuedRawMaterial(
        string $materialNo,
        string $componentName,
        int    $quantity,
        string $status,
        string $referenceNo,
        string $process,
        string $model
    ): bool {
        try {
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

            if ($existing && isset($existing['id'])) {
                $ciProcess = $existing['process'] ?? $process;
                // If process is 'supplied', set to null
                if (strtolower((string) $ciProcess) !== 'supplied') {
                    $ciProcess = null;
                }

                // Update existing
                $updated = $this->db->Update(
                    "UPDATE issued_rawmaterials
                 SET quantity = :quantity,
                     status   = :status,
                     type     = :type
                 WHERE id = :id",
                    [
                        ':quantity' => $quantity,
                        ':status'   => $status,
                        ':type'     => $ciProcess,
                        ':id'       => $existing['id'],
                    ]
                );
                return $updated !== false;
            } else {
                // Fetch process from components_inventory
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

                $ciProcess = $ciRow['process'] ?? $process;
                $ciProcess = trim((string) $ciProcess); // remove spaces
                if (strtolower($ciProcess) !== 'supplied') {
                    $ciProcess = null;
                }

                // Insert new
                $inserted = $this->db->Insert(
                    "INSERT INTO issued_rawmaterials (
                    material_no, component_name, quantity,
                    status, reference_no, issued_at, model, type
                 ) VALUES (
                    :material_no, :component_name, :quantity,
                    :status, :reference_no, NOW(), :model, :type
                 )",
                    [
                        ':material_no'    => $materialNo,
                        ':component_name' => $componentName,
                        ':quantity'       => $quantity,
                        ':status'         => $status,
                        ':reference_no'   => $referenceNo,
                        ':model'          => $model,
                        ':type'           => $ciProcess,
                    ]
                );
                return $inserted !== false;
            }
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
