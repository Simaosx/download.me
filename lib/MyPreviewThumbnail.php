<?php
class MyPreviewThumbnail
{
    const MODE_SCALE = "scale";
    const MODE_CROP = "crop";
    private static $srcPic;
    private static $srcName;
    private static $srcExt;
    private static $newName = "";
    private static $newPic;
    private static $type;
    private static $thumbDir = "";
    
    public static function getType()
    {
        return self::$type;
    }
    public static function getThumbDir()
    {
        return self::$thumbDir;
    }
    public static function setThumbDir($value)
    {
        self::$thumbDir = $value;
    }
    
    protected static function setNewName($src, $newWidth, $newHeight, $mode, $sameDir)
    {
        /**
         *найдём исходное имя картинки
         */
        $subfolders = explode("/", $src);
        $srcName    = array_pop($subfolders);
        $dir        = implode("/", $subfolders);
        
        if ($sameDir === true) {
            self::$thumbDir = $dir;
        }
        $pattern       = "/([\\-\\s\\w]+)(\\.\\w+)/ui";
        $Name          = preg_replace($pattern, '${1}', $srcName);
        $ext           = preg_replace($pattern, '${2}', $srcName);
        self::$srcName = $Name;
        self::$srcExt  = $ext;
        
        /**
         *используем его в новом имени для превьюшки
         */
        return self::$newName = "thumb-{$newWidth}x{$newHeight}-{$mode}-" . self::$srcName;
    }
    
    /*Создаёт новое изображение из исходного*/
    private static function getSrcPic($src, $type)
    {
        if ($type == IMAGETYPE_GIF) {
            $im = imagecreatefromgif($src);
        } elseif ($type == IMAGETYPE_JPEG) {
            $im = imagecreatefromjpeg($src);
        } elseif ($type == IMAGETYPE_PNG) {
            $im = imagecreatefrompng($src);
        } else {
            throw new myPreviewException("Данный формат {$type} не поддерживатся");
        }
        return $im;
    }
    /**
     *Сохраняет картинку в папку назначения, присваивая соответствующее расширение.
     *Если передать четвертым параметром true, просто скопирует файл, переданный в первом параметре
     *в папку назначения. 
     */
    private static function saveToFile($pic, $type, $saveTo, $copy = false)
    {
        if ($pic == null) {
            throw new myPreviewException("Wrong argument type!. MyPreviewThumbnail::savatoFile() first param expecting to be filename, null given ");
        }
        $path = $saveTo . self::$srcExt;
        if ($copy) {
            copy($pic, $path);
        } else {
            switch ($type) {
                case IMAGETYPE_GIF:
                    imagegif($pic, $path);
                    break;
                case IMAGETYPE_JPEG:
                    imagejpeg($pic, $path);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($pic, $path);
                    break;
                default:
                    throw new myPreviewException("Данный формат {$type} не поддерживатся");
            }
        }
        return $path;
    }
    
    public static function sendHeader($type)
    {
        switch ($type) {
            case IMAGETYPE_GIF:
                header('Content-Type: image/gif');
                break;
            case IMAGETYPE_JPEG:
                header('Content-Type: image/jpeg');
                break;
            case IMAGETYPE_PNG:
                header('Content-Type: image/png');
                break;
        }
    }
    
    /**методы createThumbScale и createThumbCrop
     *выполняют всю работу по созданию уменьшенной копии.
     *Они проверяют действительно ли необходимо уменьшать картинку,
     *и если да - делают это.
     *если же размеры картинки итак удовлетворяют условия,
     *эти методы просто передают исходное изображение и путь для сохранения
     *методу saveToFile c параметром $copy = true.
     */
    private static function createThumbScale($src, $width, $height, $newWidth, $newHeight)
    {
        //$newPath = "thumbs/" . self::$newName;
        $newPath = self::$thumbDir . "/" . self::$newName;
        /**
         *найдем коэффициент уменьшения
         *он будет одним и тем же для ширины и высоты если MODE_SCALE
         */
        $factor  = (($width / $newWidth) > ($height / $newHeight)) ? ($width / $newWidth) : ($height / $newHeight);
        /**
         *Чтобы избежать растягивания исходного изображения выполним следующую проверку
         */
        if ($factor > 1) {
            /**
             *теперь необходимо рассчитать величину для измерения, которое не ограничено
             *параметром, если это справедливо для одного из них
             */
            $dstW = ($newWidth == 0) ? round($width / $factor) : min($newWidth, round($width / $factor));
            $dstH = ($newHeight == 0) ? round($height / $factor) : min($newHeight, round($height / $factor));
            
            self::$newPic = imagecreatetruecolor($dstW, $dstH);
            imagecopyresampled(self::$newPic, self::$srcPic, 0, 0, 0, 0, $dstW, $dstH, $width, $height);
            $path = self::saveToFile(self::$newPic, self::$type, $newPath);
            imagedestroy(self::$newPic);
            
        } elseif ($factor <= 1) {
            /**
             *Если нет необходимости уменьшать,
             *возвращаем исходное изображение, а точнее его копию.
             */
            $path = self::saveToFile($src, self::$type, $newPath, true);
        }
        return $path;
    }
    
