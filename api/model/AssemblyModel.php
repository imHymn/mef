<?php
class AssemblyModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function getMaterialComponent($material_no)
    {
        $sql = "SELECT * FROM `components_inventory` WHERE material_no ='$material_no'";
        return $this->db->Select($sql);
    }
    public function getData_toassign(string $cutoff, string $model): array
    {
        // DELIVERY FORMS
        $sqlDelivery = " SELECT * FROM delivery_form WHERE created_at >= :cutoff AND model = :model AND person_incharge IS NULL ";
        $delivery = $this->db->Select($sqlDelivery, [
            ':cutoff' => $cutoff,
            ':model'  => $model
        ]);

        $sqlAssembly = " SELECT * FROM assembly_list WHERE created_at >= :cutoff AND model = :model AND person_incharge IS NULL ";
        $assembly = $this->db->Select($sqlAssembly, [
            ':cutoff' => $cutoff,
            ':model'  => $model
        ]);

        return [
            "delivery" => $delivery,
            "assembly" => $assembly
        ];
    }
    public function getAllData_assigned($cutoff, $model)
    {

        $sqlDelivery = " SELECT * FROM delivery_form WHERE  created_at >= :cutoff AND model = :model AND person_incharge IS NOT NULL ";
        $delivery = $this->db->Select($sqlDelivery, [
            ':cutoff' => $cutoff,
            ':model'  => $model
        ]);

        $sqlAssembly = " SELECT * FROM assembly_list WHERE created_at >= :cutoff AND model = :model AND person_incharge IS NOT NULL ";
        $assembly = $this->db->Select($sqlAssembly, [
            ':cutoff' => $cutoff,
            ':model'  => $model
        ]);

        return [
            "delivery" => $delivery,
            "assembly" => $assembly
        ];
    }
    public function getAllModelData_assigned($full_name, $model)
    {
        $sql = "
        SELECT 
            d.id,
            d.material_no,
            d.material_description,
            d.sub_component,
            d.assembly_process,
            d.reference_no,
            d.process_no,
            d.by_order,
                d.model
        FROM delivery_form d
        WHERE d.person_incharge = :full_name
       
          AND NOT EXISTS (
              SELECT 1
              FROM assembly_list a
              WHERE a.itemID = d.id
                AND a.process_no = d.process_no
                AND a.time_in IS NOT NULL
                AND a.time_out IS NOT NULL
          )
        ORDER BY d.by_order ASC
    ";

        return $this->db->Select($sql, [':full_name' => $full_name]);
    }
    public function getSpecificData_assigned($full_name, $model)
    {
        $sql = "
        SELECT 
            d.id,
            d.material_no,
            d.material_description,
            d.sub_component,
            d.assembly_process,
            d.reference_no,
            d.process_no,
            d.by_order
        FROM delivery_form d
        WHERE d.person_incharge = :full_name
          AND d.model = :model
          AND NOT EXISTS (
              SELECT 1
              FROM assembly_list a
              WHERE a.itemID = d.id
                AND a.process_no = d.process_no
                AND a.time_in IS NOT NULL
                AND a.time_out IS NOT NULL
          )
        ORDER BY d.by_order ASC
    ";

        return $this->db->Select($sql, [':full_name' => $full_name, ':model' => $model]);
    }
    public function assignOperator($itemId, $person, $byOrder)
    {
        $sql = "UPDATE delivery_form SET person_incharge = :person, by_order = :by_order WHERE id = :id";
        return $this->db->Update($sql, [':person' => $person, ':by_order' => $byOrder, ':id' => $itemId]);
    }
    public function getLatestPendingQuantity(string $reference_no): int
    {
        $sql = "SELECT pending_quantity FROM assembly_list WHERE reference_no = :reference_no ORDER BY time_in DESC LIMIT 1";

        $params = [':reference_no' => $reference_no];
        $result = $this->db->SelectOne($sql, $params);

        return isset($result['pending_quantity']) ? (int)$result['pending_quantity'] : 0;
    }
    public function insertAssemblyRecord(array $data): int
    {
        // Step 1: Check for existing record in delivery_form
        $checkSql = "SELECT created_at FROM assembly_list
        WHERE material_no = :material_no
          AND material_description = :material_description
          AND lot_no = :lot_no
          AND duplicated = :duplicated
          AND sub_component = :sub_component
          AND assembly_process = :assembly_process
        ORDER BY created_at DESC
        LIMIT 1";

        $existing = $this->db->SelectOne($checkSql, [
            ':material_no'         => $data['material_no'],
            ':material_description' => $data['material_description'],
            ':lot_no'              => $data['lot_no'],
            ':duplicated'          => $data['duplicated'],
            ':sub_component'       => $data['sub_component'],
            ':assembly_process'    => $data['assembly_process'],
        ]);

        // Step 2: Use found created_at as time_in if available
        $timeIn = $existing['created_at'] ?? $data['time_in'];

        // Step 3: Perform insert
        $sql = "INSERT INTO assembly_list (
        itemID, model, shift, lot_no, date_needed, reference_no,
        material_no, material_description, pending_quantity, total_quantity,
        person_incharge, time_in, status, section, created_at,total_process,
        process, assembly_section, assembly_section_no, process_no,
    variant, sub_component, assembly_process, duplicated,manpower,cycle_time,fuel_type
    ) VALUES (
        :itemID, :model, :shift, :lot_no, :date_needed, :reference_no,
        :material_no, :material_description, :pending_quantity, :total_qty,
        :person_incharge, :time_in, :status, :section, :created_at,:total_process,
        :process, :assembly_section, :assembly_section_no, :process_no,
         :variant, :sub_component, :assembly_process, :duplicated,:manpower,:cycle_time,:fuel_type
    )";

        return $this->db->Insert($sql, [
            ':itemID'               => $data['itemID'],
            ':model'                => $data['model'],
            ':shift'                => $data['shift'],
            ':lot_no'               => $data['lot_no'],
            ':date_needed'         => $data['date_needed'],
            ':reference_no'        => $data['reference_no'],
            ':material_no'         => $data['material_no'],
            ':material_description' => $data['material_description'],
            ':pending_quantity'    => $data['pending_quantity'],
            ':total_qty'           => $data['total_qty'],
            ':person_incharge'     => $data['full_name'],
            ':time_in'             => $timeIn,
            ':status'              => $data['status'],
            ':fuel_type'              => $data['fuel_type'],
            ':section'             => $data['section'],
            ':created_at'          => $timeIn,
            ':process'             => $data['process'],
            ':assembly_section'    => $data['assembly_section'],
            ':assembly_section_no' =>  $data['assembly_section_no'],
            ':process_no'          => $data['process_no'],
            ':total_process'          => $data['total_process'],

            ':variant'             => $data['variant'],
            ':sub_component'       => $data['sub_component'],
            ':assembly_process'    =>  $data['assembly_process'],
            ':duplicated'          =>  $data['duplicated'],
            ':manpower'          =>  $data['manpower'],
            ':cycle_time'       => $data['cycle_time']
        ]);
    }

    public function getPendingAssembly($id)
    {
        $sql = "SELECT assembly_pending, total_quantity
                        FROM delivery_form 
                        WHERE id = :id 
                        ORDER BY created_at DESC 
                        LIMIT 1";
        return $this->db->SelectOne($sql, [':id' => $id]);
    }
    public function updateAssemblyListTimeout(array $data): bool
    {
        $subComponentIsNull = empty(trim($data['sub_component'] ?? ''));
        $assemblyProcessIsNull = empty(trim($data['assembly_process'] ?? ''));
        $manpower = isset($data['manpower']) ? (int)$data['manpower'] : 1;
        $duplicated = isset($data['duplicated']) ? (int)$data['duplicated'] : 1;
        $process_no = isset($data['process_no']) ? (int)$data['process_no'] : 1;

        // Shared WHERE clause base
        $whereClause = "material_no = :material_no
        AND material_description = :material_description
         AND process_no=:process_no";

        $whereParams = [
            ':material_no'          => $data['material_no'],
            ':material_description' => $data['material_description'],

            ':process_no'               => $data['process_no'],
        ];



        // STEP 1: Check for other unfinished duplicates
        $checkOthersSql = "SELECT COUNT(*) as count FROM assembly_list
        WHERE $whereClause AND reference_no != :reference_no AND time_out IS NULL";

        $checkParams = $whereParams + [':reference_no' => $data['reference_no']];
        $otherUnfinished = $this->db->SelectOne($checkOthersSql, $checkParams);

        // STEP 2: If others are unfinished, update only current (add manpower conditionally)
        if ($otherUnfinished && (int)$otherUnfinished['count'] > 0) {
            $sqlUpdate = "UPDATE assembly_list SET 
            done_quantity = :done_quantity,
            pending_quantity = :pending_quantity,
            status = :status,
            section = :section,
            time_out = :time_out
        WHERE reference_no = :reference_no";

            $paramsUpdate = [
                ':done_quantity'    => $data['done_quantity'],
                ':pending_quantity' => $data['pending_quantity'],
                ':status'           => $data['status'],
                ':section'          => $data['section'],
                ':time_out'         => $data['time_out'],
                ':reference_no'     => $data['reference_no'],
            ];

            // ✅ If manpower > 1, include it in WHERE and params
            if ($manpower > 1) {
                $sqlUpdate .= " AND duplicated = :duplicated";
                $paramsUpdate[':duplicated'] = $duplicated;
            }

            return $this->db->Update($sqlUpdate, $paramsUpdate);
        }

        // STEP 3: All duplicates are done — update only the current row by ID
        $updateAllSql = "UPDATE assembly_list SET 
        done_quantity = :done_quantity,
        pending_quantity = :pending_quantity,
        status = :status,
        section = :section,
        time_out = :time_out
    WHERE $whereClause AND itemID = :itemID";

        $updateParams = $whereParams + [
            ':done_quantity'    => $data['done_quantity'],
            ':pending_quantity' => $data['pending_quantity'],
            ':status'           => $data['status'],
            ':section'          => $data['section'],
            ':time_out'         => date('Y-m-d H:i:s'),
            ':itemID' => $data['id'] ?? null,

        ];

        return $this->db->Update($updateAllSql, $updateParams);
    }
    public function updateDeliveryFormPending($id, $remainingPending)
    {
        $sql = "UPDATE delivery_form SET section = :section, status = :status, assembly_pending = :remainingPending WHERE id = :id";
        $params = [
            ':section' => 'QC',
            ':status' => 'done',
            ':remainingPending' => $remainingPending,
            ':id' => $id,
        ];

        return $this->db->Update($sql, $params);
    }
    public function checkDuplicate(
        ?string $material_no,
        ?string $material_description,
        ?string $lot_no,
        ?string $manpower,
        ?string $assembly_process,
        ?string $sub_component,
        string $reference_no,
        string $assembly_section,
        int $process_no
    ): array {
        $baseParts = explode('-', $reference_no);
        if (count($baseParts) < 2) return [];

        $baseReference = $baseParts[0] . '-' . $baseParts[1];

        $params = [
            ':base_ref' => $baseReference . '%',
            ':assembly_section' => $assembly_section,
            ':process_no' => $process_no,
        ];

        $sql = "SELECT 
                reference_no,
                SUM(done_quantity) AS total_done,
                MAX(total_quantity) AS total_required
            FROM assembly_list
            WHERE reference_no LIKE :base_ref
              AND assembly_section = :assembly_section AND process_no=:process_no";



        $sql .= " GROUP BY reference_no ORDER BY reference_no";

        return $this->db->Select($sql, $params) ?? [];
    }
    public function duplicateDeliveryFormWithPendingUpdate(
        int $id,
        string $time_out,
        int $remainingPending,
        string $assembly_section,
        int $assembly_section_no,
        string $reference_no,
        int $process_no
    ): bool {
        // Step 1: Fetch the original row by ID
        $selectSql = "SELECT * FROM delivery_form WHERE id = :id";
        $row = $this->db->SelectOne($selectSql, [':id' => $id]);

        if (!$row) {
            return false;
        }

        // Step 2: Fetch the current highest duplicated count for the reference
        $dupSql = "SELECT MAX(duplicated) AS max_dup FROM delivery_form WHERE reference_no = :ref AND process_no = :process_no";
        $dupResult = $this->db->SelectOne($dupSql, [':ref' => $row['reference_no'], ':process_no' => $row['process_no']]);
        $duplicatedCount = (int)($dupResult['max_dup'] ?? 0) + 1;

        // Step 3: Prepare the new row for insertion
        $newRow = [
            'reference_no'         => $row['reference_no'],
            'material_no'          => $row['material_no'],
            'material_description' => $row['material_description'],
            'model'           => $row['model'],
            'quantity'             => $row['quantity'],
            'total_quantity'       => $row['total_quantity'],
            'assembly_pending'     => $remainingPending,
            'supplement_order'     => $row['supplement_order'],
            'variant'              => $row['variant'],
            'date_needed'          => $row['date_needed'],
            'lot_no'               => $row['lot_no'],
            'shift'                => $row['shift'],
            'manpower'                => $row['manpower'],
            'total_process'                => $row['total_process'],
            'status'               => 'continue',
            'section'              => 'ASSEMBLY',
            'created_at'          => $row['created_at'],
            'assembly_section'     => $assembly_section,
            'assembly_section_no'  => $assembly_section_no,
            'process_no'           => $row['process_no'],
            'sub_component'        => $row['sub_component'],
            'assembly_process'     => $row['assembly_process'],
            'duplicated'           => $duplicatedCount,
            'cycle_time'           => $row['cycle_time'],
            'pi_kbn_quantity' => $row['pi_kbn_quantity'],
            'pi_kbn_pieces'   => $row['pi_kbn_pieces'],
            'fuel_type'       => $row['fuel_type'],
            'customer_id'       => $row['customer_id'],
            'person_incharge'      => $row['person_incharge'],
            'by_order'             => $row['by_order'],
        ];

        // Step 4: Insert with duplicated count
        $insertSql = "INSERT INTO delivery_form (
        reference_no, material_no, material_description, model,
        quantity, total_quantity, assembly_pending, supplement_order, variant,
        date_needed, lot_no, shift, status, section, created_at,
        assembly_section, assembly_section_no, process_no, customer_id,by_order,
        sub_component, assembly_process, duplicated,manpower,total_process,cycle_time,pi_kbn_quantity,pi_kbn_pieces,fuel_type,person_incharge
    ) VALUES (
        :reference_no, :material_no, :material_description, :model,
        :quantity, :total_quantity, :assembly_pending, :supplement_order, :variant,
        :date_needed, :lot_no, :shift, :status, :section, :created_at,
        :assembly_section, :assembly_section_no, :process_no, :customer_id, :by_order,
        :sub_component, :assembly_process, :duplicated,:manpower,:total_process,:cycle_time,:pi_kbn_quantity,:pi_kbn_pieces ,:fuel_type ,:person_incharge
    )";

        return $this->db->Insert($insertSql, $newRow) !== false;
    }
    public function deductComponentInventory(
        string $materialNo,
        string $referenceNo,
        int $totalQty,
        string $timeIn,
        int $manpower,
        string $model
    ): void {
        $components = $this->fetchComponents($materialNo);

        $actualQtyUsed = $totalQty;
        $processedKeys = []; // track unique material_no + components_name

        foreach ($components as $component) {
            $key = $materialNo . '||' . $component['components_name'];
            if (isset($processedKeys[$key])) {
                continue; // skip duplicate
            }
            $processedKeys[$key] = true;

            $newInventory = $this->updateActualInventory(
                $component,
                $materialNo,
                $actualQtyUsed,
                $timeIn
            );

            $status = $this->determineStatus($component, $newInventory);

            if (in_array($status, ['Critical', 'Minimum', 'Reorder'], true)) {
                $this->upsertIssuedRawMaterial(
                    $materialNo,
                    $component['components_name'],
                    $newInventory,
                    $status,
                    $referenceNo,
                    $model,
                    $component['process']
                );
            }
        }
    }
    public function moveToQCList(array $data): ?array
    {
        $qty = (int)$data['total_quantity'];
        $insertedIds = [];

        $sqlInsert = "INSERT INTO qc_list
        (model, shift, lot_no, date_needed, reference_no, customer_id, material_no, material_description, 
         total_quantity, status, section, assembly_section, created_at, variant, cycle_time, 
         pi_kbn_quantity, pi_kbn_pieces, fuel_type)
        VALUES 
        (:model, :shift, :lot_no, :date_needed, :reference_no, :customer_id, :material_no, :material_description, 
         :total_quantity, :status, :section, :assembly_section, :created_at, :variant, :cycle_time, 
         :pi_kbn_quantity, :pi_kbn_pieces, :fuel_type)";

        $piQty    = (int)$data['pi_kbn_quantity'];
        $piPieces = (int)$data['pi_kbn_pieces'];

        // ✅ Handle multiple customer IDs (comma-separated)
        $customerIds = array_map('trim', explode(',', $data['customer_id']));

        if (!empty($piQty) && !empty($piPieces)) {
            // ✅ Pair pipieces with customerIds one-to-one
            for ($i = 0; $i < $piPieces; $i++) {
                $custId = $customerIds[$i] ?? $customerIds[0]; // fallback if less customers

                $params = [
                    ':model' => $data['model'],
                    ':shift' => $data['shift'],
                    ':lot_no' => $data['lot_no'],
                    ':variant' => $data['variant'],
                    ':date_needed' => $data['date_needed'],
                    ':reference_no' => $custId,
                    ':customer_id' => $custId,
                    ':material_no' => $data['material_no'],
                    ':material_description' => $data['material_description'],
                    ':assembly_section' => 'qc',
                    ':total_quantity' => $piQty,
                    ':status' => 'pending',
                    ':section' => 'qc',
                    ':created_at' => $data['created_at'],
                    ':cycle_time' => $data['cycle_time'],
                    ':pi_kbn_quantity' => $piQty,
                    ':pi_kbn_pieces'   => $piPieces,
                    ':fuel_type' => $data['fuel_type'],
                ];

                $insertedId = $this->db->Insert($sqlInsert, $params);
                if ($insertedId) $insertedIds[] = $insertedId;
            }
        } else {
            // Default batch splitting
            $batchQty = 30; // default

            // ✅ Override for specific models
            if (in_array($data['model'], ['KSWW HPI', 'KSWW K1AK', 'K2VN', 'KAWASAKI'])) {
                $batchQty = 100;
            }

            $batches = intdiv($qty, $batchQty);
            $remainder = $qty % $batchQty;

            $custCount = count($customerIds);
            $batchIndex = 0;

            for ($i = 0; $i < $batches; $i++) {
                $custId = $customerIds[$batchIndex % $custCount];
                $batchIndex++;

                $params = [
                    ':model' => $data['model'],
                    ':shift' => $data['shift'],
                    ':lot_no' => $data['lot_no'],
                    ':variant' => $data['variant'],
                    ':date_needed' => $data['date_needed'],
                    ':reference_no' => $data['reference_no'],
                    ':customer_id' => $custId,
                    ':material_no' => $data['material_no'],
                    ':material_description' => $data['material_description'],
                    ':assembly_section' => $data['assembly_section'],
                    ':total_quantity' => $batchQty,
                    ':status' => 'pending',
                    ':section' => 'qc',
                    ':created_at' => $data['created_at'],
                    ':cycle_time' => $data['cycle_time'],
                    ':pi_kbn_quantity' => $piQty,
                    ':pi_kbn_pieces'   => $piPieces,
                    ':fuel_type' => $data['fuel_type'],
                ];

                $insertedId = $this->db->Insert($sqlInsert, $params);
                if ($insertedId) $insertedIds[] = $insertedId;
            }

            if ($remainder > 0) {
                $custId = $customerIds[$batchIndex % $custCount];
                $params[':total_quantity'] = $remainder;
                $params[':customer_id'] = $custId;

                $insertedId = $this->db->Insert($sqlInsert, $params);
                if ($insertedId) $insertedIds[] = $insertedId;
            }
        }

        // ✅ Update statements still run once per reference_no
        $sqlUpdateDelivery = "UPDATE delivery_form 
                          SET section = 'QC' 
                          WHERE reference_no = :reference_no";
        $this->db->Update($sqlUpdateDelivery, [':reference_no' => $data['reference_no']]);

        $sqlUpdateAssembly = "UPDATE assembly_list 
                          SET status = 'done', section = 'qc' 
                          WHERE reference_no = :reference_no";
        $this->db->Update($sqlUpdateAssembly, [':reference_no' => $data['reference_no']]);

        return $insertedIds;
    }
    public function getTotalDoneAndRequired(string $reference_no, string $assembly_section, int $process_no): ?array
    {
        $params = [
            ':reference_no' => $reference_no,
            ':assembly_section' => $assembly_section,
            ':process_no' => $process_no
        ];

        $sql = "SELECT SUM(done_quantity) AS total_done, MAX(total_quantity) AS total_required 
            FROM assembly_list 
            WHERE reference_no = :reference_no 
              AND assembly_section = :assembly_section AND process_no=:process_no";


        $result = $this->db->SelectOne($sql, $params);

        if (!$result || !is_array($result)) {
            return null;
        }

        return $result;
    }
    public function updatePendingQuantity($reference_no, $process_no)
    {
        // Sanitize inputs
        $reference_no = trim($reference_no);

        $sql = "UPDATE delivery_form SET assembly_pending = 0 WHERE reference_no = :reference_no AND process_no=:process_no";
        $params = [':reference_no' => $reference_no, ':process_no' => $process_no];


        $updated = $this->db->Update($sql, $params);

        return $updated ? true : "Failed to update delivery_form.";
    }
    private function fetchComponents(string $materialNo): array
    {
        $sql = "
            SELECT  id, components_name, usage_type, actual_inventory,
                    critical, minimum, reorder, normal, maximum_inventory,process
            FROM    components_inventory
            WHERE   material_no = :material_no
        ";
        $rows = $this->db->Select($sql, [':material_no' => $materialNo]);

        if (!$rows) {
            throw new \RuntimeException("No components found for material_no: {$materialNo}");
        }
        return $rows;
    }


    private function updateActualInventory(
        array  $component,
        string $materialNo,
        float    $totalQty,
        string $timeIn
    ): float {
        $componentName    = $component['components_name'];
        $usageType        = (int) $component['usage_type'];
        $currentInventory = (float) $component['actual_inventory'];
        $deductQty        = $totalQty * $usageType;
        $newInventory     = max(0, $currentInventory - $deductQty);

        $isClip = in_array($componentName, ['CLIP 25', 'CLIP 60', 'NUT WELD', 'NUT WELD', 'NUT WELD (6)', 'NUT WELD (8)', 'NUT WELD (10)', 'NUT WELD (11.112)', 'NUT WELD (12)', 'REINFORCEMENT'], true);

        $sql = "
        UPDATE components_inventory
        SET    actual_inventory = :new_inventory,
               updated_at       = :date
        WHERE  components_name = :components_name";

        $params = [
            ':new_inventory'   => $newInventory,
            ':date'            => $timeIn,
            ':components_name' => $componentName
        ];

        if (!$isClip) {
            $sql .= " AND material_no = :material_no";
            $params[':material_no'] = $materialNo;
        }

        $this->db->Update($sql, $params);

        return $newInventory;
    }



    private function determineStatus(array $component, float $stock): string
    {
        $critical = (float) $component['critical'];
        $minimum  = (float) $component['minimum'];
        $reorder  = (float) $component['reorder'];
        $normal   = (float) $component['normal'];
        $max      = (float) $component['maximum_inventory'];

        return match (true) {
            $stock <= $critical                        => 'Critical',
            $stock <= $minimum && $stock > $critical   => 'Minimum',
            $stock <= $reorder && $stock > $minimum    => 'Reorder',
            $stock > $reorder && $stock <= $max        => 'Normal',
            $stock > $max                              => 'Maximum',
        };
    }
    private function upsertIssuedRawMaterial(
        string $materialNo,
        string $componentName,
        float    $quantity,
        string $status,
        string $referenceNo,
        string $model,
        ?string $process
    ): void {

        $isClip = in_array($componentName, ['CLIP 25', 'CLIP 60', 'NUT WELD', 'NUT WELD', 'NUT WELD (6)', 'NUT WELD (8)', 'NUT WELD (10)', 'NUT WELD (11.112)', 'NUT WELD (12)', 'REINFORCEMENT'], true);

        $query = "
        SELECT id
        FROM issued_rawmaterials
        WHERE component_name = :component_name
          AND process IS NULL AND delivered_at IS NULL";

        $params = [':component_name' => $componentName];

        if (!$isClip) {
            $query .= " AND material_no = :material_no";
            $params[':material_no'] = $materialNo;
        }

        $query .= " ORDER BY issued_at ASC LIMIT 1";

        $existing = $this->db->SelectOne($query, $params);

        if ($existing && isset($existing['id'])) {
            $this->db->Update(
                "UPDATE issued_rawmaterials
                SET quantity = :quantity,
                 status   = :status
             WHERE id = :id",
                [
                    ':quantity' => $quantity,
                    ':status'   => $status,
                    ':id'       => $existing['id'],
                ]
            );
        } else {

            if ($quantity <= 0) {
                throw new \Exception("Invalid quantity: $quantity");
            }

            if (empty($referenceNo)) {
                throw new \Exception("Missing reference number");
            }

            $this->db->Insert(
                "INSERT INTO issued_rawmaterials (
                material_no, component_name, quantity,
                status, reference_no, issued_at,model,`type`
             ) VALUES (
                :material_no, :component_name, :quantity,
                :status, :reference_no, NOW(),:model,:type
             )",
                [
                    ':material_no'    => $materialNo,
                    ':component_name' => $componentName,
                    ':quantity'       => $quantity,
                    ':status'         => $status,
                    ':reference_no'   => $referenceNo,
                    ':model'   => $model,
                    ':type' => $process
                ]
            );
        }
    }
    public function getUserByIdAndName(string $userId, string $name): ?array
    {
        $sql = "SELECT * FROM users WHERE user_id = :user_id AND name = :name LIMIT 1";
        $params = [
            ':user_id' => $userId,
            ':name'    => $name
        ];

        $user = $this->db->SelectOne($sql, $params);
        return $user ?: null;
    }
    public function resetAssembly($id)
    {
        // 1. Update delivery_form
        $updateSql = "UPDATE delivery_form SET section = 'DELIVERY' , person_incharge = NULL WHERE id = :id";
        $this->db->Update($updateSql, [':id' => $id]);

        // 2. Delete from assembly_list
        $deleteSql = "DELETE FROM assembly_list WHERE itemID = :id";
        $this->db->Delete($deleteSql, [':id' => $id]);

        return true;
    }
    public function resetFinishing($id)
    {
        // ✅ Correct SQL syntax
        $updateSql = "UPDATE rework_finishing 
                  SET assembly_person_incharge = NULL, assembly_timein = NULL 
                  WHERE id = :id";
        $this->db->Update($updateSql, [':id' => $id]);

        return true;
    }
    public function getAllAssemblyData($model)
    {
        $assembly = $this->db->Select(
            "SELECT * FROM assembly_list WHERE time_out IS NOT NULL AND model = :model",
            ['model' => $model]
        );

        $stamping = $this->db->Select(
            "SELECT * FROM stamping WHERE section IN('FINISHING','L300 ASSY') AND time_out IS NOT NULL AND model = :model",
            ['model' => $model]
        );

        return [
            'assembly' => $assembly,
            'stamping' => $stamping
        ];
    }
}
