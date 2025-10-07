<?php
class AccountModel
{
    private $db;

    // Accept DatabaseClass from outside
    public function __construct(DatabaseClass $db)
    {
        $this->db = $db;
    }

    // Select all users
    public function getAccounts()
    {
        $sql = "SELECT * FROM users";
        return $this->db->Select($sql);
    }
    public function resetUsers($userId, $hashedPassword)
    {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        return $this->db->Update($sql, [
            ':password' => $hashedPassword,
            ':id' => $userId
        ]);
    }

    public function getAssemblyAccounts(): array
    {
        return $this->db->Select("
        SELECT * 
        FROM users 
        WHERE JSON_CONTAINS(section, '\"assembly\"')  AND role in('operator','line leader')
    ");
    }
    public function getFinishingAccounts(): array
    {
        return $this->db->Select("
        SELECT * 
        FROM users 
        WHERE JSON_CONTAINS(section, '\"finishing\"')  AND role in('operator','line leader')
    ");
    }
    public function getPaintingAccounts(): array
    {
        return $this->db->Select("
        SELECT * 
        FROM users 
        WHERE JSON_CONTAINS(section, '\"painting\"') AND role in('operator','line leader')
    ");
    }
    public function getStampingAccounts(): array
    {
        return $this->db->Select("
        SELECT * 
        FROM users 
        WHERE JSON_CONTAINS(section, '\"stamping\"') AND role in('operator','line leader')
    ");
    }

    public function getQCAccounts(): array
    {
        return $this->db->Select("
        SELECT * 
        FROM users 
        WHERE JSON_CONTAINS(section, '\"qc\"') AND role in('operator','line leader')
    ");
    }
    public function getUserByUserId($user_id)
    {
        $sql = "SELECT * FROM users WHERE user_id = :user_id";
        return $this->db->SelectOne($sql, [':user_id' => $user_id]);
    }
    public function getUserbyId($id)
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        return $this->db->SelectOne($sql, [':id' => $id]);
    }
    public function updateUser($id, $data)
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $params[':id'] = $id;

        return $this->db->Update($sql, $params); // using your DatabaseClass
    }
    public function deleteUser($id)
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $params = [':id' => $id];
        return $this->db->Update($sql, $params); // using your DatabaseClass
    }
    public function updatePassword($userId, $hashedPassword)
    {
        $sql = "UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :user_id";
        $result = $this->db->Update($sql, [
            ':password' => $hashedPassword,
            ':user_id' => $userId
        ]);
        return $result !== false; // normalize return
    }


    public function createUser($data)
    {
        $sql = "INSERT INTO users (name, user_id, password, department, role, section,specific_section,created_at) 
                VALUES (:name, :user_id, :password, :department, :role, :section,:specific_section,NOW())";

        $params = [
            ':name' => $data['name'],
            ':user_id' => $data['user_id'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':department' => $data['department'],
            ':role' => $data['role'],
            ':section' => $data['section'],
            ':specific_section' => $data['specific_section'],
        ];

        return $this->db->Insert($sql, $params);
    }
    public function login($user_id, $password)
    {
        $sql = "SELECT * FROM users WHERE user_id = :user_id LIMIT 1";
        $user = $this->db->SelectOne($sql, [':user_id' => $user_id]);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Incorrect password.'];
        }

        return [
            'success' => true,
            'user' => [
                'id'              => $user['id'],
                'user_id'         => $user['user_id'],
                'name'            => $user['name'],
                'role'            => $user['role'],
                'department'      => $user['department'] ?? null,
                'section'         => $user['section'] ?? null,
                'specific_section' => $user['specific_section'] ?? null,
            ]
        ];
    }

    public function updateQRGenerated(array $userIds, string $generatedAt): bool
    {
        if (empty($userIds)) {
            return false;
        }

        // Build placeholders (:id0, :id1, ...)
        $placeholders = [];
        $params = ['gen' => $generatedAt];
        foreach ($userIds as $i => $userId) {
            $placeholders[] = ":id{$i}";
            $params["id{$i}"] = $userId;
        }

        $sql = "UPDATE users 
                   SET generated_at = :gen 
                 WHERE user_id IN (" . implode(',', $placeholders) . ")";

        return (bool)$this->db->Update($sql, $params);
    }
}
