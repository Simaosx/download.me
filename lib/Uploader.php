<?php

class Uploader {

    private $uploadDir;
    private $errorMessage = '';
    private $dbGateway;

    public function __construct($uploadsFolder, $gateway) {
        $this->uploadDir = $uploadsFolder;
        $this->dbGateway = $gateway;
    }

    public function getError() {
    	return $this->errorMessage;
    }

    private function makeDir($total) {
     //будет создавать уникальную директорию для каждого файла
            $dir = sprintf( $this->uploadDir . "/" . '%d' . "/" . '%d', floor($total / 1000), $total % 1000);
            if (!file_exists($dir) && mkdir($dir, 0755, true)) {
                return $dir;
            } else {
                throw new Exception("Не удалось создать директорию для загружаемого файла");
            }
    }
    //исключаем потенциально опасные символы из имени файла
    private function getSafeFileName($userfile) {
        $subFolders = explode("/", $userfile['name']);
        $name = array_pop($subFolders);
        $name = trim($name);
        //Избавляемся от '.' в начале имени файла и '..' 
        $forbiddenNames = array("!^\\.+!ui", "!\\.{2,}!ui");
        $name = preg_replace($forbiddenNames, array("", ""), $name);
        return $name .= ($name == "") ? "noname" : "";
    } 

    private function encodeName($userfile) {
        $subFolders = explode("/", $userfile['name']);
        $name = array_pop($subFolders);
        $name = trim($name);
        $pattern = "/(.*)(\\.\\w+)$/ui";
        $ext = preg_replace($pattern, "$2", $name);
        $onlyName = preg_replace($pattern, "$1", $name);
        $encoded = md5($onlyName) . $ext;
        return $encoded;
    }
    
    public function getUploadProgress() {
      session_start();
      $data = array();
      if(isset($_SESSION['upload_progress_d-loadme']) && is_array($_SESSION['upload_progress_d-loadme'])) {
        $percent = ($_SESSION['upload_progress_d-loadme']['bytes_processed'] * 100 ) / $_SESSION['upload_progress_d-loadme']['content_length'];
        //$percent = round($percent);
        $data = array(
         'percent' => $percent,
         'content_length' => $_SESSION['upload_progress_d-loadme']['content_length'],
         'bytes_processed' => $_SESSION['upload_progress_d-loadme']['bytes_processed']
       );
      return $data;  
     } else {
        return false;
     }
    }

    private function getFileInfo($userfile, $name, $encName) {
            $fileMetaData = array(
                "name" => $name,
                "size" => $userfile['size'],
                "mime" => $userfile['type'],
                "unixtime" => time(),
                "md5" => $encName
                );
     return $fileMetaData;
    } 

    public function uploadFile(array $files) {
        session_start();
        $userfile = $files['userfile'];
        if (!$userfile) {
            $this->errorMessage = "Вы не выбрали файл.";
            return false;
        }
        if ($userfile['error'] > 0) {
          if ( $userfile['error'] <= 2 ) {
     	    $this->errorMessage = 'Размер файла больше допустимого.';
          } elseif (!$userfile['name']) {
     	    $this->errorMessage = "Вы не выбрали файл для загрузки";
          } else {
           throw new Exception("Uploader::uploadFile was unable to upload file");
          }
        return false;
        }

        $name = $this->getSafeFileName($userfile);

        $encName = $this->encodeName($userfile);

        //подготовим информацию о файле, для передачи шлюзу соединения с бд
        $fileMetaData = $this->getFileInfo($userfile, $name, $encName);
        
        $file = File::constructFromArray($fileMetaData);
        //Вызываем метод insert шлюза, который внесёт данные о файле в базу данных и присвоит файлу id,
        //полученный из базы. 
        $file = $this->dbGateway->insert($file);
        //общее количество загрузок = id последнего запроса
        $total = $file->id;
        $newDir = $this->makeDir($total);

        $upfile = $newDir . "/" . $encName;
        if (!is_uploaded_file($userfile['tmp_name'])) {
            throw new Exception("File is not uploaded_file");
        }
        if (!move_uploaded_file($userfile['tmp_name'], $upfile)) {
            throw new Exception('Невозможно переместить файл в каталог назначения');
        }
        //если выполнение дошло до сюда, файл был успешно загружен
        //заносим путь к файлу в базу данных
        $success = $this->dbGateway->addPath($upfile, $total);
        //стартуем сессию только если файл был успешно загружен и добавлен в бд

        if ($success === true) {
            //session_start();
            if (!isset($_SESSION['fileId'])) {
                $_SESSION['fileId'] = array();
            }
            $_SESSION['fileId']["$total"] = 0;
        }
    return $total;
    }
}