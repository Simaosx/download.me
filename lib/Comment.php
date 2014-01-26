<?php
class Comment extends Entity {
	protected $author;
	protected $body;
	protected $unixtime;
    
    protected $fileId;
	protected $id;
	protected $path;
	protected $children;
	protected $parentId;
    protected $siblings;
    
    public $properties = array(
        "author",
        "body",
        "unixtime",
        "fileId",
        "id",
        "path",
        "children",
        "parentId",
        "level",
        "siblings"
    	);
    public function getLevel () {
              $levels = explode(".", $this->path);
               $level = count($levels) - 2;
               return $level;
    }

}