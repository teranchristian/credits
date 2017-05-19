<?php
require_once __DIR__.'/vendor/autoload.php';

$videoDirectory = "./videos";
$iter = new DirectoryIterator($videoDirectory);
foreach ($iter as $file) { 
    if ($file->isDot()) {
        continue;
    }
    $fileName = $file->getFilename();
    $findCredits = findCredits($fileName);
    $startTime = findCreditsStartTime($fileName, $findCredits);
    echo $fileName. gmdate("H:i:s", $startTime)."</br>";
    exit;
}

function findCredits($video) {
    global $videoDirectory;
    
    $fileName = buildFileName($video);
    $videoCCDir = "./tmp/pc-{$fileName}";
    if (!file_exists($videoCCDir)) {
        mkdir($videoCCDir, 0700);
    }

    $videoPath = "{$videoDirectory}/".$video;
    $duration = getVideoDuration($videoPath);

    $searchPoint = (int)($duration - ($duration/4));

    while (true) {
        $pointTime = $searchPoint;
        $searchPoint = (int)(($duration + $pointTime)/2);
        if ($duration < $searchPoint) {
            break;
        }
        $count = 0;

        for ($i=0; $i <= 3; $i++) { 
            $break = $pointTime+$i;
            $fileImg = "{$videoCCDir}/{$fileName}-{$break}.png";
            if (file_exists($fileImg)) {
                break;
            }
            
            $shot = getImageText($videoPath, $fileImg, $break);
            if (!empty($shot)) {
                if ($count++ === 2) {
                    return $pointTime;
                }
            }
        }
    }
    return false;
}

function findCreditsStartTime($video, $findCredits)
{
    global $videoDirectory;
    $fileName = buildFileName($video);
    $videoCCDir = "./tmp/bc-{$fileName}";
    if (!file_exists($videoCCDir)) {
        mkdir($videoCCDir, 0700);
    }
    $videoPath = "{$videoDirectory}/".$video;
    $duration = getVideoDuration($videoPath);

    $lap = 0;
    $count = 0;
    while (true) {
        $lap += 5;
        $break = $findCredits - $lap;
        $fileImg = "{$videoCCDir}/{$fileName}-{$break}.png";
        if (file_exists($fileImg)) {
            break;
        }

        $shot = getImageText($videoPath, $fileImg, $break);
        if (empty($shot)) {
            if ($count++ === 2) {
                return $break;
            }
        } else {
            $count = 0;
        }
    }
    return false;
}

function getVideoDuration($videoPath) {
    $ffmpeg = FFMpeg\FFMpeg::create();
    $duration = (int)$ffmpeg->getFFProbe()
                ->format($videoPath)
                ->get('duration');
    return $duration;
}

function getImageText($videoPath, $fileImg, $break)
{
    $ffmpeg = FFMpeg\FFMpeg::create();
    $video = $ffmpeg->open($videoPath)
                ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($break))
                ->save($fileImg);
    $shot = (new TesseractOCR($fileImg))->run();
    return $shot;
}

function buildFileName($file)
{
    $fileName =  pathinfo($file, PATHINFO_FILENAME);
    $fileName = preg_replace('/\s+/', '-', $fileName);
    return $fileName;
}

// function getClosedCaptioning($video) {
//     global $videoDirectory, $videoBreak;
//     $fileName =  pathinfo($video, PATHINFO_FILENAME);
//     $fileName = preg_replace('/\s+/', '-', $fileName);
//     $videoCCDir = "./tmp/cc-{$fileName}";
//     if (!file_exists($videoCCDir)) {
//         mkdir($videoCCDir, 0700);
//     }

