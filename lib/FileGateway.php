<?php
class FileGateway {
  //сохраняем здесь имя таблицы, т.к. класс создан только для работы с ней
  private $table = "files";
  //это свойство ссылается на синглтон объекта mysqli
  private $mysqliInstanse;
  
  public function insert(File $file) {     

    $this->mysqliInstanse->query('START TRANSACTION');

    $query = "INSERT INTO {$this->table} (name, size, mime, unixtime, md5)
    VALUES (?, ?, ?, ?, ?)";
    if ($stmt = $this->mysqliInstanse->prepare($query)) {
      $name = $file->name;
      $size = $file->size;
      $mime = $file->mime;
      $unixtime = $file->unixtime;
      $md5 = $file->md5;
      $stmt->bind_param("sisis", $name, $size, $mime, $unixtime, $md5);
    } else {
      throw new Exception("Не удалось подготовить SQL запрос");
    }
    if (!$stmt->execute()) {
        throw new Exception("Не удалось добавить данные о файле в таблицу: (" . $stmt->errno . ") " . $stmt->error);
    }
    $stmt->close();
    $id = $this->mysqliInstanse->insert_id;
    $file->setId($id);
    return $file;
  }

  public function deleteFile($id) {
    $query = "DELETE FROM {$this->table} WHERE id = ?";
    $stmt = $this->mysqliInstanse->prepare($query);
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("Не удалось удалить данные о файле из таблицы: (" . $stmt->errno . ") " . $stmt->error);
    }
    $stmt->close();
  }

  public function addPath($path, $id) {
    $query = "UPDATE {$this->table} SET path = ? WHERE id = ?";
    $stmt = $this->mysqliInstanse->prepare($query);
    $stmt->bind_param("si", $path, $id);
     if (!$stmt->execute()) {

       $this->mysqliInstanse->query('ROLLBACK');
       throw new Exception("Не удалось добавить путь к файлу (" . $stmt->errno . ") " . $stmt->error);
     } else {
        $stmt->close();
        $this->mysqliInstanse->query('COMMIT');
        return true;
     }
  }
  public function getData($id) {
    $query = "SELECT * FROM {$this->table} WHERE id=?";
    $stmt = $this->mysqliInstanse->prepare($query);
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
       throw new Exception("Не удалось получить данные о файле по заданному id: " . $id);
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    if (!$row) {
      return false;
    } 
    $file = File::constructFromArray($row);
    return $file;
  }

  public function getLatestFiles($limit) {
    $query = "SELECT id, name, size FROM files ORDER BY id DESC LIMIT {$limit}";
    $stmt = $this->mysqliInstanse->prepare($query);
    $stmt->execute();
    $stmt->bind_result($id, $name, $size);
    $files = array();
    while ($stmt->fetch()) {
      $fields = array(
        'id' => $id,
        'name' => $name,
        'size' => $size
      );
      $file = File::constructFromArray($fields);
      $files[] = $file;
    }
    $stmt->close();
    return $files;
  }
/*
  public function checkFile($fileId) {
    $query = "SELECT description FROM {$this->table} WHERE id = ?";
    $stmt = $this->mysqliInstanse->prepare($query);
    $stmt->bind_param("i", $fileId);
     if (!$stmt->execute()) {
        throw new Exception("Не удалось связаться с базой данных: (" . $stmt->errno . ") " . $stmt->error);
     }
     $stmt->bind_result($description);
     $stmt->fetch();
     $stmt->close();
     return $description;
  }
*/
  public function changeDescription($fileId, $description) {
    $query = "UPDATE {$this->table} SET description = ? 
              WHERE id = ?";
    if ($stmt = $this->mysqliInstanse->prepare($query) )  {
        $stmt->bind_param("si", $description, $fileId);
    } else {
      throw new Exception("Не удалось подготовить SQL запрос при попытке изменить описание");
    }
     if(!$stmt->execute()) {
      throw new Exception("Не удалось изменить описание для файла - Проблемы при соединение с бд"); 
     }
     $stmt->close();
    //$num =  $stmt->affected_rows;          
  }
/*
  public function addDescription($fileId, $description) {

    $query = "INSERT INTO {$this->table} (description)
    VALUES (?) WHERE id = ?";

    if ($stmt = $this->mysqliInstanse->prepare($query)) {
      $stmt->bind_param("si", $description, $fileId);
    } else {
      throw new Exception("Не удалось подготовить SQL запрос при попытке добавления описания");
    }
    if (!$stmt->execute()) {
        throw new Exception("Не удалось добавить данные о файле в таблицу: (" . $stmt->errno . ") " . $stmt->error);
    }
    $stmt->close();
    //return $stmt->affected_rows;
  }
  */
  public function getDescription($fileId) {
    $query = "SELECT (description) FROM {$this->table} WHERE id = ?";
    if ($stmt = $this->mysqliInstanse->prepare($query)) {
      $stmt->bind_param("i", $fileId);
    } else {
      throw new Exception("Не удалось подготовить SQL запрос");
    }
    if (!$stmt->execute()) {
        throw new Exception("Не удалось добавить данные о файле в таблицу: (" . $stmt->errno . ") " . $stmt->error);
    }
    $stmt->bind_result($description);
    if( $stmt->fetch() === false ) {
      throw new Exception("Не удалось получить описаниие для файла по id: " . $id);
    }
    $stmt->close();
    return $description;
  } 

  public function __construct(mysqli $mysqli) {
    $this->mysqliInstanse = $mysqli;
  }
}
