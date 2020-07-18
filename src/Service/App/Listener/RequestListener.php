<?php

namespace Sourceml\Service\App\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Twig\Loader\FilesystemLoader;

class RequestListener {

    private $container;
    private $twigLoader;

    public function __construct(Container $container, FilesystemLoader $twigLoader) {
        $this->container = $container;
        $this->twigLoader = $twigLoader;
    }

    public function onKernelRequest(RequestEvent $event) {
        $im = $this->container->get('sourceml_app.install_manager');
        if(!$im->isNotWritableRequest() && $im->checkWriteAccess()) {
            $event->setResponse(
                $im->redirectToNotWritable()
            );
            return;
        }
        if($im->runInstaller() && !$im->isInstallRequest()) {
            $event->setResponse(
                $im->redirectToInstall()
            );
        }
        $this->initSourceml();
    }

    protected function initSourceml() {
        $this->initThemeTwigPath();
    }

    protected function initThemeTwigPath() {
        $pathes = [];
        if($theme = $this->container->getParameter('sourceml_theme')) {
            $themeDir = $this->container->getParameter("twig.default_path")."/themes/".$theme;
            if(is_dir($themeDir)) {
                $pathes[] = $themeDir;
            }
        }
        foreach($this->twigLoader->getPaths() as $path) {
            $pathes[] = $path;
        }
        $this->twigLoader->setPaths($pathes);
    }

}
