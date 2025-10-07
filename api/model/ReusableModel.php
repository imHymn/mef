<?php
class ReusableModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function getCustomerandModel()
    {
        $sql = " SELECT DISTINCT model, customer_name,variant,lot,fuel, status FROM model ORDER BY model ASC, customer_name ASC ";
        return $this->db->Select($sql);
    }

    public function updateAccountMinimal(int $id, string $section, string $specific_section): bool
    {
        $sql = "UPDATE users 
            SET section = ?, specific_section = ? 
            WHERE id = ?";
        return $this->db->Update($sql, [$section, $specific_section, $id]);
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
    public function resetFinishing($id)
    {
        // ✅ Correct SQL syntax
        $updateSql = "UPDATE rework_finishing 
                  SET assembly_person_incharge = NULL, assembly_timein = NULL 
                  WHERE id = :id";
        $this->db->Update($updateSql, [':id' => $id]);

        return true;
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
    public function resetStamping($id)
    {
        $updateSql = "UPDATE stamping SET time_in = NULL, time_out = NULL, person_incharge = NULL WHERE id = :id";
        $this->db->Update($updateSql, [':id' => $id]);

        return true;
    }
    public function resetQC($id)
    {

        $updateSql1 = "UPDATE delivery_form SET section = 'QC' WHERE id = :id";
        $this->db->Update($updateSql1, [':id' => $id]);
        $updateSql2 = "UPDATE qc_list SET person_incharge=null, time_in=null WHERE id = :id";
        $this->db->Update($updateSql2, [':id' => $id]);
        return true;
    }
    public function resetReworkQC($id)
    {
        $updateSql2 = "UPDATE rework_qc SET qc_person_incharge=null, qc_timein=null WHERE id = :id";
        $this->db->Update($updateSql2, [':id' => $id]);
        return true;
    }
}
