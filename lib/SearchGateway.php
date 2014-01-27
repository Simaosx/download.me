<?php
class SearchGateway {
	private $mysqliInstanse;
    private $table = "files";
    
    public function getNumberOfRows($string) {
     $searchString = "'{$string}*'";

     $query = "SELECT COUNT( * )
               FROM {$this->table}
               WHERE MATCH (
               name, description )
               AGAINST (
              ?
              ) LIMIT 1000";

        if ($stmt = $this->mysqliInstanse->prepare($query) )  {
            $stmt->bind_param("s", $searchString);
        } else {
            throw new Exception("Не удалось подготовить SQL запрос при попытке поиска по сайту");
        }
        if (!$stmt->execute()) {
           throw new Exception("Не удалось выполнить поиск (" . $stmt->errno . ") " . $stmt->error);
        } else {
           $stmt->bind_result($num);
           $stmt->fetch();
           $stmt->close();
           return $num;
    }
  }

    public function getResults($string, $pagenum, $filesPerPage) {
      
      $searchString = "'{$string}*'";

      $max = 'LIMIT ' . ($pagenum - 1) * $filesPerPage . ',' . $filesPerPage; 

    /*	$query = "SELECT *,
    	       MATCH (name, description) AGAINST (? IN BOOLEAN MODE) AS rel
    	       FROM {$this->table}
               WHERE MATCH (name, description) AGAINST (? IN BOOLEAN MODE)
               ORDER BY REL DESC {$max}";
*/
        $query = "SELECT id, name, description, size,
             MATCH (name, description) AGAINST (? IN BOOLEAN MODE) AS rel
             FROM {$this->table}
               WHERE MATCH (name, description) AGAINST (? IN BOOLEAN MODE)
               ORDER BY REL DESC {$max}";

        if ($stmt = $this->mysqliInstanse->prepare($query) )  {
            $stmt->bind_param("ss", $searchString, $searchString);
        } else {
            throw new Exception("Не удалось подготовить SQL запрос при попытке поиска по сайту");
        }
        if (!$stmt->execute()) {
           throw new Exception("Не удалось выполнить поиск (" . $stmt->errno . ") " . $stmt->error);
        } else {
          $stmt->bind_result($id, $name, $description, $size, $rel);
          $files = array();
          while ($stmt->fetch()) {
          $fields = array(
          'id' => $id,
          'name' => $name,
          'description' => $description,
          'size' => $size
          );
          $file = File::constructFromArray($fields);
          $files[] = $file;
          }
          $stmt->close();
          return $files;
        }
    }
	  public function __construct(mysqli $mysqli) {
    $this->mysqliInstanse = $mysqli;
  }
}