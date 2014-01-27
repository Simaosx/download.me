<?php
error_reporting(-1);
mb_internal_encoding('utf-8');

require 'vendor/slim/Slim/Slim.php';
require 'config.php';
require 'vendor/getid3/getid3.php';
spl_autoload_register(function ($class) {
  if (!preg_match("/\\\/ui", $class))
    require_once 'lib\\/' . $class . '.php';
});

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim( array(
    'templates.path' => 'templates',
    'dbSettings' => $database,
    'uploadDir' => $uploadDir,
    'domainName' => $domainName,
    'filesPerPage' => $filesPerPage,
    'maxFileSize' => $maxFileSize,
    'debug' => false
    ));

function h($text) {
    return htmlspecialchars($text, ENT_QUOTES);
}

//делаем объект mysqli синглтоном с помощью средств Slim
$app->container->singleton('mysqli', function() use($app) {
    $db = $app->config('dbSettings');
    $host = $db['host'];
    $user = $db['user'];
    $pswd = $db['password'];
    $name = $db['name'];
    return new mysqli($host, $user, $pswd, $name);
});
 if ($app->mysqli->connect_errno) {
   throw new Exception("Не удалось подключиться к MySQL: (" . $app->mysqli->connect_errno . ") " . $app->mysqli->connect_error);
}
if (!$app->mysqli->set_charset("utf8")) {
   throw new Exception("Ошибка при загрузке набора символов utf8: \n", $app->mysqli->error);
}
$app->mysqli->query("SET NAMES 'utf8'");

$app->container->singleton('getID3', function() use ($app) {
  return new getID3;
});

//делаем объект FileGateway синглтоном, чтобы не создавать его каждый раз,
//когда требуется обращаться к бд.
$app->container->singleton('FileGateway', function() use ($app) {
    return new FileGateway($app->mysqli);
});
//то же самое со шлюзом для комментариев
$app->container->singleton('CommentGateway', function() use ($app) {
    return new CommentGateway($app->mysqli);
});

$app->container->singleton('SearchGateway', function() use ($app) {
  return new SearchGateway($app->mysqli);
});

$app->get('/', function () use ($app) {
  session_start();
  $flash = isset($_SESSION['slim.flash']) ? $_SESSION['slim.flash'] : null;
    $app->render('frontpage.phtml', array(
        "flash" => $flash,
        "title" => "Download.me - файлообменник",
        "maxFileSize" => $app->config('maxFileSize'),
        'active' => 'home' 
        ));
});

$app->post('/upload(/:async)', function ($async=false) use ($app) {

    $uploader = new Uploader($app->config('uploadDir'), $app->FileGateway);
    $id = $uploader->uploadFile($_FILES);

    if ($id && !$async) {
      $app->response->redirect("/f/{$id}", 303);
    } elseif ($id && $async) {
      header("Content-Type: application/json");
      echo json_encode(array(
        'id' => $id));
     } elseif (!$id && !$async) {
    $app->flash('error', $uploader->getError());
    $app->response->redirect('/', 303);
    }
});

$app->get('/upload-progress', function() use ($app) {
  $uploader = new Uploader($app->config('uploadDir'), $app->FileGateway);
  $data = $uploader->getUploadProgress();
  if ($data) {
    $data['ok'] = true;
    header("Content-Type: application/json");
    echo json_encode($data);
  } else {
    $data = array(
      "ok" => false);
    header("Content-Type: application/json");
    echo json_encode($data);
  } 
});

$app->get('/f/thumb/:res/:mode/:src+', function ($res, $mode, $src) use ($app) {
    $sides = explode("x", $res);
    $width = $sides[0];
    $height = $sides[1];
    $origFile = implode("/", $src);
    $origFileValid = urldecode($origFile);
    $origName = array_pop($src);
    $origDir = implode("/", $src);
    $thumbName = $origDir . "/thumb-{$res}-{$mode}-{$origName}";
    if (file_exists($thumbName)) {
        $info = getimagesize($thumbName);
        MyPreviewThumbnail::sendHeader($info[2]);
        readfile($thumbName);
    } else {
      try {
       $brandNewPic = MyPreviewThumbnail::resize($origFileValid, $width, $height, $mode, true);
      } catch (myPreviewException $e) {
       echo $e;
      }
      MyPreviewThumbnail::sendHeader(MyPreviewThumbnail::getType());
      readfile($brandNewPic);
    }
    });

$app->get('/f/:id', function ($id) use ($app) {
  session_start();
  $flash = $_SESSION['slim.flash'];
  //var_dump($flash);
    $file = $app->FileGateway->getData($id);
    $comments = $app->CommentGateway->getAllFileComments($id);
    if($file) {
         $app->render('details.phtml', array(
          'file' => $file,
          'title' => $file->name,
          'comments' => $comments,
          'app' => $app,
          'flash' => $flash
         ));
    } else {
        $app->notFound();
    }
});

