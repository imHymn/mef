<?php
class FGModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function getReadyforPullOut($model)
    {
        $sql = "SELECT * FROM fg_warehouse WHERE status = 'pending' AND quantity = total_quantity AND model = :model";

        return $this->db->Select($sql, [':model' => $model]);
    }
    public function markAsPulledFromFG($id, $pulled_at)
    {
        $sql = "UPDATE fg_warehouse SET status = 'done', pulled_at = :pulled_at WHERE id = :id";
        $result = $this->db->Update($sql, [':id' => $id, ':pulled_at' => $pulled_at]);

        return $result ? true : "❌ Failed to update FG Warehouse.";
    }


    public function markDeliveryFormAsDone($reference_no)
    {
        $sql = "UPDATE delivery_form SET status = 'done', section = 'WAREHOUSE' WHERE reference_no = :reference_no";
        $result = $this->db->Update($sql, [':reference_no' => $reference_no]);

        return $result ? true : "❌ Failed to update Delivery Form.";
    }
    public function markDeliveryHistoryasDone($reference_no)
    {
        $sql = "UPDATE delivery_history SET status = 'done', active =1 WHERE reference_no = :reference_no";
        $result = $this->db->Update($sql, [':reference_no' => $reference_no]);

        return $result ? true : "❌ Failed to update Delivery History.";
    }


    public function markAssemblyListAsDone($reference_no)
    {
        $sql = "UPDATE assembly_list SET status = 'done', section = 'warehouse' WHERE reference_no = :reference_no";
        $result = $this->db->Update($sql, [':reference_no' => $reference_no]);

        return $result ? true : "❌ Failed to update Assembly List.";
    }

    public function updateMaterialInventory($material_no, $material_description, $quantity)
    {

        $sql = "UPDATE material_inventory 
            SET quantity = quantity + :quantity 
            WHERE material_no = :material_no 
            AND material_description = :material_description";

        $params = [
            ':quantity' => $quantity,
            ':material_no' => $material_no,
            ':material_description' => $material_description,
        ];

        $result = $this->db->Update($sql, $params);

        return $result ? true : "❌ Failed to update Material Inventory.";
    }
    public function updateComponentsInventory($material_no, $material_description, $quantity)
    {
        if (in_array($material_description, ['CLIP 25', 'CLIP 60', 'NUT WELD', 'NUT WELD', 'NUT WELD (6)', 'NUT WELD (8)', 'NUT WELD (10)', 'NUT WELD (11.112)', 'NUT WELD (12)', 'REINFORCEMENT'])) {
            $sql = "UPDATE components_inventory SET actual_inventory = actual_inventory + :quantity WHERE components_name = :material_description";
            $params = [
                ':quantity' => $quantity,
                ':material_description' => $material_description,
            ];
        } else {
            $sql = "UPDATE components_inventory SET actual_inventory = actual_inventory + :quantity WHERE material_no = :material_no AND components_name = :material_description";
            $params = [
                ':quantity' => $quantity,
                ':material_no' => $material_no,
                ':material_description' => $material_description,
            ];
        }
        $result = $this->db->Update($sql, $params);
        return $result ? true : "❌ Failed to update Material Inventory.";
    }
    public function getAllComponents($model)
    {
        $sql = "SELECT * from material_inventory WHERE model=:model";
        return $this->db->Select($sql, [':model' => $model]);
    }
    public function getPulledoutHistory($model)
    {
        $sql = "SELECT * FROM fg_warehouse WHERE status = 'done' AND model = :model";
        return $this->db->Select($sql, [':model' => $model]);
    }
}
