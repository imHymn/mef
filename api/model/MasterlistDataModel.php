<?php
class MasterlistDataModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function deleteSKU($id)
    {
        $sql = "DELETE FROM material_inventory WHERE id = :id";
        return $this->db->Delete($sql, [':id' => $id]);
    }
    public function deleteComponent($id)
    {
        $sql = "DELETE FROM components_inventory WHERE id = :id";
        return $this->db->Delete($sql, [':id' => $id]);
    }
    public function deleteRM($id)
    {
        $sql = "DELETE FROM rawmaterials_inventory WHERE id = :id";
        return $this->db->Delete($sql, [':id' => $id]);
    }
    public function getSkuMaterialNo($model)
    {
        $sql = "SELECT material_no,material_description FROM material_inventory WHERE model = :model";
        return $this->db->Select($sql, [':model' => $model]);
    }
    public function getSKUData($model)
    {

        $sql = "SELECT id,material_no,material_description,assembly_processtime,sub_component,assembly_section,assembly_process,manpower,total_process,quantity,process,fuel_type FROM material_inventory WHERE model=:model";
        return $this->db->Select($sql, [':model' => $model]);
    }
    public function getRMComponent($model)
    {
        $sql = "
        SELECT c.material_no, c.components_name,usage_type
        FROM components_inventory c
        WHERE c.model = :model
          AND NOT EXISTS (
              SELECT 1
              FROM rawmaterials_inventory r
              WHERE r.material_no = c.material_no
                AND r.component_name = c.components_name
          )
    ";

        return $this->db->Select($sql, [':model' => $model]);
    }
    public function addRM($input)
    {
        try {
            $sql = "INSERT INTO rawmaterials_inventory
                (material_no, component_name, model,  material_description, `usage`)
                VALUES
                (:material_no, :component_name, :model,  :material_description, :usage)";

            $params = [
                ':material_no' => $input['material_no'] ?? null,
                ':component_name' => $input['component_name'] ?? null,
                ':model' => $input['model'] ?? null,
                ':material_description' => $input['material_description'] ?? null,
                ':usage' => $input['usage'] ?? 0
            ];

            $this->db->Insert($sql, $params);

            return ['success' => true, 'message' => 'Raw material added successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }





    public function getComponentData($model)
    {
        $sql = "
        SELECT 
            c.id,
            c.material_no AS material_no,
            c.components_name,
            c.usage_type,
            c.process_quantity,
            c.stage_name,
            c.actual_inventory,
            c.rm_stocks,
            c.process,
            c.pair,
            COALESCE(m.material_no, 'No Material Component') AS rm_material_no
        FROM components_inventory c
        LEFT JOIN material_inventory m 
            ON c.material_no = m.material_no 
            AND m.model = c.model
        WHERE c.model = :model
    ";

        return $this->db->Select($sql, [':model' => $model]);
    }
    public function addSKU($input)
    {
        // Basic validation
        $required = ['customer_name', 'model', 'material_no', 'material_description'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                return ['success' => false, 'message' => "Missing required field: $field"];
            }
        }

        // Prepare insert data
        $insertData = [
            ':customer_name'        => $input['customer_name'],
            ':model'                => $input['model'],
            ':material_no'          => $input['material_no'],
            ':material_description' => $input['material_description'],
            ':assembly_process'     => $this->jsonOrNull($input['assembly_process']),
            ':assembly_processtime' => $this->jsonOrNull($input['assembly_processtime']),
            ':assembly_section'     => $this->jsonOrNull($input['assembly_section']),
            ':manpower'             => $this->jsonOrNull($input['manpower']),
            ':sub_component'        => $this->jsonOrNull($input['sub_component']),
            ':process' => $input['process'],

            ':quantity'             => $input['quantity'] ?? 0,
            ':total_process'        => $input['total_process'] ?? 0,
            ':fuel_type'            => $input['fuel_type'] ?? null
        ];

        $sql = "INSERT INTO material_inventory ( customer_name, model, material_no, material_description, assembly_process, 
        assembly_processtime, assembly_section, manpower, sub_component, process, quantity, total_process, fuel_type )
        VALUES ( :customer_name, :model, :material_no, :material_description, :assembly_process, 
        :assembly_processtime, :assembly_section, :manpower, :sub_component, :process, :quantity, :total_process, :fuel_type )";
        try {
            $this->db->Insert($sql, $insertData);
            return ['success' => true, 'message' => 'SKU added successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to add SKU: ' . $e->getMessage()];
        }
    }
    public function addComponent($input)
    {
        try {
            $usage = floatval($input['usage']);
            $maximum_inventory = 450 * $usage;
            $normal            = 360 * $usage;
            $reorder           = 270 * $usage;
            $minimum           = 180 * $usage;
            $critical          = 90  * $usage;

            // Prepare insert data
            $insertData = [
                ':material_no'       => $input['material_no'],
                ':components_name'   => $input['components_name'],
                ':model'             => $input['model'],
                ':usage_type'        => $usage,
                ':process_quantity'  => $input['process_quantity'] ?? null,
                ':stage_name'        => $input['stage_name'] ?? null,
                ':actual_inventory'  => $input['actual_inventory'],
                ':maximum_inventory' => $maximum_inventory,
                ':normal'            => $normal,
                ':reorder'           => $reorder,
                ':minimum'           => $minimum,
                ':critical'          => $critical,
                ':process'           => $input['process'],
            ];

            $sql = "INSERT INTO components_inventory ( material_no, components_name, model, usage_type, process_quantity, stage_name, 
            actual_inventory, maximum_inventory, normal, reorder, minimum, critical, process ) 
            VALUES ( :material_no, :components_name, :model, :usage_type, :process_quantity, :stage_name, 
            :actual_inventory, :maximum_inventory, :normal, :reorder, :minimum, :critical, :process )";

            $this->db->Insert($sql, $insertData);

            return ['success' => true, 'message' => 'Component added successfully!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to add component: ' . $e->getMessage()];
        }
    }

    public function updateRM(array $input)
    {
        // Validate required fields
        if (empty($input['rm_id'])) {
            return ['success' => false, 'message' => 'RM ID is required'];
        }

        // Prepare update query
        $sql = "UPDATE `rawmaterials_inventory` SET
                `material_no` = :material_no,
                `component_name` = :component_name,
                `material_description` = :material_description,
                `usage` = :usage
            WHERE `id` = :rm_id";

        $params = [
            ':material_no' => $input['rm_material_no'] ?? '',
            ':component_name' => $input['rm_component_name'] ?? '',
            ':material_description' => $input['rm_material_description'] ?? '',
            ':usage' => $input['rm_usage'] ?? 0,
            ':rm_id' => $input['rm_id']
        ];

        try {
            $result = $this->db->Update($sql, $params); // assuming Update() returns affected rows or true/false
            if ($result) {
                return ['success' => true];
            } else {
                return ['success' => false, 'message' => 'No changes made'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateComponent($input)
    {
        // List of special models
        $specialModels = [
            'TAMARAW',
            'VIOS',
            'INNOVA (EXH FRONT)',
            'INNOVA EXH TAIL (GAS)',
            'INNOVA EXH TAIL (DIESEL)'
        ];

        // If process is empty string, set it to null
        $processValue = isset($input['process']) && $input['process'] !== ''
            ? $input['process']
            : null;

        // Prepare parameters
        $params = [
            ':material_no'     => $input['material_name'] ?? null,
            ':components_name' => $input['components_name'] ?? null,
            ':model'           => $input['model'] ?? null,
            ':stage_name'      => $input['stage_name'] ?? null,
            ':process'         => $processValue,
            ':actual_inventory'      => $input['actual_inventory'] ?? null,
            ':usage_type'      => $input['usage'] ?? null,
        ];

        // Determine WHERE clause
        if (in_array($input['model'] ?? '', $specialModels)) {
            // Update all rows matching material_no and components_name
            $sql = "UPDATE components_inventory
                SET 
                    model = :model,
                    stage_name = :stage_name,
                    process = :process,
                    updated_at = NOW(),actual_inventory = :actual_inventory,
                    usage_type = :usage_type
                WHERE material_no = :material_no
                  AND components_name = :components_name";
        } else {
            // Normal update by id
            $sql = "UPDATE components_inventory
                SET 
                    material_no = :material_no,
                    components_name = :components_name,
                    model = :model,
                    actual_inventory = :actual_inventory,
                    stage_name = :stage_name,
                    process = :process,
                    usage_type = :usage_type,
                    updated_at = NOW()
                WHERE id = :id";
            $params[':id'] = $input['id'] ?? null;
        }

        try {
            return $this->db->Update($sql, $params);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function jsonOrNull($value)
    {
        if (is_array($value) && empty($value)) return null;
        return isset($value) ? json_encode($value) : null;
    }

    public function updateSKU($input)
    {
        if (empty($input['id'])) {
            return ['success' => false, 'message' => 'Missing SKU ID'];
        }

        $id = $input['id'];

        $updateData = [
            ':assembly_process'     => $this->jsonOrNull($input['assembly_process']),
            ':assembly_processtime' => $this->jsonOrNull($input['assembly_processtime']),
            ':assembly_section'     => $this->jsonOrNull($input['assembly_section']),
            ':manpower'             => $this->jsonOrNull($input['manpower']),
            ':sub_component'        => $this->jsonOrNull($input['sub_component']),
            ':total_process'        => $input['total_process'] ?? 0,
            ':process'        =>  $this->jsonOrNull($input['process']),
            ':quantity'             => $input['quantity'] ?? 0,
            ':id'                   => $id,
            ':fuel_type'            => $input['fuel_type'] ?? null
        ];
        $sql = "UPDATE material_inventory SET 
                assembly_process = :assembly_process,
                assembly_processtime = :assembly_processtime,
                assembly_section = :assembly_section,
                manpower = :manpower,
                sub_component = :sub_component,
                total_process = :total_process,
                quantity = :quantity,
                process = :process,
                fuel_type = :fuel_type
                WHERE id = :id";


        try {
            $this->db->Update($sql, $updateData);
            // Always return success if query executed, even if rowCount() == 0
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update SKU: ' . $e->getMessage()];
        }
    }

    public function getRMData($model)
    {
        $sql = "
    (
        -- Raw materials with corresponding components (or missing)
            SELECT DISTINCT
            r.id as rm_id,
            r.material_no AS rm_material_no,
            r.material_description AS rm_material_description,
            r.component_name AS rm_component_name,
            r.usage AS rm_usage,
            c.material_no AS comp_material_no,
            c.components_name AS comp_component_name,
            c.usage_type AS comp_usage_type,
            CASE
                WHEN c.material_no IS NULL THEN 'Missing in Components'
                ELSE 'Matched'
            END AS match_status
        FROM rawmaterials_inventory r
        LEFT JOIN components_inventory c
            ON r.material_no = c.material_no
            AND r.component_name = c.components_name
        WHERE r.model = :model
    )
    UNION ALL
    (
        -- Components without matching raw materials
        SELECT
            r.id as rm_id,
            r.material_no AS rm_material_no,
            r.material_description AS rm_material_description,
            r.component_name AS rm_component_name,
            r.usage AS rm_usage,
            c.material_no AS comp_material_no,
            c.components_name AS comp_component_name,
            c.usage_type AS comp_usage_type,
            'Missing in Raw Materials' AS match_status
        FROM components_inventory c
        LEFT JOIN rawmaterials_inventory r
            ON r.material_no = c.material_no
            AND r.component_name = c.components_name
            AND r.model = c.model
        WHERE r.material_no IS NULL
            AND c.model = :model
    )
    ";

        return $this->db->Select($sql, [':model' => $model]);
    }
}
