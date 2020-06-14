<?php

namespace Sourceml\Service\Sources;

class Waveform {

    protected $soxExists;

    protected $params;

    public function audioToPng($audioFile, $pngFile, $params = array()) {
        if(!$audioFile || !file_exists($audioFile)) {
            throw new \Exception("can't find audio file");
        }
        if(!$pngFile) {
            throw new \Exception("png file name is empty");
        }
        $this->init($params);
        $this->audioToWav(
            $audioFile,
            $wavFile = $pngFile.".wav"
        );
        $this->wavToPng($wavFile, $pngFile);
        @unlink($wavFile);
    }

    public function init($params = array()) {
        $this->params = array(
            "width" => "650",
            "height" => "100",
            "background" => "",
            "foreground" => "#000000",
            "drawFlat" => true,
            "detail" => 5,
        );
        $this->params = $params + $this->params;
    }

    public function audioToWavCommand($audioFile, $wavFile) {
        return "sox -V \"".$audioFile."\" -b 16 -r 8000 -c 1 \"".$wavFile."\"";
    }

    public function soxCommandExists() {
        if(isset($this->soxExists)) {
            return $this->soxExists;
        }
        $command = "sox";
        $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
        if(
            (
                $process = proc_open(
                    "$whereIsCommand $command",
                    array(
                        0 => array("pipe", "r"), //STDIN
                        1 => array("pipe", "w"), //STDOUT
                        2 => array("pipe", "w"), //STDERR
                    ),
                    $pipes
                )
            ) === false
        ) {
            $this->soxExists = false;
        }
        else {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            $this->soxExists = $stdout != '';
        }
        return $this->soxExists;
    }

    public function audioToWav($audioFile, $wavFile) {
        if(!$this->soxCommandExists()) {
            throw new \Exception("sox command not found");
        }
        if(file_exists($wavFile) && !@unlink($wavFile)) {
            throw new \Exception("can't remove existing wav file");
        }
        system($this->audioToWavCommand($audioFile, $wavFile));
        if(!file_exists($wavFile)) {
            throw new \Exception("can't make wav file");
        }
    }

    public function wavToPng($wavFile, $pngFile) {
        if(!@imagepng($img = $this->wavToImg($wavFile), $pngFile)) {
            throw new \Exception("can't make png file from img object");
        }
        imagedestroy($img);
    }

    public function wavToImg($wavFile) {
        list($r, $g, $b) = $this->html2rgb($this->params["foreground"]);
        if(!($handle = fopen($wavFile, "r"))) {
            throw new \Exception("can't open wav file");
        }

        // wav file header retrieval
        $heading[] = fread($handle, 4);
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = fread($handle, 4);
        $heading[] = fread($handle, 4);
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = bin2hex(fread($handle, 4));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = bin2hex(fread($handle, 2));
        $heading[] = fread($handle, 4);
        $heading[] = bin2hex(fread($handle, 4));

        // wav bitrate
        $peek = hexdec(substr($heading[10], 0, 2));
        $byte = $peek / 8;

        // checking whether a mono or stereo wav
        $channel = hexdec(substr($heading[6], 0, 2));

        $ratio = ($channel == 2 ? 40 : 80);

        // start putting together the initial canvas
        // $data_size = (size_of_file - header_bytes_read) / skipped_bytes + 1
        $data_size = floor((filesize($wavFile) - 44) / ($ratio + $byte) + 1);
        $data_point = 0;

        $processHeightZoom = 5;

        if(
            $img = imagecreatetruecolor(
                $data_size / $this->params["detail"],
                $processHeightZoom * $this->params["height"]
            )
        ) {
            if($this->params["background"] == "") {
                imagesavealpha($img, true);
                imagealphablending($img, false);
                $transparentColor = imagecolorallocatealpha($img, 0, 0, 0, 127);
                imagefill($img, 0, 0, $transparentColor);
            }
            else {
                list($br, $bg, $bb) = $this->html2rgb($this->params["background"]);
                imagefilledrectangle(
                    $img,
                    0,
                    0,
                    (int) ($data_size / $this->params["detail"]),
                    $processHeightZoom * $this->params["height"],
                    imagecolorallocate($img, $br, $bg, $bb)
                );
            }
        }
        $min_v = ($processHeightZoom * $this->params["height"]) / 2;
        $max_v = ($processHeightZoom * $this->params["height"]) / 2;
        if($img) while(!feof($handle) && $data_point < $data_size) {
            if ($data_point++ % $this->params["detail"] == 0) {
                $bytes = array();
                for ($i = 0; $i < $byte; $i++) $bytes[$i] = fgetc($handle);
                switch($byte) {
                    // get value for 8-bit wav
                    case 1:
                        $data = $this->findValues($bytes[0], $bytes[1]);
                        break;
                    // get value for 16-bit wav
                    case 2:
                        if(ord($bytes[1]) & 128)
                        $temp = 0;
                        else
                        $temp = 128;
                        $temp = chr((ord($bytes[1]) & 127) + $temp);
                        $data = floor($this->findValues($bytes[0], $temp) / 256);
                        break;
                }

                // skip bytes for memory optimization
                fseek($handle, $ratio, SEEK_CUR);
                // draw this data point
                // relative value based on height of image being generated
                // data values can range between 0 and 255
                $v = (int) ($data / 255 * ($processHeightZoom * $this->params["height"]));
                if($v < $min_v) {
                    $min_v = $v;
                }
                if($v > $max_v) {
                    $max_v = $v;
                }
                // don't print flat values on the canvas if not necessary
                if(
                    !(
                            $v / ($processHeightZoom * $this->params["height"]) == 0.5
                        &&  !$this->params["drawFlat"]
                    )
                ) {
                    // draw the line on the image using the $v value and centering it
                    // vertically on the canvas
                    imageline(
                        $img,
                        // x1
                        (int) ($data_point / $this->params["detail"]),
                        // y1: height of the image minus $v as a percentage
                        // of the height for the wave amplitude
                        ($processHeightZoom * $this->params["height"]) - $v,
                        // x2
                        (int) ($data_point / $this->params["detail"]),
                        // y2: same as y1, but from the bottom of the image
                        ($processHeightZoom * $this->params["height"])
                        - (( $processHeightZoom * $this->params["height"]) - $v),
                        imagecolorallocate($img, $r, $g, $b)
                    );
                }
            } else {
                // skip this one due to lack of detail
                fseek($handle, $ratio + $byte, SEEK_CUR);
            }
        }
        fclose($handle);
        $stretch = min(
            array(
                ($processHeightZoom * $this->params["height"]) - $max_v,
                $min_v
            )
        );
        $rimg = imagecreatetruecolor($this->params["width"], $this->params["height"]);
        imagesavealpha($rimg, true);
        imagealphablending($rimg, false);
        imagecopyresampled(
            $rimg,
            $img,
            0,
            0,
            0,
            0 + $stretch,
            $this->params["width"],
            $this->params["height"],
            imagesx($img),
            imagesy($img) - (2 * $stretch)
        );
        imagedestroy($img);
        return $rimg;
    }

    public function findValues($byte1, $byte2) {
        return hexdec(bin2hex($byte1)) + (hexdec(bin2hex($byte2)) * 256);
    }

    public function html2rgb($input) {
        $input = $input[0] == "#" ? substr($input, 1, 6) : substr($input, 0, 6);
        return array(
            hexdec(substr($input, 0, 2)),
            hexdec(substr($input, 2, 2)),
            hexdec(substr($input, 4, 2))
        );
    }

}
