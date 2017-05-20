<?php

namespace Credits;

class Finder
{
    private $video;
    private $tmpDir;

    function __construct(\SplFileInfo $video, $tmpDir = null)
    {
        $this->video = $video;
        $this->tmpDir = sys_get_temp_dir();
        if (!empty($tmpDir)) {
            if (!file_exists($tmpDir)) {
                mkdir($tmpDir, 0777);
            }
            $this->tmpDir = $tmpDir;
        }
    }

    function findCredits()
    {
        $videoPath = $this->video->getPathname();
        $fileName = $this->buildFileName();

        $videoCCDir = $this->tmpDir."/fc-{$fileName}";

        if (!file_exists($videoCCDir)) {
            mkdir($videoCCDir, 755);
        }
        $duration = $this->getVideoDuration();
        $searchPoint = (int)($duration - ($duration/4));

        $found = false;
        while (!$found) {
            $pointTime = $searchPoint;
            $searchPoint = (int)(($duration + $pointTime)/2);
            if ($duration < $searchPoint) {
                break;
            }

            $count = 0;
            for ($i=0; $i <= 3; $i++) { 
                $pointTime+=$i;
                $fileImg = "{$videoCCDir}/{$pointTime}.png";
                if (file_exists($fileImg)) {
                    unlink($fileImg);
                    break;
                }
                
                $shot = $this->getImageText($fileImg, $pointTime);
                unlink($fileImg);
                if (!empty($shot)) {
                    if ($count++ === 2) {
                        $found = true;
                    }
                }
            }
        }
        rmdir($videoCCDir);
        $response = 0;
        if ($found) {
            $response = $pointTime;
        }
        return $response;
    }

    function findCreditsStartTime($pointCredits)
    {
        $fileName = $this->buildFileName();
        $videoCCDir = $this->tmpDir."/fcst-{$fileName}";
        if (!file_exists($videoCCDir)) {
            mkdir($videoCCDir, 0700);
        }
        $duration = $this->getVideoDuration();

        $lap = 0;
        $count = 0;
        $found = false;
        while (!$found) {
            $lap += 5;
            $break = $pointCredits - $lap;
            $fileImg = "{$videoCCDir}/{$break}.png";
            if (file_exists($fileImg)) {
                unlink($fileImg);
                break;
            }

            $shot = $this->getImageText($fileImg, $break);
            unlink($fileImg);
            if (empty($shot)) {
                if ($count++ === 2) {
                    $found = true;
                }
            } else {
                $count = 0;
            }
        }
        rmdir($videoCCDir);
        $response = 0;
        if ($found) {
            $response = $break;
        }
        return $response;
    }

    public function getVideoDuration($round = true)
    {
        $ffmpeg = \FFMpeg\FFMpeg::create();
        $duration = $ffmpeg->getFFProbe()
                    ->format($this->video->getPathname())
                    ->get('duration');
        if ($round) {
            $duration = (int)$duration;
        }
        return $duration;
    }

    function getImageText($img, $seconds)
    {
        $ffmpeg = \FFMpeg\FFMpeg::create();
        $video = $ffmpeg->open($this->video->getPathname())
                    ->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($seconds))
                    ->save($img);
        $shot = (new \TesseractOCR($img))->run();
        return $shot;
    }

    function buildFileName()
    {
        $baseName = pathinfo(
            $this->video->getBasename(),
            PATHINFO_FILENAME
        );
        $baseName = preg_replace('/\s+/', '-', $baseName);
        return $baseName;
    }
}
