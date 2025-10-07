<?php
class QCModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function getTodoList(string $cutoff, string $modelName): array
    {
        $sql = " SELECT * FROM qc_list WHERE status = 'pending' AND section = 'qc' AND created_at >= :cutoff AND model =:model ";
        return $this->db->Select($sql, [':cutoff' => $cutoff, ':model' => $modelName]);
    }
    public function timeinOperator(int $id, string $name, string $time_in): bool
    {
        $sql = "UPDATE qc_list SET person_incharge = :name, time_in = :time_in WHERE id = :id";
        $params = [
            ':id'      => $id,
            ':name'    => $name,
            ':time_in' => $time_in,
        ];

        return $this->db->Update($sql, $params);
    }
    public function updateQCListTimeout(array $data): bool
    {
        $sql = "UPDATE qc_list 
            SET 
                done_quantity = :quantity,
                pending_quantity = :pending_quantity,
                good = :good,
                no_good = :no_good,
                rework = :rework,
                `replace` = :replace,
                time_out = :time_out,
                person_incharge = :name
            WHERE id = :id";

        $params = [
            ':id' => $data['id'],
            ':pending_quantity' => $data['pending_quantity'],
            ':quantity' => $data['quantity'],
            ':good' => $data['good'],
            ':no_good' => $data['no_good'],
            ':rework' => $data['rework'],
            ':replace' => $data['replace'],
            ':time_out' => $data['time_out'],
            ':name' => $data['name'],
        ];

        return $this->db->Update($sql, $params);
    }
    public function getQCTotalSummary(string $reference_no): ?array
    {
        $sql = "SELECT 
                SUM(done_quantity) AS total_done, 
                SUM(pending_quantity) AS total_pending, 
                SUM(good) AS total_good,
                SUM(no_good) AS total_no_good,
                SUM(rework) AS total_rework,
                SUM(`replace`) AS total_replace, 
                MAX(total_quantity) AS total_required 
            FROM qc_list 
            WHERE reference_no = :reference_no";

        $params = [':reference_no' => $reference_no];

        return $this->db->SelectOne($sql, $params);
    }

    public function insertReworkAssembly(array $data): int
    {
        $params = [
            'ref' => $data['reference_no'],
            'mat' => $data['material_no']
        ];

        // Step 1: Get latest rework_no only (no grouping)
        $latest = $this->db->SelectOne(
            "SELECT rework_no
         FROM rework_finishing
         WHERE reference_no = :ref AND material_no = :mat
         ORDER BY rework_no DESC
         LIMIT 1",
            $params
        );

        $nextReworkNo = 1;

        if ($latest) {
            $currentReworkNo = (int)$latest['rework_no'];

            // Step 2: Get total_handled and max_quantity for that specific rework_no
            $totals = $this->db->SelectOne(
                "SELECT 
                SUM(`replace` + rework) AS total_handled,
                MAX(quantity) AS max_quantity
             FROM rework_finishing
             WHERE reference_no = :ref AND material_no = :mat AND rework_no = :rw",
                [
                    'ref' => $data['reference_no'],
                    'mat' => $data['material_no'],
                    'rw'  => $currentReworkNo
                ]
            );

            $total_handled = (int)$totals['total_handled'];
            $max_quantity  = (int)$totals['max_quantity'];

            $nextReworkNo = ($total_handled >= $max_quantity)
                ? $currentReworkNo + 1
                : $currentReworkNo;
        }


        $sql = "INSERT INTO rework_finishing (
            rework_no,
            itemID, model, material_no, material_description,
            shift, lot_no, `replace`, rework,cycle_time,
            quantity, assembly_quantity, date_needed,
            reference_no, created_at, status, section,assembly_section,fuel_type
        ) VALUES (
            :rework_no,
            :itemID, :model, :material_no, :material_description,
            :shift, :lot_no, :replace, :rework,:cycle_time,
            :quantity, :assembly_quantity, :date_needed,
            :reference_no, :created_at, :status, :section,:assembly_section,:fuel_type
        )";

        return $this->db->Insert($sql, [
            ':rework_no'         => $nextReworkNo,
            ':itemID'            => $data['id'],
            ':model'             => $data['model'],
            ':material_no'       => $data['material_no'],
            ':material_description' => $data['material_description'],
            ':shift'             => $data['shift'],
            ':lot_no'            => $data['lot_no'],
            ':replace'           => $data['total_replace'],
            ':rework'            => $data['total_rework'],
            ':quantity'          => $data['total_no_good'],
            ':assembly_quantity' => $data['total_no_good'],
            ':date_needed'       => $data['date_needed'],
            ':reference_no'      => $data['reference_no'],
            ':created_at'        => $data['time_out'],
            ':fuel_type'        => $data['fuel_type'] ?? null,
            ':status'            => 'pending',
            ':section'           => 'assembly',
            ':assembly_section'        => 'FINISHING',
            ':cycle_time'        => $data['cycle_time']
        ]);
    }
    public function moveToFGWarehouse(array $data): bool
    {
        // Insert into fg_warehouse
        $insertFG = "INSERT INTO fg_warehouse (
        reference_no, material_no, material_description, model, quantity, total_quantity,
        lot_no, shift, date_needed, section, status, created_at, part_type
    ) VALUES (
        :reference_no, :material_no, :material_description, :model, :quantity, :total_quantity,
        :lot_no, :shift, :date_needed, :section, :status, :created_at  , :part_type
    )";

        $paramsFG = [
            ':reference_no' => $data['customer_id'] ?? $data['reference_no'],
            ':material_no' => $data['material_no'],
            ':material_description' => $data['material_description'],
            ':model' => $data['model'],
            ':quantity' => $data['total_good'],
            ':total_quantity' => $data['total_quantity'],
            ':lot_no' => $data['lot_no'],
            ':shift' => $data['shift'],
            ':date_needed' => $data['date_needed'],
            ':section' => 'warehouse',
            ':status' => 'pending',
            ':created_at' => $data['created_at'],
            ':part_type' => $data['part_type']
        ];

        $this->db->Insert($insertFG, $paramsFG);

        // Update delivery_form
        // $sqlUpdateDelivery = "UPDATE delivery_form 
        //                   SET section = :newSection 
        //                   WHERE reference_no = :reference_no";

        // $paramsDelivery = [
        //     ':reference_no' => $data['reference_no'],
        //     ':newSection' => $data['new_section']
        // ];
        // $this->db->Update($sqlUpdateDelivery, $paramsDelivery);

        // Update assembly_list
        $sqlUpdateAssembly = "UPDATE customer_form 
                          SET status = :newStatus, section = :newSection 
                          WHERE reference_no = :reference_no";

        $paramsAssembly = [
            ':reference_no' => $data['reference_no'],
            ':newSection' => $data['new_section'],
            ':newStatus' => $data['new_status']
        ];
        $this->db->Update($sqlUpdateAssembly, $paramsAssembly);

        // Update qc_list
        $sqlUpdateQC = "UPDATE qc_list 
                    SET status = :newStatus, section = :newSection 
                    WHERE id = :id";

        $paramsQC = [
            ':id' => $data['id'],
            ':newSection' => $data['new_section'],
            ':newStatus' => 'done'
        ];
        $this->db->Update($sqlUpdateQC, $paramsQC);

        return true;
    }
    public function duplicatePendingQCRow(int $id, int $pending_quantity, string $time_out): bool
    {
        $selectSql = "SELECT * FROM qc_list WHERE id = :id";
        $selectParams = [':id' => $id];

        $modifyCallback = function ($row) use ($id, $pending_quantity, $time_out) {
            return [
                'itemID' => $id,
                'model' => $row['model'],
                'material_no' => $row['material_no'],
                'material_description' => $row['material_description'],
                'reference_no' => $row['reference_no'],
                'shift' => $row['shift'],
                'lot_no' => $row['lot_no'],
                'pending_quantity' => $pending_quantity,
                'total_quantity' => $row['total_quantity'],
                'status' => $row['status'],
                'section' => $row['section'],
                'variant' => $row['variant'],
                'date_needed' => $row['date_needed'],
                'created_at' => $row['created_at'],
                'process' => $row['process'],
                'assembly_section' => $row['assembly_section'],
                'cycle_time' => $row['cycle_time'],
                'pi_kbn_pieces' => $row['pi_kbn_pieces'],
                'pi_kbn_quantity' => $row['pi_kbn_quantity'],
                'fuel_type' => $row['fuel_type'],
                'part_type' => $row['part_type'],
                'customer_id' => $row['customer_id']
            ];
        };

        $insertSql = "INSERT INTO qc_list (
        itemID, model, material_no, material_description, pending_quantity, reference_no, cycle_time,fuel_type, customer_id,
        shift, lot_no, total_quantity, status, section, date_needed, created_at,process,assembly_section,variant,pi_kbn_quantity,pi_kbn_pieces,part_type
    ) VALUES (
        :itemID, :model, :material_no, :material_description, :pending_quantity, :reference_no, :cycle_time,:fuel_type, :customer_id,
        :shift, :lot_no, :total_quantity, :status, :section, :date_needed, :created_at,:process,:assembly_section,:variant,:pi_kbn_quantity,:pi_kbn_pieces,:part_type
    )";

        return $this->db->DuplicateAndModify($selectSql, $selectParams, $modifyCallback, $insertSql);
    }
    public function getRework($model)
    {
        $sql = "SELECT * FROM rework_qc WHERE section = 'qc' AND status IN ('pending','continue') AND model=:model AND created_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)";
        return $this->db->Select($sql, [':model' => $model]);
    }
    public function timein_reworkOperator(int $id, string $fullName, string $timeIn): bool
    {
        $sql = "UPDATE rework_qc SET qc_person_incharge = :full_name, qc_timein = :time_in WHERE id = :id";
        $params = [':full_name' => $fullName, ':time_in' => $timeIn, ':id' => $id];

        return $this->db->Update($sql, $params);
    }
    public function updateReworkQcTimeout(array $params): bool
    {
        $sql = "UPDATE rework_qc SET qc_person_incharge = :full_name, no_good = :no_good, good = :good, qc_pending_quantity = :qc_pending_quantity, qc_timeout = :time_out 
        WHERE id = :id";
        return $this->db->Update($sql, $params);
    }
    public function getQcSummaryByReference(string $reference_no, int $rework_no): ?array
    {
        $sql = "SELECT 
                model,
                material_no,
                material_description,
                shift,
                lot_no,
                date_needed,
                SUM(no_good)            AS total_noGood,
                SUM(good)               AS total_good,
                SUM(qc_pending_quantity) AS total_qc_pending_quantity,
                MAX(quantity)           AS total_quantity,
                SUM(rework)             AS total_rework,
                SUM(`replace`)          AS total_replace
            FROM  rework_qc
            WHERE reference_no = :reference_no
              AND rework_no    = :rework_no
            GROUP BY reference_no,
                     model,
                     material_no,
                     material_description,
                     shift,
                     lot_no,
                     date_needed";

        return $this->db->SelectOne(
            $sql,
            [
                ':reference_no' => $reference_no,
                ':rework_no'    => $rework_no
            ]
        );
    }

    public function markQcReferenceDone(string $reference_no): bool
    {
        $sql = "UPDATE rework_qc 
            SET status = 'done' 
            WHERE reference_no = :reference_no";

        return $this->db->Update($sql, [':reference_no' => $reference_no]);
    }

    public function updateDeliveryFormSection(string $reference_no, string $section = 'WAREHOUSE'): bool
    {
        $sql = "UPDATE delivery_form 
            SET section = :section 
            WHERE reference_no = :reference_no";

        return $this->db->Update($sql, [
            ':reference_no' => $reference_no,
            ':section' => $section
        ]);
    }

    public function updateFgWarehouseQuantity(string $reference_no, int $quantity): bool
    {
        $sql = "UPDATE fg_warehouse 
            SET quantity = quantity + :total_good 
            WHERE reference_no = :reference_no";

        return $this->db->Update($sql, [
            ':reference_no' => $reference_no,
            ':total_good' => $quantity
        ]);
    }
    public function getReworkQcById(int $id): ?array
    {
        $sql = "SELECT * FROM rework_qc WHERE id = :id";
        return $this->db->SelectOne($sql, [':id' => $id]);
    }

    public function duplicateReworkQc(array $row, string $time_out, int $rework_no): int
    {
        $insertSql = "INSERT INTO rework_qc (
        itemID, reference_no, model, material_no, material_description,
        shift, lot_no, no_good, good, quantity,rework_no,
        qc_quantity, qc_pending_quantity, qc_person_incharge,
        qc_timein, qc_timeout,assembly_section,cycle_time,
        status, section, date_needed, created_at,process,fuel_type, customer_id
    ) VALUES (
        :itemID, :reference_no, :model, :material_no, :material_description,
        :shift, :lot_no, :no_good, :good, :quantity,:rework_no,
        :qc_quantity, :qc_pending_quantity, :qc_person_incharge,
        :qc_timein, :qc_timeout,:assembly_section,:cycle_time,
        :status, :section, :date_needed, :created_at,:process,:fuel_type, :customer_id
    )";

        $data = [
            'itemID' => $row['id'],
            'reference_no' => $row['reference_no'],
            'model' => $row['model'],
            'material_no' => $row['material_no'],
            'material_description' => $row['material_description'],
            'shift' => $row['shift'],
            'lot_no' => $row['lot_no'],
            'rework_no' => $rework_no,
            'no_good' => null,
            'good' => null,
            'quantity' => $row['quantity'],
            'qc_quantity' => $row['qc_pending_quantity'],
            'qc_pending_quantity' => $row['qc_pending_quantity'],
            'qc_person_incharge' => null,
            'qc_timein' => null,
            'qc_timeout' => null,
            'status' => 'continue',
            'section' => 'qc',
            'assembly_section' => $row['assembly_section'],
            'date_needed' => $row['date_needed'],
            'created_at' => $time_out,
            'process' => $row['process'],
            'cycle_time' => $row['cycle_time'],
            'fuel_type' => $row['fuel_type'],
            'customer_id' => $row['customer_id']
        ];
        return $this->db->Insert($insertSql, $data);
    }
    public function insertReworkStamping(array $data): int
    {
        $params = [
            'ref' => $data['reference_no'],
            'mat' => $data['material_no']
        ];

        // Step 1: Get latest rework_no only (no grouping)
        $latest = $this->db->SelectOne(
            "SELECT rework_no
         FROM rework_stamping
         WHERE reference_no = :ref AND material_no = :mat
         ORDER BY rework_no DESC
         LIMIT 1",
            $params
        );

        $nextReworkNo = 1;

        if ($latest) {
            $currentReworkNo = (int)$latest['rework_no'];

            // Step 2: Get total_handled and max_quantity for that specific rework_no
            $totals = $this->db->SelectOne(
                "SELECT 
                SUM(`replace` + rework) AS total_handled,
                MAX(quantity) AS max_quantity
             FROM rework_stamping
             WHERE reference_no = :ref AND material_no = :mat AND rework_no = :rw",
                [
                    'ref' => $data['reference_no'],
                    'mat' => $data['material_no'],
                    'rw'  => $currentReworkNo
                ]
            );

            $total_handled = (int)$totals['total_handled'];
            $max_quantity  = (int)$totals['max_quantity'];

            $nextReworkNo = ($total_handled === $max_quantity)
                ? $currentReworkNo + 1
                : $currentReworkNo;
        }

        // Step 3: Insert new rework_finishing row
        $sql = "INSERT INTO rework_stamping (
            rework_no,
            itemID, model, material_no, material_description,
            shift, lot_no, `replace`, rework,assembly_section,
            quantity, stamping_quantity, date_needed,fuel_type,
            reference_no, created_at, status, section,process
        ) VALUES (
            :rework_no,
            :itemID, :model, :material_no, :material_description,
            :shift, :lot_no, :replace, :rework,:assembly_section,
            :quantity, :stamping_quantity, :date_needed,:fuel_type,
            :reference_no, :created_at, :status, :section,:process
        )";

        return $this->db->Insert($sql, [
            ':rework_no'         => $nextReworkNo,
            ':itemID'            => $data['id'],
            ':model'             => $data['model'],
            ':material_no'       => $data['material_no'],
            ':material_description' => $data['material_description'],
            ':shift'             => $data['shift'],
            ':lot_no'            => $data['lot_no'],
            ':replace'           => $data['total_replace'],
            ':rework'            => $data['total_rework'],
            ':quantity'          => $data['total_no_good'],
            ':stamping_quantity' => $data['total_no_good'],
            ':date_needed'       => $data['date_needed'],
            ':fuel_type'        => $data['fuel_type'],
            ':reference_no'      => $data['reference_no'],
            ':created_at'        => $data['time_out'],
            ':status'            => 'pending',
            ':section'           => 'stamping',
            ':process'        => $data['process'],
            ':assembly_section'        => 'FINISHING'
        ]);
    }
    public function getAllQCData(string $model): array
    {
        // QC data
        $qcSql = "SELECT * FROM qc_list WHERE model = :model AND time_out IS NOT NULL";
        $qcData = $this->db->Select($qcSql, [':model' => $model]);

        // Rework QC data
        $reworkSql = "SELECT * FROM rework_qc WHERE model = :model AND qc_timeout IS NOT NULL";
        $reworkData = $this->db->Select($reworkSql, [':model' => $model]);

        return [
            'qc' => $qcData,
            'rework' => $reworkData
        ];
    }
}
