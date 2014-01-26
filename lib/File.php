<?php
class File extends Entity {

    protected $id;
	protected $name;
	protected $size;
	protected $mime;
	protected $extension = '';
	protected $md5;
	protected $unixtime;
	protected $path;
  protected $description;

	protected $properties = array(
      "id",
      "name",
      "size",
      "mime",
      "extension",
      "md5",
      "unixtime",
      "path",
      "description");

	public function setId($id) {
		if (isset($this->id)) {
			throw new Exception("У файла уже есть id, и его нельзя переназначить: " . $this->name . "id: " . $this->id);
		} else {
		  $this->id = $id;
		} 
	}
    public function getRelativeLink() {
    	return "/f/" . $this->id;
    }
    public function getPrettySize() {
    	$prettySize = array();
    	$value = $this->size/1024;
    	$prettySize['value'] = round($value, 2);
    	if ($value < 1) {
          $prettySize['measure'] = 'байт';
    	} elseif (($value >= 1) && ($value<1024)) {
          $prettySize['measure'] = "КБ";
        } elseif ($value >= 1024) {
        	$prettySize['value'] = round($prettySize['value']/1024, 2);
        	$prettySize['measure'] = "МБ";
        }
    $prettySize = $prettySize['value'] . " " . $prettySize['measure'];
    return $prettySize;
    }

    public function isPicture() {
      try {
    	$isPicture = getimagesize($this->path);
      return $isPicture;
    } catch(Exception $e) {
      return false;
    }
	}

	public function isResizable() {
		$info = getimagesize($this->path);
		if (
			($info[2] == 1) || 
			($info[2] == 2) ||
			($info[2] == 3) ) {
			return true;
		} else return false;
	}

  public function getExtension() {
   $parts = explode(".", $this->name);
   $ext = array_pop($parts);
   return $ext;
  }
	
	public function isPlayable() {
    $playable = array('mp3', 'wav', 'ogg');
    if (in_array($this->getExtension(), $playable)) {
      return true;
    } else {
      return false;
    }
  } 
}