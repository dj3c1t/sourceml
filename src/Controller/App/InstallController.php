<?php

namespace Sourceml\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallController extends Controller {

    public function requirementsAction(Request $request) {
        require dirname($this->container->get('kernel')->getRootDir())."/app/SymfonyRequirements.php";
        $symfonyRequirements = new \SymfonyRequirements();
        return $this->render(
            'App/Install/requirements.html.twig',
            array(
                "symfonyRequirements" => $symfonyRequirements,
            )
        );
    }

    public function notwritableAction(Request $request) {
        $im = $this->container->get('sourceml_app.install_manager');
        return $this->render(
            'App/Install/notwritable.html.twig',
            array(
                "notWritable" => $im->checkWriteAccess(),
            )
        );
    }

    public function indexAction(Request $request) {
        $im = $this->container->get('sourceml_app.install_manager');
        if(!$im->runInstaller()) {
            return $this->redirect($this->generateUrl('homepage'));
        }
        $parameters = $im->getParameters();
        if($request->getMethod() == 'POST') {
            try {
                $im->setParameters($request);
                $im->connectToDatabase();
                $im->saveDatabaseParameters();
                $im->installDatabase();
                $im->setSiteTitle();
                $im->setInstallInfos();
                $im->createAdminUser();
                $im->disableInstaller();
                $im->setAppEnv("prod");
                $im->clearAppCache();
                return $this->redirect($this->generateUrl('install_success'));
            }
            catch(\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }
        }
        return $this->render(
            'App/Install/index.html.twig',
            array(
                "parameters" => $im->getParameters(),
            )
        );
    }

    public function successAction() {
        $this->get('session')->getFlashBag()->add('success', "Le site a été installé");
        return $this->render('App/Install/success.html.twig');
    }

}
