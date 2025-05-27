<?php

namespace App\Core;

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];

    public function __construct() {
        $this->db = \App\Config\Database::getInstance()->getConnection();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function all() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    public function create($data) {
        $data = $this->filterFillable($data);
        
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        
        return $this->find($this->db->lastInsertId());
    }

    public function update($id, $data) {
        $data = $this->filterFillable($data);
        
        $fields = array_keys($data);
        $values = array_values($data);
        
        $set = implode('=?,', $fields) . '=?';
        $sql = "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?";
        
        $values[] = $id;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        
        return $this->find($id);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    public function where($column, $value) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public function paginate($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} LIMIT ? OFFSET ?");
        $stmt->execute([$perPage, $offset]);
        $items = $stmt->fetchAll();
        
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $total = $stmt->fetchColumn();
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function beginTransaction() {
        return $this->db->beginTransaction();
    }

    protected function commit() {
        return $this->db->commit();
    }

    protected function rollBack() {
        return $this->db->rollBack();
    }
} 