//     $videoPath = "{$videoDirectory}/".$video;
//     $ffmpeg = FFMpeg\FFMpeg::create();
//     $duration = (int)$ffmpeg->getFFProbe()
//                 ->format($videoPath)
//                 ->get('duration');
//     $duration-=60;
//     $endTime = $duration;
//     $startTime = $duration-560;
//     $found = true;
//     while ($found) {
//         $pointTime = (int)(($endTime - $startTime) / 2);
//         echo "$pointTime </br>";
//         echo "s: $startTime </br>";
//         echo "e: $endTime </br>";
//         echo "</br>";
//         $endTime -= $pointTime;
//         if ($pointTime <= 1) {
//             break;
//         }
//         $count = 0;

//         for ($i=0; $i <= 4; $i++) { 
//             $break = $startTime+$pointTime+$i;
//             $fileImg = "{$videoCCDir}/{$fileName}-{$break}.png";
//             echo "</br>";
//             if (file_exists($fileImg)) {
//                 $found = false;
//             }
//                 $video = $ffmpeg->open($videoPath)
//                     ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($break))
//                     ->save($fileImg);

//             $shot = (new TesseractOCR($fileImg))->run();
//             if (empty($shot)) {
//                 $count++;


//                 if ($count === 2) {
//                 echo $fileImg ." ".gmdate("H:i:s", $pointTime);
//                 $found = false;
//                 break;
//                 }
//             }
//             $count = 0;
//         }
//     }

//     // rrmdir($videoCCDir);
//     // exit;*/
// }

 function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir") {
          rrmdir($dir."/".$object);
        } else {
            unlink($dir."/".$object);
        }
       }
     }
     reset($objects);
     rmdir($dir);
   }
 }

// $section = ($percentage / 100) * $totalTime;
// $fromSecond = $totalTime - $section;
// gmdate("H:i:s", $fromSecond);

// $milliseconds = round(microtime(true) * 1000);
// $fileImg = "./tmp/{$milliseconds}.png";
// $video = $ffmpeg->open($videoPath)
//     ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($fromSecond-8))
//     // ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($fromSecond-66))
//     ->save($fileImg);
// $im = imagecreatefrompng($fileImg);
// if($im && imagefilter($im, IMG_FILTER_GRAYSCALE))
// {
//     imagefilter($im, IMG_FILTER_CONTRAST, 10);
//     imagepng($im, "./tmp/{$milliseconds}-g.png");
//     $size = min(imagesx($im), imagesy($im));
//     list($width, $height, $type, $attr) = getimagesize($fileImg);
//     $newHeight = $height - ((10/100) * $height);
//     $im2 = imagecrop($im, ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $newHeight]);
//     if ($im2 !== FALSE) {
//         imagepng($im2, "./tmp/{$milliseconds}-cs.png");
//     }
//     imagedestroy($im);
// echo "  000";
//     exit;
// }



// $im = imagecreatefrompng('example.png');
// $size = min(imagesx($im), imagesy($im));
// $im2 = imagecrop($im, ['x' => 0, 'y' => 0, 'width' => $size, 'height' => $size]);
// if ($im2 !== FALSE) {
//     imagepng($im2, 'example-cropped.png');
// }

// exit;
// echo (new TesseractOCR("./tmp/{$milliseconds}-g.png"))
//     ->run();


// 1/
// Ymdudum Sound 1mm Regan Anslollu Kumpvs 5.: I’hnk‘glaphu Hnda) a. Cu“ 5mm Ben Bradbury Kay l'A Il’ kmhxe Se| m Mun mm Rem: mm Damn! Bury
// 2/
// Makeup and HaurAmst cm Wm"; Pmdud‘mn Snund Richard Regan Ansmlle Kumpls 5a Phnk‘gmphu Hm: ' Craﬂ 5mm nu. Hmdbury Kry l'A Ir Rulhxe 51:! m Mkn 1-“ m
// 3
// Elecmc 1m. Briggs 5m Buy an,» Jake seem thi Immum Dam/s g Kmyom mam (mm mm Hawk: Scnpt Supemsm Manclln Ewmond Makeup and HairAmn
