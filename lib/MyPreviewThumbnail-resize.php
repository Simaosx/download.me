<?php
header("HTTP/1.1 200 OK");
$original = $_SERVER["REQUEST_URI"];
/*
echo "<pre>";
 var_dump($_SERVER['REQUEST_URI']);
 echo "decoded: " . urldecode($_SERVER['REQUEST_URI']);
echo "</pre>";
*/
$srcPattern = "!.*\\/(\\d+)x(\\d+)\\/(\\w+)\\/(.+)!ui";
$params = array();
if (preg_match($srcPattern, urldecode($original), $params)) {
  $src = $params[4];
  //$src = str_replace(".jpg", ".jpeg", $src);
  $newWidth = $params[1];
  $newHeight = $params[2];
  $mode = $params[3];
//var_dump($src);
/*
var_dump($newWidth);
var_dump($newHeight);
var_dump($mode);
*/
try {
$brandNewPic = MyPreviewThumbnail::resize($src, $newWidth, $newHeight, $mode);
} catch (myPreviewException $e) {
   echo $e;
}
//$proto = $_SERVER['REQUEST_PROTOCOL'];
//header("$proto 200 OK Created");
MyPreviewThumbnail::sendHeader(MyPreviewThumbnail::getType());
readfile($brandNewPic);
}