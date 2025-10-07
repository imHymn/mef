<?php


class DatabaseClass
{
    private $connection = null;

    public function __construct()
    {
        try {
            $dsn = "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4";
            $this->connection = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function Select($sql, $params = [])
    {
        $stmt = $this->executeStatement($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function SelectOne($sql, $params = [])
    {
        $stmt = $this->executeStatement($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function Update($sql, $params = [])
    {
        $stmt = $this->executeStatement($sql, $params);
        return $stmt->rowCount() > 0; // return true if at least 1 row updated
    }

    public function Insert($sql, $params = [])
    {
        $stmt = $this->executeStatement($sql, $params);
        if ($stmt->rowCount() > 0) {
            $lastInsertId = $this->connection->lastInsertId();
            return $lastInsertId;
        }
        return false;
    }
    public function Delete($sql, $params = [])
    {
        $stmt = $this->executeStatement($sql, $params);
        return $stmt->rowCount();
    }
    private function executeStatement($sql, $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }
    public function commit()
    {
        return $this->connection->commit();
    }
    public function rollBack()
    {
        if ($this->connection->inTransaction()) {
            return $this->connection->rollBack();
        }
        return false;
    }
    public function DuplicateAndModify($selectSql, $selectParams, callable $modifyCallback, $insertSql)
    {
        $rows = $this->Select($selectSql, $selectParams);
        if (empty($rows)) {
            return 0;
        }
        $insertCount = 0;
        foreach ($rows as $row) {
            $modifiedRow = $modifyCallback($row);
            $insertParams = [];
            foreach ($modifiedRow as $key => $value) {
                $insertParams[":$key"] = $value;
            }

            $this->Insert($insertSql, $insertParams);
            $insertCount++;
        }

        return $insertCount;
    }
    public function inTransaction()
    {
        return $this->connection->inTransaction();
    }
}