$app->get('/files(/:page)', function ($page=1) use ($app) {
  $num = 100;
  $files = $app->FileGateway->getLatestFiles($num);

  $app->render('latestFiles.phtml', array(
    'files' => $files,
    'title' => 'Последние файлы',
    'active' => 'files'
    ));
});

$app->get('/about', function () use ($app) {
    $app->render('about.phtml', array(
        'title' => 'О нас',
        'active' => 'about'
        ));
});

$app->post('/comment', function () use ($app) {
  $requestVars = $app->request->post();
  $commentHandler = new CommentsController($app->CommentGateway, $requestVars);
  $comments = $commentHandler->addComment();
  if (!$comments) {
    $app->render('commentError.phtml', array(
      "params" => $commentHandler->getParamsFromCache(),
      "title" => "Заполните все поля"
      ));
  } else {
  //рендерим details с комментами (пока без ajax)
  $app->response->redirect("/f/{$requestVars['fileId']}", 303);
}
});

$app->get('/showFullPicture/:id', function($id) use ($app) {
  $file = $app->FileGateway->getData($id);
  if (file_exists($file->path)) {
    $app->render('pictureView.phtml', array(
      'title' => $file->name,
      'file' => $file,
      'path' => $file->path
      ));
  }
});

$app->post('/modify/:id', function($id) use ($app) {
  session_start();
  $description = $app->request->post('description');
  if (isset($_SESSION['fileId'])
   && array_key_exists("$id", $_SESSION['fileId']) ) {
    /*
    $isSet = $app->FileGateway->getDescription($id);
    if (!$isSet) {
    $app->FileGateway->addDescription($id, $description );
    $message = "Описание файла успешно добавлено.";
    } else {
    $app->FileGateway->changeDescription($id, $description);
    $message = 'Описание файла успешно изменено.';
    }
    */
  $app->FileGateway->changeDescription($id, $description );
  $app->flash('success', 'Описание файла успешно обновлено');
  } 
  $app->response->redirect("/f/{$id}", 303);
});

$app->get('/search(/:page)', function($page=1) use ($app) {
  $queryString = $app->request->get('string');
  $searchHandler = new SearchController($app->SearchGateway, $app->config('filesPerPage'));
  $totalFilesNum = $searchHandler->countResults($queryString);
  $files = $searchHandler->find($queryString, $page);
  $last = $searchHandler->countPages();
  $message = $searchHandler->getHeaderString($queryString);
  $app->render('searchResults.phtml', array(
    'files' => $files,
    'title' => "Результаты поиска",
    'message' => $message,
    'query' => $queryString,
    'curPage' => $page,
    'last' => $last,
    'query' => $queryString
    ));

});

$app->post('/comment/async', function() use ($app) {
  $commentHandler = new CommentsController($app->CommentGateway, $app->request->post());
  $result = $commentHandler->addComment(true);
   if (is_array($result)) {
    list($comment, $relativesNum) = $result; 
  } else {
    $comment = $result;
    $relativesNum = null;
  }
  if (!$comment) {
    header("Content-Type: application/json");
    echo json_encode(array(
    'ok' => false
    ));
    exit;
   } else {
    $html = $app->view->fetch('comment.phtml', array(
    'comment' => $comment));
    header("Content-Type: application/json");
    echo json_encode(array(
    'ok' => true,
    'relativesNum' => $relativesNum,
    'parentId' => $comment->parentId,
    'html' => $html,
     'id' => $comment->id
     ));
    exit;
  }
});

$app->post('/delete', function() use ($app) {
  session_start();
  $id = $app->request->post('id');
  if ($id 
    && isset($_SESSION['fileId']) 
    && array_key_exists("$id", $_SESSION['fileId']) ) { 
    $file = $app->FileGateway->getData($id);
    if ($file) {
      $dir = dirname($file->path);
      $files = scandir($dir);
      foreach ($files as $file) {
        if ($file == "." || $file == "..") {
          continue;
        }
        $filePath = $dir . "/" . $file;
        unlink($filePath);
      }
      rmdir($dir);
    } 
  $app->FileGateway->deleteFile($id);
  $app->response->redirect("/successRemoval", 303);
 } else {
  $app->response->redirect('/', 303);
 }
});

$app->get('/successRemoval', function() use ($app) {
  $app->render('successRemoval.phtml', array(
    'title' => 'Файл удалён')); 
});

$app->get('/rules', function() use ($app) {
  $app->render('rules.phtml', array(
    'title' => 'Правила',
    'maxFileSize' => $app->config('maxFileSize')));
});

$app->notFound(function () use ($app) {
    $app->render('fileNotFound.phtml', array(
        'title' => '404 - Страница не найдена'
        ));
});

$app->error(function () use ($app) {
    $app->render('error.phtml', array(
      'title'=> "Сервис временно недоступен"));
});

$app->run();
