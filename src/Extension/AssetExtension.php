<?php

namespace Sourceml\Extension;

use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for the Symfony Asset component.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class AssetExtension extends AbstractExtension
{
    private $packages;
    protected $container;

    public function __construct(Packages $packages, Container $container)
    {
        $this->packages = $packages;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', [$this, 'getAssetUrl']),
            new TwigFunction('asset_version', [$this, 'getAssetVersion']),
        ];
    }

    /**
     * Returns the public url/path of an asset.
     *
     * If the package used to generate the path is an instance of
     * UrlPackage, you will always get a URL and not a path.
     */
    public function getAssetUrl(string $path, string $packageName = null): string
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
     */
    public function getAssetVersion(string $path, string $packageName = null): string
    {
        return $this->packages->getVersion($path, $packageName);
    }
}
