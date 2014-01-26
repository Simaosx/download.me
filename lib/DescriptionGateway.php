<?
class DescriptionGateway {
  private $table = "descriptions";
  private $mysqliInstanse;
  
  public function checkFile($fileId) {
    $query = "SELECT file_id FROM {$this->table} WHERE file_id = ?";
    $stmt = $this->mysqliInstanse->prepare($query);
    $stmt->bind_param("i", $fileId);
     if (!$stmt->execute()) {
        throw new Exception("Не удалось связаться с базой данных: (" . $stmt->errno . ") " . $stmt->error);
     }
     $stmt->store_result();
     $num  = $stmt->num_rows;
     $stmt->close();
     return $num;
  }

  public function changeDescription($fileId, $description) {
    $query = "UPDATE {$this->table} SET description = ? 
              WHERE file_id = ?";
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

  public function addDescription($fileId, $description) {

    $query = "INSERT INTO {$this->table} (file_id, description)
    VALUES (?, ?)";

    if ($stmt = $this->mysqliInstanse->prepare($query)) {
      $stmt->bind_param("is", $fileId, $description);
    } else {
      throw new Exception("Не удалось подготовить SQL запрос при попытке добавления описания");
    }
    if (!$stmt->execute()) {
        throw new Exception("Не удалось добавить данные о файле в таблицу: (" . $stmt->errno . ") " . $stmt->error);
    }
    $stmt->close();
    //return $stmt->affected_rows;
  }
  
  public function getDescription($fileId) {
    $query = "SELECT (description) FROM {$this->table} WHERE file_id = ?";
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