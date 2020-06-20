<?php

namespace Sourceml\Service\App\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class RequestListener {

    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event) {
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
        $twigLoader = $this->container->get('twig.loader');
        $pathes = [];
        if($theme = $this->container->getParameter('sourceml_theme')) {
            $themeDir = $this->container->getParameter("twig.default_path")."/themes/".$theme;
            if(is_dir($themeDir)) {
                $pathes[] = $themeDir;
            }
        }
        foreach($twigLoader->getPaths() as $path) {
            $pathes[] = $path;
        }
        $twigLoader->setPaths($pathes);
    }

}
