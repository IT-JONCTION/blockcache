<?php

namespace Itjonction\Blockcache\General;

use Exception;
use PDO;

trait HasCacheKey
{
    protected PDO $pdo;
    protected array $parents_cached = [];
    protected string $table_cached;
    protected string $id_cached;

    public function getTableCached(): string
    {
        return $this->table_cached;
    }

    public function setTableCached(string $table_cached): void
    {
        $this->table_cached = $table_cached;
    }

    // Inject PDO instance
    public function setPdo(PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    // Set parents for the model
    public function setParents(array $parents): void
    {
        $this->parents_cached = $parents;
    }

    /**
     * @throws Exception
     */
    public function getCacheKey(PDO $pdo, string $table = null, string $id = null): string
    {
        $table = $table ?? $this->table_cached;
        $id = $id ?? $this->id_cached;

        $stmt = $pdo->prepare("SELECT id, updated_at FROM $table WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Record not found in $table with id $id");
        }

        $this->id_cached = $row['id'];
        $updated_at = $row['updated_at'];

        return sprintf("%s/%s-%s",
          get_class($this),
          $this->id,
          strtotime($updated_at)
        );
    }

    /**
     * @throws Exception
     */
    public function touchParents(PDO $pdo): void
    {
        foreach ($this->parents_cached as $parent) {
            $table = is_string($parent) ? $parent : $parent['table'];
            $id = is_string($parent) ? 'id' : $parent['id'];
            try {
                $stmt = $pdo->prepare("UPDATE $table SET updated_at = NOW() WHERE id = :id");
                $stmt->execute(['id' => $id]);
            } catch (Exception $e) {
                throw new Exception('Failed to touch parent: ' . $e->getMessage());
            }
        }
    }
}

