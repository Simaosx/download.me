<?php

class CommentGateway {
    
    private $mysqliInstanse;
    private $table = "comments"; 

    public function postComment(Comment $comment) {
      $query = "INSERT INTO {$this->table} (parent_id, file_id, unixtime, author, body, path) VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt = $this->mysqliInstanse->prepare($query)) {
    	$parentId = $comment->parentId;
    	$fileId = $comment->fileId;
    	$unixtime = $comment->unixtime;
    	$author = $comment->author;
    	$body = $comment->body;
    	$path = $comment->path;
      $stmt->bind_param("iiisss", $parentId, $fileId, $unixtime, $author, $body, $path);
    } 
    if (!$stmt->execute()) {
      return false;
        throw new Exception("Не удалось добавить данные о файле в таблицу: (" . $stmt->errno . ") " . $stmt->error);
    }
    $stmt->close();
        $id = $this->mysqliInstanse->insert_id;
    $comment->id = $id;
    return $this;
    }
    public function getSiblingsNum($fileId, $parentId) {
      $queryending = ($parentId === NULL) ? "IS NULL" : "= ?";
      $query = "SELECT file_id, parent_id FROM {$this->table}
      WHERE file_id = ? AND parent_id {$queryending}";
      
      $stmt = $this->mysqliInstanse->prepare($query);
      if ($parentId === NULL) {
          $stmt->bind_param("i", $fileId);
      } else {
      $stmt->bind_param("ii", $fileId, $parentId);
      }

      if (!$stmt->execute()) {
        throw new Exception("Не удалось получить количество братских комментариев: (" . $stmt->errno . ") " . $stmt->error);
     }
     $stmt->store_result();
     $num  = $stmt->num_rows;
     $stmt->close();
     return $num;
  }
  
  public function  getAllFileComments($fileId) {
     $query = "SELECT id, unixtime, author, body, path, parent_id FROM {$this->table} WHERE file_id = ? ORDER BY path ASC";
     
     $stmt = $this->mysqliInstanse->prepare($query);
     $stmt->bind_param('i', $fileId);
     $stmt->execute();
     $stmt->bind_result($id, $unixtime, $author, $body, $path, $parentId);
      $comments = array();
    while ($stmt->fetch()) {
      $fields = array(
        'id' => $id,
        'unixtime' => $unixtime,
        'author' => $author,
        'body' => $body,
        'path' => $path,
        'parentId' => $parentId
      );
      $comment = Comment::constructFromArray($fields);
      $comments[] = $comment;
    }
    $stmt->close();
 return $comments;
  }

  public function getPathById($id) {
    $query = "SELECT id, path FROM {$this->table} WHERE id = ?";
    $stmt = $this->mysqliInstanse->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($id, $path);
    $stmt->fetch();
    $stmt->close();
    return $path;
  }

  public function getDescendantsNum($id) {
    $query = "SELECT COUNT(*) FROM {$this->table} WHERE path LIKE CONCAT((SELECT path FROM comments WHERE id = ?), '%') AND id <> ?";
      $stmt = $this->mysqliInstanse->prepare($query);
      $stmt->bind_param("ii", $id, $id);
      if (!$stmt->execute()) {
        throw new Exception("Не удалось получить количество потомков: (" . $stmt->errno . ") " . $stmt->error);
     }
     $stmt->bind_result($num);
     $stmt->fetch();
     $stmt->close();
     return $num; 
  }
  
  public function __construct(mysqli $mysqli) {
         $this->mysqliInstanse = $mysqli;
  }
}