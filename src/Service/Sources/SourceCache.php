<?php

namespace Sourceml\Service\Sources;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\App\Configuration;
use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\DerivationSource;

class SourceCache {

    const SOURCES_DIR     = "sources";
    const DERIVATIONS_DIR = "derivations";

    protected $container;
    protected $rootDir;
    protected $xmlDir;

    public function __construct(Container $container) {
        $this->container = $container;
        $this->rootDir =
            dirname($this->container->get('kernel')->getRootDir())
            ."/".$this->container->getParameter('web_dir');
        $this->xmlDir = 'medias/xml';
    }

    public function getSourceXmlPath(Source $source) {
        return
            $this->rootDir."/".$this->xmlDir."/"
            .SourceCache::SOURCES_DIR."/"
            .$source->getId().".xml";
    }

    public function getDerivationXmlPath(DerivationSource $derivation) {
        return
            $this->rootDir."/".$this->xmlDir."/"
            .SourceCache::DERIVATIONS_DIR."/"
            .$derivation->getId().".xml";
    }

    public function initXmlDir($path = null) {
        $currentDir = $this->rootDir;
        $xmlDir = $this->xmlDir;
        if(isset($path)) {
            $xmlDir .= "/".$path;
        }
        foreach(explode("/", $xmlDir) as $dir) {
            if($dir) {
                $currentDir .= "/".$dir;
                if(!is_dir($currentDir)) {
                    @mkdir($currentDir);
                    if(!is_dir($currentDir)) {
                        throw new \Exception("unable to create source xml dir");
                    }
                }
            }
        }
    }

    public function getXmlFile(Source $source) {
        $cache_file = $this->getSourceXmlPath($source);
        if($this->cacheFileNeedUpdate($cache_file)) {
            $this->updateCacheFile($source);
        }
        return $cache_file;
    }

    public function getDerivationXmlFile(DerivationSource $derivation) {
        $cache_file = $this->getDerivationXmlPath($derivation);
        if($this->cacheFileNeedUpdate($cache_file)) {
            $this->updateDerivationCacheFile($derivation);
        }
        return $cache_file;
    }

    public function getXmlContent(Source $source) {
        $cache_file = $this->getXmlFile($source);
        if(!($content = @file_get_contents($cache_file))) {
            throw new \Exception("unable to read source xml content");
        }
        return $content;
    }

    public function cacheFileNeedUpdate($cache_file) {
        if(!file_exists($cache_file)) {
            return true;
        }
        $em = $this->container->get("doctrine")->getManager();
        $configurationRepo = $em->getRepository(Configuration::class);
        $lifetime_enabled = $configurationRepo->getConfiguration("sourceml.cache.lifetime_enabled");
        $lifetime_enabled = isset($lifetime_enabled) ? ($lifetime_enabled ? true : false) : false;
        if($lifetime_enabled) {
            $lifetime = $configurationRepo->getConfiguration("sourceml.cache.lifetime");
            $lifetime = 60 * 60 * (float) (isset($lifetime) ? $lifetime : "72");
            if(!($filectime = @filectime($cache_file))) {
                return false;
            }
            return time() - $filectime > $lifetime;
        }
        return false;
    }

    public function updateCacheFile(Source $source) {
        $this->initXmlDir(SourceCache::SOURCES_DIR);
        $cache_file = $this->getSourceXmlPath($source);
        if($source->isReference()) {
            if(!($content = @file_get_contents($source->getReferenceUrl()))) {
                throw new \Exception("unable to read xml reference file");
            }
            if(($xml = @simplexml_load_string($content)) === false) {
                throw new \Exception("unable to parse xml reference file");
            }
        }
        else {
            $source_xml_parser = $this->container->get('sourceml.source_xml_parser');
            $content = $source_xml_parser->getXmlFromSource($source);
        }
        if(!@file_put_contents($cache_file, $content)) {
            throw new \Exception("unable to write xml file");
        }
    }

    public function updateDerivationCacheFile(DerivationSource $derivation) {
        $this->initXmlDir(SourceCache::DERIVATIONS_DIR);
        $cache_file = $this->getDerivationXmlPath($derivation);
        if(!($content = @file_get_contents($derivation->getUrl()))) {
            throw new \Exception("unable to read xml derivation file");
        }
        if(($xml = @simplexml_load_string($content)) === false) {
            throw new \Exception("unable to parse xml derivation file");
        }
        if(!@file_put_contents($cache_file, $content)) {
            throw new \Exception("unable to write xml file");
        }
    }

    public function deleteCacheFile(Source $source) {
        $ok = true;
        $cache_file = $this->getSourceXmlPath($source);
        if(file_exists($cache_file)) {
            $ok = $ok & @unlink($cache_file);
        }
        if($derivations = $source->getDerivations()) {
            foreach($derivations as $derivation) {
                $cache_file = $this->getDerivationXmlPath($derivation);
                if(file_exists($cache_file)) {
                    $ok = $ok & @unlink($cache_file);
                }
            }
        }
        @clearstatcache();
        return $ok;
    }

    public function emptyCache() {
       $this->emptyCacheDir($this->rootDir."/".$this->xmlDir."/".SourceCache::SOURCES_DIR);
       $this->emptyCacheDir($this->rootDir."/".$this->xmlDir."/".SourceCache::DERIVATIONS_DIR);
    }

    protected function emptyCacheDir($cacheDir) {
        if(!is_dir($cacheDir)) {
            return true;
        }
        if(!($dh = opendir($cacheDir))) {
            throw new \Exception("can't open cache directory");
        }
        while(($file = readdir($dh)) !== false) {
            if(strtolower(substr($file, -4)) == ".xml") {
                if(!@unlink($cacheDir."/".$file)) {
                    throw new \Exception("can't remove cache file ".$file);
                }
            }
        }
        closedir($dh);
        return true;
    }

}
