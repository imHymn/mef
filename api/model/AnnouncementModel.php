<?php
class AnnouncementModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }
    public function getAnnouncement()
    {
        $sql = "SELECT * FROM announcements WHERE deleted_at IS NULL ORDER BY id ASC ";
        return $this->db->Select($sql);
    }
    public function createAnnouncement($title, $message, $category, $priority, $status, $start_date, $end_date, $created_at, $updated_at)
    {
        $sql = "INSERT INTO announcements 
            (title, message, category, priority, status, start_date, end_date, created_at, updated_at)
            VALUES 
            (:title, :message, :category, :priority, :status, :start_date, :end_date, :created_at, :updated_at)";

        $params = [
            ':title' => $title,
            ':message' => $message,
            ':category' => $category,
            ':priority' => $priority,
            ':status' => $status,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':created_at' => $created_at,
            ':updated_at' => $updated_at,
        ];

        return $this->db->Insert($sql, $params);
    }

    public function updateAnnouncement($id, $title, $message, $category, $priority, $status, $start_date, $end_date, $updated_at)
    {
        $sql = "UPDATE announcements 
            SET title = :title, message = :message, category = :category, 
                priority = :priority, status = :status, start_date = :start_date, end_date = :end_date,
                updated_at = :updated_at 
            WHERE id = :id";

        $params = [
            ':id' => $id,
            ':title' => $title,
            ':message' => $message,
            ':category' => $category,
            ':priority' => $priority,
            ':status' => $status,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':updated_at' => $updated_at
        ];

        return $this->db->Update($sql, $params);
    }

    public function getAnnouncementById($id)
    {
        $sql = "SELECT * FROM announcements WHERE id = :id";
        return $this->db->SelectOne($sql, [":id" => $id]);
    }

    public function deleteAnnouncement($id)
    {
        $sql = "DELETE FROM announcements WHERE id = :id";
        return $this->db->Update($sql, [":id" => $id]);
    }
    public function softDeleteAnnouncement($id)
    {
        $sql = "UPDATE announcements SET deleted_at = NOW() WHERE id = :id";
        return $this->db->Update($sql, [":id" => $id]);
    }
}
