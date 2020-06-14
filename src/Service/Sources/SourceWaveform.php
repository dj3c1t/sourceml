<?php

namespace Sourceml\Service\Sources;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\App\Configuration;
use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\JQFileUpload\Media;

class SourceWaveform {

    protected $container;

    protected $rootDir;
    protected $mediaRootDir;
    protected $waveformDir;

    public function __construct(Container $container) {
        $this->container = $container;
        $upload_manager = $this->container->get('jq_file_upload.upload_manager');
        $this->rootDir =
            dirname($this->container->get('kernel')->getRootDir())
            ."/".$this->container->getParameter('web_dir');
        $this->mediaRootDir = $upload_manager->getMediaRootDir();
        $this->waveformDir = "waveforms";
    }

    public function getAudioFileMimeTypes() {
        return array(
            "audio/wav",
            "audio/x-wav",
            "audio/flac",
            "audio/x-flac",
            "audio/ogg",
            "application/ogg",
            "audio/mp3",
            "audio/mpeg",
        );
    }

    public function isAudioMimeType($mimeType) {
        foreach($this->getAudioFileMimeTypes() as $audioMimeType) {
            if($mimeType == $audioMimeType) {
                return true;
            }
        }
        return false;
    }

    public function getMediasDir() {
        return $this->rootDir."/".$this->mediaRootDir."/";
    }

    public function updateWaveform(Source $source, $forceUpdate = false) {
        $sw = $this->container->get('sourceml.waveform');
        $em = $this->container->get('doctrine')->getManager();
        $mediasDir = $this->getMediasDir();
        if(!$sw->soxCommandExists()) {
            return;
        }
        if($waveform = $source->getWaveform()) {
            if($forceUpdate) {
                $source->setWaveform(null);
                $em->remove($waveform);
                @unlink($mediasDir.$waveform->getName());
            }
            else {
                return;
            }            
        }
        if(!($documents = $source->getDocuments())) {
            return;
        }
        foreach($documents as $document) {
            $localAudioFile = false;
            if($media = $document->getMedia()) {
                $localAudioFile = $mediasDir.$media->getName();
            }
            else {
                $localAudioFile = $this->getLocalFile($document->getUrl());
            }
            if($localAudioFile) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $localAdioFileMimeType = finfo_file($finfo, $localAudioFile);
                finfo_close($finfo);
                if(!$this->isAudioMimeType($localAdioFileMimeType)) {
                    continue;
                }
                $this->makeWaveformsDir();
                $pngFile = $this->waveformDir."/".$source->getId().".png";
                $sw->audioToPng(
                    $localAudioFile,
                    $mediasDir.$pngFile,
                    array(
                        "foreground" => "#555555"
                    )
                );
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $pngFileMimeType = finfo_file($finfo, $mediasDir.$pngFile);
                finfo_close($finfo);
                $media = new Media();
                $media->setName($pngFile);
                $media->setSize(filesize($mediasDir.$pngFile));
                $media->setMimeType($pngFileMimeType ? $pngFileMimeType : "");
                $em->persist($media);
                $source->setWaveform($media);
                return;
            }
        }

    }

    public function makeWaveformsDir() {
        $current_dir = $this->rootDir;
        $path = explode("/", $this->mediaRootDir."/".$this->waveformDir);
        foreach($path as $dir) {
            if(!$dir) {
                continue;
            }
            $current_dir .= "/".$dir;
            if(is_dir($current_dir)) {
                continue;
            }
            @mkdir($current_dir);
            if(!is_dir($current_dir)) {
                throw new \Exception("unable to make ".$this->waveformDir." dir");
            }
        }
    }

    public function getLocalFile($url) {
        $v_url = explode("/", $url);
        if(count($v_url) < 4 || !$v_url[2] || !$v_url[3]) {
            return false;
        }
        $em = $this->container->get('doctrine')->getManager();
        $configurationRepo = $em->getRepository(Configuration::class);
        if($v_url[2] != $configurationRepo->getConfigurationByName("install_domain")) {
            return false;
        }
        $localFile = $configurationRepo->getConfigurationByName("install_host_root_dir");
        for($i = 3; $i < count($v_url); $i++) {
            $localFile .= "/".$v_url[$i];
        }
        if(!file_exists($localFile)) {
            return false;
        }
        return $localFile;
    }

}
