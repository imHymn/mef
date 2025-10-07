<?php
class FinishingModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function getSpecificData_assigned($full_name, $model)
    {
        $sql = " SELECT * FROM rework_finishing WHERE assembly_person_incharge =:full_name  AND assembly_timeout IS NULL AND model = :model ";
        return $this->db->Select($sql, [':full_name' => $full_name, ':model' => $model]);
    }
    public function getAllData_assigned($model)
    {
        $sql = "SELECT * FROM rework_finishing WHERE   assembly_person_incharge IS NOT NULL AND model=:model";
        return $this->db->Select($sql, [':model' => $model]);
    }
    public function getAllModelData_assigned($full_name)
    {
        $sql = "SELECT * FROM rework_finishing WHERE   assembly_person_incharge=:person AND assembly_timeout is NULL ";
        return $this->db->Select($sql, [':person' => $full_name]);
    }
    public function assignOperator($itemId, $person, $byOrder)
    {
        $sql = "UPDATE rework_finishing SET assembly_person_incharge = :person, by_order = :by_order WHERE id = :id";
        return $this->db->Update($sql, [':person' => $person, ':by_order' => $byOrder, ':id' => $itemId]);
    }
    public function getData_toassign($model)
    {
        $sql = " SELECT * FROM rework_finishing WHERE assembly_person_incharge IS NULL AND model = :model ";
        return $this->db->Select($sql, [':model' => $model]);
    }
    public function timeinOperator(int $id, string $full_name, string $time_in)
    {
        $sql = "UPDATE rework_finishing SET assembly_person_incharge = :full_name, assembly_timein = :time_in WHERE id = :id";
        $params = [':id' => $id, ':full_name' => $full_name, ':time_in' => $time_in];

        $updated = $this->db->Update($sql, $params);

        return $updated ? true : "Failed to update rework_finishing.";
    }
    public function updateReworkFinishingTimeout(array $data): bool
    {
        $sql = "UPDATE rework_finishing 
        SET assembly_person_incharge = :full_name, 
            `replace` = :replace, 
            rework = :rework,
            assembly_pending_quantity = :assembly_pending_quantity,
            assembly_timeout = :time_out 
        WHERE id = :id";

        $params = [
            ':full_name' => $data['full_name'],
            ':id' => $data['id'],
            ':time_out' => $data['time_out'],
            ':replace' => $data['replace'],
            ':rework' => $data['rework'],
            ':assembly_pending_quantity' => $data['assembly_pending_quantity']
        ];

        return $this->db->Update($sql, $params);
    }
    public function getGroupedFinishingByReference(string $reference_no, int $rework_no): ?array
    {
        $sql = "SELECT 
                model,
                material_no,
                material_description,
                shift,
                lot_no,
                date_needed,
                SUM(`replace`) AS total_replace,
                SUM(`rework`) AS total_rework,
                SUM(`assembly_pending_quantity`) AS total_assembly_pending_quantity,
                MAX(quantity) AS total_quantity
            FROM rework_finishing
            WHERE reference_no = :reference_no AND rework_no =:rework_no
            GROUP BY reference_no, model, material_no, material_description, shift, lot_no, date_needed";

        $params = [':reference_no' => $reference_no, ':rework_no' => $rework_no];

        return $this->db->SelectOne($sql, $params);
    }
    public function updateComponentInventoryAfterReplace(string $material_no, int $total_replace, string $time_out, string $reference_no): void
    {
        // Step 1: Get components
        $sqlComponents = "SELECT id, components_name, usage_type, actual_inventory,
                             critical, minimum, reorder, normal, maximum_inventory
                      FROM components_inventory 
                      WHERE material_no = :material_no";

        $components = $this->db->Select($sqlComponents, [':material_no' => $material_no]);

        if (!$components) {
            throw new \Exception("No components found for material_no: $material_no");
        }

        // Step 2: Check if reference_no already exists in issued_rawmaterials
        $existsSql = "SELECT COUNT(*) as count FROM issued_rawmaterials WHERE reference_no = :reference_no";
        $existsResult = $this->db->SelectOne($existsSql, [':reference_no' => $reference_no]);

        $referenceExists = $existsResult && (int)$existsResult['count'] > 0;

        // Step 3: Update each component's inventory
        foreach ($components as $component) {
            $componentId = $component['id'];
            $componentsName = $component['components_name'];
            $usageType = (int)$component['usage_type'];
            $currentInventory = (int)$component['actual_inventory'];

            $returnQty = $total_replace * $usageType;
            $newInventory = $currentInventory - $returnQty;

            // Update inventory
            $sqlUpdateInventory = "UPDATE components_inventory 
                               SET actual_inventory = :new_inventory, updated_at = :date
                               WHERE material_no = :material_no AND components_name = :components_name";

            $paramsUpdateInventory = [
                ':new_inventory' => $newInventory,
                ':material_no' => $material_no,
                ':components_name' => $componentsName,
                ':date' => $time_out
            ];

            $this->db->Update($sqlUpdateInventory, $paramsUpdateInventory);

            // ✅ Determine new status
            $critical = (int)$component['critical'];
            $minimum = (int)$component['minimum'];
            $reorder = (int)$component['reorder'];
            $normal = (int)$component['normal'];
            $maximum = (int)$component['maximum_inventory'];

            if ($newInventory > $maximum) {
                $status = 'Maximum';
            } elseif ($newInventory >= $normal && $newInventory <= $maximum) {
                $status = 'Normal';
            } elseif ($newInventory >= $reorder && $newInventory < $normal) {
                $status = 'Reorder';
            } elseif ($newInventory >= $minimum && $newInventory < $reorder) {
                $status = 'Minimum';
            } elseif ($newInventory < $minimum) {
                $status = 'Critical';
            }

            // 🚨 Only insert if not existing AND status is Critical, Minimum, or Reorder
            if (!$referenceExists && in_array($status, ['Critical', 'Minimum', 'Reorder'])) {
                $insertSql = "INSERT INTO issued_rawmaterials (
                            material_no, component_name, quantity, status, reference_no, issued_at
                          ) VALUES (
                             :material_no, :component_name, :quantity, :status, :reference_no, NOW()
                          )";

                $insertParams = [

                    ':material_no' => $material_no,
                    ':component_name' => $componentsName,
                    ':quantity' => $newInventory,
                    ':status' => $status,
                    ':reference_no' => $reference_no
                ];

                $this->db->Insert($insertSql, $insertParams);
            }
        }
    }
    public function markReworkFinishingAsDone(string $reference_no): void
    {
        $sql = "UPDATE rework_finishing 
            SET status = 'done' 
            WHERE reference_no = :reference_no";

        $this->db->Update($sql, [':reference_no' => $reference_no]);
    }
    public function insertReworkQC(string $reference_no, array $result, int $total, string $time_out, string $assembly_section, ?int $cycle_time): void
    {
        $latestRework = $this->db->SelectOne(
            "SELECT rework_no
         FROM rework_qc
         WHERE reference_no = :ref AND material_no = :mat
         ORDER BY rework_no DESC
         LIMIT 1",
            [
                'ref' => $reference_no,
                'mat' => $result['material_no']
            ]
        );

        $nextReworkNo = 1;

        if ($latestRework) {
            $currentReworkNo = (int) $latestRework['rework_no'];

            $totals = $this->db->SelectOne(
                "SELECT SUM(qc_quantity) AS total_done,
                    MAX(quantity)   AS max_quantity
             FROM rework_qc
             WHERE reference_no = :ref
               AND material_no = :mat
               AND rework_no = :rw",
                [
                    'ref' => $reference_no,
                    'mat' => $result['material_no'],
                    'rw'  => $currentReworkNo
                ]
            );

            $total_done   = (int) $totals['total_done'];
            $max_quantity = (int) $totals['max_quantity'];

            // Step 3: Set rework_no based on whether limit is met
            $nextReworkNo = ($total_done >= $max_quantity)
                ? $currentReworkNo + 1
                : $currentReworkNo;
        }

        // Step 4: Insert new rework_qc row
        $sql = "INSERT INTO rework_qc (
                rework_no,
                reference_no, model, material_no, material_description,
                shift, lot_no, quantity,cycle_time,
                qc_quantity, qc_person_incharge,
                qc_timein, qc_timeout,assembly_section,
                status, section, date_needed, created_at
            ) VALUES (
                :rework_no,
                :reference_no, :model, :material_no, :material_description,
                :shift, :lot_no, :quantity,:cycle_time,
                :qc_quantity, :qc_person_incharge,
                :qc_timein, :qc_timeout,:assembly_section,
                :status, :section, :date_needed, :created_at
            )";

        $params = [
            ':rework_no'            => $nextReworkNo,
            ':reference_no'         => $reference_no,
            ':model'                => $result['model'],
            ':material_no'          => $result['material_no'],
            ':material_description' => $result['material_description'],
            ':shift'                => $result['shift'],
            ':lot_no'               => $result['lot_no'],
            ':quantity'             => $total,
            ':qc_quantity'          => $total,
            ':qc_person_incharge'   => null,
            ':qc_timein'            => null,
            ':qc_timeout'           => null,
            ':status'               => 'pending',
            ':section'              => 'qc',
            ':date_needed'          => $result['date_needed'],
            ':created_at'           => $time_out,
            ':assembly_section'     => $assembly_section,
            ':cycle_time'           => $cycle_time ?? null
        ];

        $this->db->Insert($sql, $params);
    }
    public function duplicateReworkFinishing(int $id, string $rework_no, int $replace, int $rework, int $inputQty, string $time_out, ?int $cycle_time): int
    {
        $selectSql = "SELECT * FROM rework_finishing WHERE id = :id";
        $selectParams = [':id' => $id];

        $row = $this->db->SelectOne($selectSql, $selectParams);

        if (!$row) {
            throw new \Exception("No record found to duplicate for ID: $id");
        }

        $newData = [
            ':itemID' => $id,
            ':reference_no' => $row['reference_no'],
            ':model' => $row['model'],
            ':material_no' => $row['material_no'],
            ':material_description' => $row['material_description'],
            ':shift' => $row['shift'],
            ':lot_no' => $row['lot_no'],
            ':rework_no' => $rework_no,
            ':replace' =>  $replace,
            ':rework' => $rework,
            ':quantity' => $inputQty,
            ':assembly_quantity' => $inputQty,
            ':assembly_pending_quantity' => $row['assembly_pending_quantity'],
            ':assembly_person_incharge' => $row['assembly_person_incharge'],
            ':assembly_timein' => null,
            ':assembly_timeout' => null,
            ':status' => 'continue',
            ':section' => 'assembly',
            ':date_needed' => $row['date_needed'],
            ':assembly_section' => $row['assembly_section'],
            ':fuel_type' => $row['fuel_type'],
            ':created_at' => $time_out,
            ':cycle_time' => $cycle_time ?? null,
            ':customer_id' => $row['customer_id'],
            ':by_order' => $row['by_order']
        ];

        $insertSql = "INSERT INTO rework_finishing (
        itemID,
        reference_no, model, material_no, material_description,
        shift, lot_no, `replace`, rework, quantity,rework_no,
        assembly_quantity, assembly_pending_quantity, assembly_person_incharge,
        assembly_timein, assembly_timeout,assembly_section,by_order,
        status, section, date_needed, created_at,cycle_time,fuel_type, customer_id
    ) VALUES (
        :itemID, :reference_no, :model, :material_no, :material_description,
        :shift, :lot_no, :replace, :rework, :quantity,:rework_no,
        :assembly_quantity, :assembly_pending_quantity, :assembly_person_incharge,
        :assembly_timein, :assembly_timeout,:assembly_section,:by_order,
        :status, :section, :date_needed, :created_at,:cycle_time,:fuel_type ,:customer_id
    )";

        return $this->db->Insert($insertSql, $newData); // returns inserted row count
    }
}