    private static function createThumbCrop($src, $width, $height, $newWidth, $newHeight)
    {
        $newPath = self::$thumbDir . "/" . self::$newName;
        
        $factor = (($width / $newWidth) < ($height / $newHeight)) ? ($width / $newWidth) : ($height / $newHeight);
        if ($factor >= 1) {
            $dstW = max($newWidth, round($width / $factor));
            $dstH = max($newHeight, round($height / $factor));
            /**
             *Определение окончательных размеров
             */
            if ($newWidth != 0 && $newHeight != 0) {
                $finalW = min($newWidth, $dstW);
                $finalH = min($newHeight, $dstH);
            } elseif ($newHeight == 0) {
                $finalW = min($newWidth, $dstW);
                $finalH = $dstH;
            } elseif ($newWidth == 0) {
                $finalW = $dstW;
                $finalH = min($newHeight, $dstH);
            }
            /**
             *создаём временный файл уменьшенного изображения
             *Далее будем обрезать его, если есть необходимость
             */
            $dst = imagecreatetruecolor($dstW, $dstH);
            imagecopyresampled($dst, self::$srcPic, 0, 0, 0, 0, $dstW, $dstH, $width, $height);
            
            $temp     = self::$thumbDir . "/" . "temp_" . self::$srcName;
            $tempPath = self::saveToFile($dst, self::$type, $temp);
            imagedestroy($dst);
            $im = self::getSrcPic($tempPath, self::$type);
            /**
             *Если картинка горизонтальная
             */
            if (($dstW >= $newWidth) && ($newWidth != 0)) {
                /**
                 *обрезаем справа и слева
                 */
                self::$newPic = imagecreatetruecolor($finalW, $finalH);
                imagecopyresampled(self::$newPic, $im, 0, 0, floor(($dstW - $finalW) / 2), 0, $finalW, $finalH, $finalW, $finalH);
            }
            /**
             *Если картинка вертикальная
             */
            elseif (($dstH >= $newHeight) && ($newHeight != 0)) {
                /**
                 *обрезаем cверху и снизу
                 */
                self::$newPic = imagecreatetruecolor($finalW, $finalH);
                imagecopyresampled(self::$newPic, $im, 0, 0, 0, floor(($dstH - $finalH) / 2), $finalW, $finalH, $finalW, $finalH);
            }
            unlink($tempPath); //удаляем временный файл
            $path = self::saveToFile(self::$newPic, self::$type, $newPath);
            imagedestroy(self::$newPic);
        } elseif ($factor < 1) {
            /**
             *Если изображение итак достаточно мало,
             *возвращаем исходное изображение, а точнее его копию.
             */
            $path = self::saveToFile($src, self::type, $newPath, true);
        }
        return $path;
    }
    
    /**
     *Этот метод просто создаёт ссылку на уменьшенную копию изображения.
     *Если для файла с таким именем и параметрами уменьшения уже была создана превью,
     *Ссылка будет указывать на уже существующую превью. Если же запрос с такими параметрами новый, 
     *по этой ссылке еще ничего нет. Когда пользователь перейдет по ней, Apache перенаправит
     *его на страницу, указанную в файле конфигурации сервера как обработчик ошибки 404.
     *На ней запустится скрипт генерирующий превью, который создаст и сохранит нужную превью
     *а затем выдаст её в браузер. Это сделано для того, чтобы генерация превьюшки происходила только один раз, 
     *и при повторном обращении картинка отдавалась с диска. При этом мы бы не хотели, 
     *чтобы сервер уже не будет запускать для этого медленный PHP-скрипт. 
     */
    public static function link($src, $newWidth = 0, $newHeight = 0, $mode = self::MODE_SCALE)
    {
        $arr  = preg_split("!\\/!ui", $src, -1, PREG_SPLIT_NO_EMPTY);
        $name = array_pop($arr);
        $dir  = implode("/", $arr);
        
        $src = $dir . "/" . $name;
        
        $string = "thumb" . "/{$newWidth}x{$newHeight}/{$mode}/{$src}";
        return $string; 
    }
    /**
     *есть 2 режима уменьшения, когда, например, мы пытаемся вписать картинку
     * 1000×200 в квадрат 100×100: CROP — уменьшает картинку до 500×100 и отрезает
     * правую и левую части, SCALE — уменьшает картинку до 100×20
     *По умолчанию, будет сохранять уменьшенные коппи в папку оригинала
     */
    public static function resize($src, $newWidth, $newHeight, $mode, $sameDir = true)
    {
        if (file_exists($src) && getimagesize($src)) {
            list($width, $height, self::$type) = getimagesize($src);
        } else {
            if (!file_exists($src)) {
                throw new myPreviewException("Исходный файл {$src} не найден");
            } elseif (!getimagesize($src)) {
                throw new myPreviewException("файл {$src} не является изображением поддерживаемого формата");
            }
        }
        if (!$width || !$height) {
            throw new myPreviewException("Данный формат не поддерживатся");
        }
        self::$srcPic = self::getSrcPic($src, self::$type);
        self::setNewName($src, $newWidth, $newHeight, $mode, $sameDir);
        if ($mode == self::MODE_SCALE) {
            $path = self::createThumbScale($src, $width, $height, $newWidth, $newHeight);
        } elseif ($mode == self::MODE_CROP) {
            $path = self::createThumbCrop($src, $width, $height, $newWidth, $newHeight);
        } else {
            throw new myPreviewException("Параметр $mode, переданный методу MyPreviewThumbnail::resize, не верный.");
        }
        return $path;
    }
}


