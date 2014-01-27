<?php
class CommentsController {
	
    private $gateway;
    private $requestVars;
    private $cache;

    public function getParamsFromCache() {
      return $this->cache;
    }

	public function addComment($async=false) {
       $params = array(
       	'unixtime' => time(),
       	'children' => 0
       	);

        $arr = array_merge($params, $this->requestVars);
        //если комментарий не является ответом на другой комментарий
        if (!isset($arr['parentId'])) {
          //смотрим, сколько прямых комментариев есть у файла (т.е. таких, у кот. parentId = null) = $n
          //и присваиваем путь $path = ++$n;
        $siblingsNum = $this->gateway->getSiblingsNum($arr['fileId'], NULL);
        $arr['path'] = $arr['fileId'] . "." . ($siblingsNum + 1);
        } else {
        	 $siblingsNum = $this->gateway->getSiblingsNum($arr['fileId'], $arr['parentId']);
           $arr['path'] = $this->gateway->getPathById($arr['parentId']) . "." . ($siblingsNum + 1);
           /*
           if(!$childrenUpdated = $this->gateway->addChild($arr['parentId'])) {
            return false;
           } 
           */
        }

        $arr['author'] .= ($arr['author'] == "") ? "Аноним" : ""; 
        $arr['siblings'] = $siblingsNum;

        if ($arr['body'] == "") {
          $this->cache = $arr;
          return false; 
        }
        

        $parts = explode(".", $arr['path']);
        //var_dump($parts);
        //die();
        foreach ($parts as $key => $value) {
          while (strlen($value) < 4) {
            $value = "0" . $value;
          }
          $parts[$key] = $value;
        }
        $arr['path'] = implode(".", $parts);

        $comment = Comment::constructFromArray($arr);
        if ($async == false) {
          $comments = $this->gateway->postComment($comment)->getAllFileComments($comment->fileId);
          return $comments;
        } else {
          if (isset($arr['parentId']) ) {
            $relativesNum = $this->gateway->getDescendantsNum($arr['parentId']);
          } 
          $this->gateway->postComment($comment);
          if (isset($arr['parentId']) ) {
          $family = array($comment, $relativesNum);
          return $family;
          } else return $comment;
      }
	}
public function __construct(CommentGateway $gateway, $requestVars) {
	$this->gateway = $gateway;
    $this->requestVars = $requestVars;
    return $this;
}


}