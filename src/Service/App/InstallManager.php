<?php

namespace Sourceml\Service\App;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

use Sourceml\Entity\App\User;
use Sourceml\Entity\App\Role;

class InstallManager {

    protected $container;

    protected $parameters;

    protected $rootDir;

    protected $webDir;

    public function __construct(Container $container) {
        $this->container = $container;
        $this->rootDir = dirname($this->container->get('kernel')->getRootDir());
        $this->webDir = $this->container->getParameter('web_dir');
        $this->parameters = array(
            "database_host" => "",
            "database_name" => "",
            "database_user" => "",
            "database_pass" => "",
            "site_title" => "",
            "admin_login" => "",
            "admin_email" => "",
            "admin_pass" => "",
        );
    }

    public function isInstallRequest() {
        return preg_match(
            "/^install/",
            $this->container->get('request_stack')->getMasterRequest()->get('_route')
        );
    }

    public function isNotWritableRequest() {
        return preg_match(
            "/^install_notwritable/",
            $this->container->get('request_stack')->getMasterRequest()->get('_route')
        );
    }

    public function checkWriteAccess() {
        $notWritable = array();
        if(!is_writable($this->rootDir."/var/cache")) {
            $notWritable[] = "var/cache";
        }
        if(!is_writable($this->rootDir."/var/log")) {
            $notWritable[] = "var/log";
        }
        if($this->isInstallRequest() && !is_writable($this->rootDir."/.env")) {
            $notWritable[] = ".env";
        }
        if(!is_writable($this->rootDir."/".$this->webDir)) {
            $notWritable[] =  $this->webDir;
        }
        $medias_dir = $this->rootDir."/".$this->webDir."/medias";
        if(is_dir($medias_dir) && !is_writable($medias_dir)) {
            $notWritable[] =  $this->webDir."/medias";
        }
        return $notWritable;
    }

    public function getParameters() {
        return $this->parameters;
    }

    public function setParameters(Request $request) {
        $data = $request->request->all();
        if(
                !isset($data["database_host"])
            ||  !isset($data["database_name"])
            ||  !isset($data["database_user"])
            ||  !isset($data["database_pass"])
            ||  !isset($data["site_title"])
            ||  !isset($data["admin_login"])
            ||  !isset($data["admin_email"])
            ||  !isset($data["admin_pass"])
        ) {
            throw new \Exception("missing parameter");
        }
        $this->parameters = array(
            "database_host" => $data["database_host"],
            "database_name" => $data["database_name"],
            "database_user" => $data["database_user"],
            "database_pass" => $data["database_pass"],
            "site_title" => $data["site_title"],
            "admin_login" => $data["admin_login"],
            "admin_email" => $data["admin_email"],
            "admin_pass" => $data["admin_pass"],
        );
    }

    public function connectToDatabase() {
        try {
            $em = $this->container->get('doctrine')->getManager();
            $connection = $em->getConnection();
            $params = $connection->getParams();
            $params['url'] = $this->getDatabaseUrl();
            $params['host'] = $this->parameters['database_host'];
            $params['dbname'] = $this->parameters['database_name'];
            $params['user'] = $this->parameters['database_user'];
            $params['password'] = $this->parameters['database_pass'];
            if($connection->isConnected()) {
                $connection->close();
            }
            $connection->__construct(
                $params, $connection->getDriver(), $connection->getConfiguration(),
                $connection->getEventManager()
            );
            $connection->connect();
        }
        catch(\Exception $e) {
            throw new \Exception("Impossible de se connecter à la base de données");
        }
    }

    public function installDatabase() {
        $kernel = $this->container->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $resultCode = $application->run(
            new ArrayInput(
                array(
                    'command' => 'doctrine:migration:migrate',
                )
            ),
            new NullOutput()
        );
        if($resultCode != 0) {
            throw new \Exception("Impossible de créer les tables dans la base de données");
        }
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $resultCode = $application->run(
            new ArrayInput(
                array(
                    'command' => 'doctrine:fixtures:load',
                )
            ),
            new NullOutput()
        );
        if($resultCode != 0) {
            throw new \Exception("Impossible de remplir la base de données");
        }
    }

    public function clearAppCache() {
        $kernel = $this->container->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $resultCode = $application->run(
            new ArrayInput(
                array(
                    'command' => 'cache:clear',
                )
            ),
            new NullOutput()
        );
        if($resultCode != 0) {
            throw new \Exception("Impossible de vider le cache");
        }
    }

    public function setSiteTitle() {
        $em = $this->container->get('doctrine')->getManager();
        $configurationRepo = $em->getRepository(\Sourceml\Entity\App\Configuration::class);
        $configurationEntity = $configurationRepo->setConfiguration(
            'site_title',
            $this->parameters['site_title']
        );
        try {
            $em->persist($configurationEntity);
            $em->flush();
        }
        catch(\Exception $e) {
            throw new \Exception("Impossible d'enregistrer le titre du site");
        }
    }

