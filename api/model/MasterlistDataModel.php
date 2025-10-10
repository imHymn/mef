<?php
class MasterlistDataModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }


    public function getSKUData($model)
    {

        $sql = "SELECT id,material_no,material_description,assembly_processtime,sub_component,assembly_section,assembly_process,manpower,total_process,quantity FROM material_inventory WHERE model=:model";
        return $this->db->Select($sql, [':model' => $model]);
    }

    public function getComponentData($model)
    {

        $sql = "SELECT id,material_no,components_name,usage_type,process_quantity,stage_name,actual_inventory,rm_stocks,process,pair FROM components_inventory WHERE model=:model";
        return $this->db->Select($sql, [':model' => $model]);
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
        ];

        // Determine WHERE clause
        if (in_array($input['model'] ?? '', $specialModels)) {
            // Update all rows matching material_no and components_name
            $sql = "UPDATE components_inventory
                SET 
                    model = :model,
                    stage_name = :stage_name,
                    process = :process,
                    updated_at = NOW(),actual_inventory = :actual_inventory
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


    public function updateSKU($input)
    {
        if (empty($input['id'])) {
            return ['success' => false, 'message' => 'Missing SKU ID'];
        }

        $id = $input['id'];

        $updateData = [
            ':assembly_process' => json_encode($input['assembly_process']),
            ':assembly_processtime' => json_encode($input['assembly_processtime']),
            ':assembly_section' => json_encode($input['assembly_section']),
            ':manpower' => json_encode($input['manpower']),
            ':sub_component' => json_encode($input['sub_component']),
            ':total_process' => $input['total_process'],
            ':quantity' => $input['quantity'], // <-- added
            ':id' => $id
        ];

        $sql = "UPDATE material_inventory SET 
                assembly_process = :assembly_process,
                assembly_processtime = :assembly_processtime,
                assembly_section = :assembly_section,
                manpower = :manpower,
                sub_component = :sub_component,
                total_process = :total_process,
                quantity = :quantity -- <-- added
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
