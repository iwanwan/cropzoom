<?php

session_start();
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

require '/var/www/dev-iwan/imagine/vendor/autoload.php';

//Obtains parameters from POST request
$source = $_POST["imageSource"];
$viewPortW = $_POST["viewPortW"];
$viewPortH = $_POST["viewPortH"];
$pWidth = $_POST["imageW"];
$pHeight =  $_POST["imageH"];

$pImageX = $_POST["imageX"];
$pImageY =  $_POST["imageY"];

$selectorX = $_POST["selectorX"];
$selectorY = $_POST["selectorY"];
$ext = end(explode(".",$_POST["imageSource"]));

$imagine = new \Imagine\Gd\Imagine();
$image = $imagine->open($source);

//Obtain width and height from the original source.
$size   = $image->getSize();
$width  = $size->getWidth();
$height = $size->getHeight();

//resize the image if the width and height doesn't match
if($pWidth != $width && $pHeight != $height){
    $image->resize(new \Imagine\Image\Box($pWidth, $pHeight));
    $size   = $image->getSize();
    $width  = $size->getWidth();
    $height = $size->getHeight();
}

if($_POST["imageRotate"]){
    $angle = $_POST["imageRotate"];
    $image->rotate($angle);
    $rotatedSize   = $image->getSize();
    $rotatedWidth  = $rotatedSize->getWidth();
    $rotatedHeight = $rotatedSize->getHeight();

    //obtain the difference between sizes so we can move the x,y points.
    $diffW = abs($rotatedWidth - $width) / 2;
    $diffH = abs($rotatedHeight - $height) / 2;

    $_POST["imageX"] = ($rotatedWidth > $width ? $_POST["imageX"] - $diffW : $_POST["imageX"] + $diffW);
    $_POST["imageY"] = ($rotatedHeight > $height ? $_POST["imageY"] - $diffH : $_POST["imageY"] + $diffH);

}

$dst_x = $src_x = $dst_y = $src_y = 0;

if($_POST["imageX"] > 0){
    $dst_x = abs($_POST["imageX"]);
}else{
    $src_x = abs($_POST["imageX"]);
}
if($_POST["imageY"] > 0){
    $dst_y = abs($_POST["imageY"]);
}else{
    $src_y = abs($_POST["imageY"]);
}

if($viewPortW > $width || $viewPortH > $height){

    $cropWidth  = ($viewPortW > $width) ? $width : $viewPortW;
    $cropHeight = ($viewPortH > $height) ? $height : $viewPortH;

    $image->crop(
        new \Imagine\Image\Point($src_x, $src_y),
        new \Imagine\Image\Box($cropWidth, $cropHeight)
    );

    //create the viewport to put the cropped image
    $viewportSize  = new Imagine\Image\Box($viewPortW, $viewPortH);
    $viewport = $imagine->create($viewportSize);
    $viewport->paste($image, new \Imagine\Image\Point($dst_x, $dst_y));
    $viewport->crop(
        new \Imagine\Image\Point($selectorX, $selectorY),
        new \Imagine\Image\Box($_POST["selectorW"], $_POST["selectorH"])
    );

    $targetFile = 'tmp/test_'.time().".".$ext;
    $viewport->save($targetFile);

}else{
    $image->crop(
        new \Imagine\Image\Point($src_x, $src_y),
        new \Imagine\Image\Box($viewPortW, $viewPortH)
    );
    $image->crop(
        new \Imagine\Image\Point($selectorX, $selectorY),
        new \Imagine\Image\Box($_POST["selectorW"], $_POST["selectorH"])
    );
    $targetFile = 'tmp/test_'.time().".".$ext;
    $image->save($targetFile);
}

echo $targetFile;