    public function setInstallInfos() {
        $em = $this->container->get('doctrine')->getManager();
        $configurationRepo = $em->getRepository(\Sourceml\Entity\App\Configuration::class);
        if(!($request = $this->container->get('request_stack')->getCurrentRequest())) {
            throw new \Exception("Impossible de trouver le domaine d'installation");
        }
        $domainConfiguration = $configurationRepo->setConfiguration(
            'install_domain',
            $request->getHttpHost()
        );
        $em->persist($domainConfiguration);
        $rootDirConfiguration = $configurationRepo->setConfiguration(
            'install_host_root_dir',
            $_SERVER['DOCUMENT_ROOT']
        );
        $em->persist($rootDirConfiguration);
        try {
            $em->flush();
        }
        catch(\Exception $e) {
            throw new \Exception("Impossible d'enregistrer le domaine d'installation");
        }
    }

    public function createAdminUser() {
        $em = $this->container->get('doctrine')->getManager();
        $roleRepo = $em->getRepository(\Sourceml\Entity\App\Role::class);
        if(!($adminRole = $roleRepo->findOneByName('admin'))) {
            throw new \Exception("Impossible de trouver le role admin");
        }
        $user = new User();
        $user->setIsActive(true);
        $user->setUsername($this->parameters['admin_login']);
        $user->setEmail($this->parameters['admin_email']);
        $user->setPassword($this->parameters['admin_pass']);
        $user->setSalt("");
        $factory = $this->container->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        $user->setPassword($encoder->encodePassword($user->getPassword(), $user->getSalt()));
        $user->addRole($adminRole);
        $em->persist($user);
        $em->flush();
    }

    public function runInstaller() {
        return $this->container->getParameter("sourceml_run_installer");
    }

    public function redirectToNotWritable() {
        return new RedirectResponse(
            $this->container->get('router')->generate('install_notwritable')
        );
    }

    public function redirectToInstall() {
        return new RedirectResponse(
            $this->container->get('router')->generate('install')
        );
    }

    public function saveDatabaseParameters() {
        $this->writeEnvParams([
            "DATABASE_URL" => $this->getDatabaseUrl(),
        ]);
    }

    public function disableInstaller() {
        $this->writeEnvParams([
            "SOURCEML_RUN_INSTALLER" => "false",
        ]);
    }

    public function setAppEnv($env) {
        $this->writeEnvParams([
            "APP_ENV" => $env,
        ]);
    }

    protected function getDatabaseUrl() {
        return "mysql://".$this->parameters['database_name']
                     .":".$this->parameters['database_pass']
                     ."@".$this->parameters['database_host']
                     ."/".$this->parameters['database_name'];
    }

    protected function writeEnvParams($params) {
        $envFile = $this->getEnvFilePath();
        $lines = $this->getEnvFileLines($envFile);
        $newLines = [];
        $paramFound = false;
        foreach($params as $paramName => $paramValue) {
            foreach($lines as $line) {
                if(strpos(trim($line), $paramName) === 0) {
                    $paramFound = true;
                    $newLines[] = $paramName."=".$paramValue;
                }
                else {
                    $newLines[] = $line;
                }
            }
            if(!$paramFound) {
                $newLines[] = $paramName."=".$paramValue;
            }
        }
        $this->writeEnvFileLines($envFile, $newLines);
    }

    protected function getEnvFilePath() {
        $envFile = dirname($this->container->get('kernel')->getRootDir()).'/.env';
        if(!file_exists($envFile)) {
            throw new \Exception("Impossible de trouver le fichier .env");
        }
        return $envFile;
    }

    protected function getEnvFileLines($envFile) {
        $lines = [];
        if($handle = @fopen($envFile, "r")) {
            while(($buffer = fgets($handle, 4096)) !== false) {
                $lines[] = rtrim($buffer, "\r\n");
            }
            if(!feof($handle)) {
                throw new \Exception("Erreur lors de lecture du fichier .env");
            }
            fclose($handle);
        }
        else {
            throw new \Exception("Impossible de lire le fichier .env");
        }
        return $lines;
    }

    protected function writeEnvFileLines($envFile, $lines) {
        if(!($handle = @fopen($envFile, 'w'))) {
            throw new \Exception("Impossible d'écrire dans le fichier .env");
        }
        foreach($lines as $line) {
            if(!fwrite($handle, $line."\n")) {
                throw new \Exception("Erreur lors de l'écriture du fichier .env");
            }
        }
        fclose($handle);
    }

}
