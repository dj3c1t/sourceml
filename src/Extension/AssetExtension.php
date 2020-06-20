<?php

namespace Sourceml\Extension;

use Symfony\Component\Asset\Packages;
use Symfony\Bridge\Twig\Extension\AssetExtension as SymfonyAssetExtension;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class AssetExtension extends SymfonyAssetExtension
{

    private $packages;
    protected $container;

    public function __construct(Packages $packages, Container $container)
    {
        $this->packages = $packages;
        $this->container = $container;
    }

    /**
     * Returns the public url/path of an asset.
     *
     * If the package used to generate the path is an instance of
     * UrlPackage, you will always get a URL and not a path.
     *
     * @param string $path        A public path
     * @param string $packageName The name of the asset package to use
     *
     * @return string The public path of the asset
     */
    public function getAssetUrl($path, $packageName = null)
    {
        if($theme = $this->container->getParameter('sourceml_theme')) {
            $themePath = "themes/".$theme."/".$path;
            if(
                file_exists(
                    $this->container->get('kernel')->getProjectDir()
                    ."/".$this->container->getParameter('web_dir')
                    ."/".$themePath
                )
            ) {
                $path = $themePath;
            }
        }
        return $this->packages->getUrl($path, $packageName);
    }

    /**
     * Returns the version of an asset.
     *
     * @param string $path        A public path
     * @param string $packageName The name of the asset package to use
     *
     * @return string The asset version
     */
    public function getAssetVersion($path, $packageName = null)
    {
        return $this->packages->getVersion($path, $packageName);
    }

}
