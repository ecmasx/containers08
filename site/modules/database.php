<?php

class Database {
  private $path;
  private $conn;

  public function __construct($path) {
    $this->path = $path;
    $this->conn = new SQLite3($path);
  }

  public function Execute($sql) {
    return $this->conn->exec($sql);
  }

  public function Fetch($sql) {
    $result = $this->conn->query($sql);
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      $rows[] = $row;
    }
    return $rows;
  }

  public function Create($table, $data) {
    $columns = implode(',', array_keys($data));
    $placeholders = implode(',', array_fill(0, count($data), '?'));
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    $stmt = $this->conn->prepare($sql);
    $i = 1;
    foreach ($data as $value) {
      $stmt->bindValue($i++, $value);
    }
    $stmt->execute();
    
    return $this->conn->lastInsertRowID();
  }

  public function Read($table, $id) {
    $sql = "SELECT * FROM $table WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    return $result->fetchArray(SQLITE3_ASSOC);
  }
  
  public function Update($table, $id, $data) {
    $updates = [];
    foreach (array_keys($data) as $column) {
      $updates[] = "$column = :$column";
    }
    $updateStr = implode(',', $updates);
    
    $sql = "UPDATE $table SET $updateStr WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    foreach ($data as $column => $value) {
      $stmt->bindValue(":$column", $value);
    }
    
    return $stmt->execute();
  }

  public function Delete($table, $id) {
    $sql = "DELETE FROM $table WHERE id = :id";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    return $stmt->execute();
  }
  
  public function Count($table) {
    $result = $this->conn->query("SELECT COUNT(*) as count FROM $table");
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row['count'];
  }
}