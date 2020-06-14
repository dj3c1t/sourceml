<?php

namespace Sourceml\Service\Sources;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Filesystem\Filesystem;

use Sourceml\Entity\App\User;
use Sourceml\Entity\App\Role;
use Sourceml\Entity\App\Configuration;
use Sourceml\Entity\Sources\Licence;
use Sourceml\Entity\Sources\AuthorRole;
use Sourceml\Entity\Sources\SourceType;
use Sourceml\Entity\Sources\Author;
use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\SourceAuthor;
use Sourceml\Entity\Sources\SourceDocument;
use Sourceml\Entity\Sources\DerivationSource;

class ImportPreviousVersion {

    protected $container;
    protected $mw_app;
    protected $mw_env;
    protected $mw_data;
    protected $mw_sgbd;
    protected $logs;
    protected $webDir;
    protected $webUri;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    // ----------------------------------------------------------------------
    //                                                                   init
    //

    public function init() {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if(!($this->mw_app = $request->attributes->get("mw_app"))) {
            throw new \Exception("can't find mw_app in request attributes");
        }
        $this->mw_env = $this->mw_app->env();
        $this->mw_data = $this->mw_env->data();
        $this->mw_sgbd = $this->mw_data->sgbd();
        $this->webDir =
            dirname($this->container->get('kernel')->getRootDir())
            ."/".$this->container->getParameter("web_dir");
        $this->initWebUri();
    }

    // ----------------------------------------------------------------------
    //                                                           init web uri
    //

    public function initWebUri() {
        $em = $this->container->get('doctrine')->getManager();
        $configurationRepo = $em->getRepository(Configuration::class);
        $kernel = $this->container->get('kernel');
        $systemWebDir = dirname($kernel->getRootDir())."/".$kernel->getWebDir();
        $hostRootDir = $configurationRepo->getConfigurationByName("install_host_root_dir");
        if(substr($systemWebDir, 0, strlen($hostRootDir)) != $hostRootDir) {
            throw new \Exception("invalid host dir configuration");
        }
        $this->webUri = substr($systemWebDir, strlen($hostRootDir));
    }

    // ----------------------------------------------------------------------
    //                                                                 import
    //

    public function import() {
        $this->log("start import");
        $this->log(
            "source installation: "
            .dirname($this->mw_env->path("mw_dir"))
        );
        $this->log(
            "destination installation: "
            .dirname($this->container->get('kernel')->getRootDir())
        );
        $this->cleanTables();
        $this->cleanMediasDir();
        $this->importUsers();
        $this->importLicences();
        $this->importAuthors();
        $this->importSources();
        $this->importSourcesCompositions();
        $this->importSourcesOrders();
        $this->importDerivations();
    }

    // ----------------------------------------------------------------------
    //                                                           clean tables
    //

    public function cleanTables() {
        $this->log("clean new installation tables");
        $em = $this->container->get('doctrine')->getManager();
        $configurationRepo = $em->getRepository(Configuration::class);
        $configurations = $configurationRepo->getConfiguration();
        $application = new Application($this->container->get('kernel'));
        $application->setAutoExit(false);
        $resultCode = $application->run(
            new ArrayInput(
                array(
                    'command' => 'doctrine:fixtures:load',
                    '--no-interaction' => true,
                )
            ),
            new NullOutput()
        );
        if($resultCode != 0) {
            throw new \Exception("Impossible de remplir la base de données");
        }
        foreach($configurations as $configurationKey => $configurationValue) {
            $configuration = $configurationRepo->setConfiguration(
                $configurationKey,
                $configurationValue
            );
            $em->persist($configuration);
        }
        $em->flush();
    }

    // ----------------------------------------------------------------------
    //                                                       clean medias dir
    //

    public function cleanMediasDir() {
        $this->log("clean new installation medias dir");
        $fs = new Filesystem();
        $fs->remove($this->webDir."/medias");
    }

    // ----------------------------------------------------------------------
    //                                                                  users
    //

    public function importUsers() {
        $this->log("import users");
        $em = $this->container->get('doctrine')->getManager();
        $userRepo = $em->getRepository(User::class);
        $roleRepo = $em->getRepository(Role::class);
        $adminRole = $roleRepo->findOneByRole("ROLE_ADMIN");
        $userRole = $roleRepo->findOneByRole("ROLE_USER");
        $encoder_factory = $this->container->get('security.encoder_factory');
        if(($users = $this->mw_data->users()) === false) {
            $this->error("can't load users from mw_data");
        }
        $mw_users = array();
        $mw_emails = array();
        foreach($users['list'] as $mw_user) {
            $this->log("creating new user for ".$mw_user["login"]);
            if(isset($mw_users[$mw_user["login"]]) || $userRepo->findOneByUsername($mw_user["login"])) {
                $this->log("user ".$mw_user["login"]." already exists, skiping this one");
                continue;
            }
            $mw_users[$mw_user["login"]] = true;
            if(isset($mw_emails[$mw_user["email"]]) || $userRepo->findOneByEmail($mw_user["email"])) {
                $this->log("user ".$mw_user["login"]." has an email (".$mw_user["email"].") already used by another user");
                $this->log("please set a different email per user before importing");
                $this->error("can't import users");
            }
            $mw_emails[$mw_user["email"]] = true;
            $user = new User();
            $this->ignoreIdGenerator($user);
            $user->setId($mw_user["id"]);
            $user->setIsActive(true);
            $user->setUsername($mw_user["login"]);
            $user->setSalt("");
            $newPassword = substr(md5(rand()), 0, 8);
            $user->setPassword(
                $encoder_factory->getEncoder($user)->encodePassword(
                    $newPassword,
                    $user->getSalt()
                )
            );
            $user->setEmail($mw_user["email"]);
            foreach($mw_user["roles"] as $mw_role_id) {
                switch($mw_role_id) {
                    case 1:
                        if(!$user->hasRole($userRole)) {
                            $user->addRole($userRole);
                        }
                        break;
                    case 2:
                    case 3:
                        if(!$user->hasRole($adminRole)) {
                            $user->addRole($adminRole);
                        }
                        break;
                }
            }
            $em->persist($user);
            $this->notifyNewPassword($user, $newPassword);
        }
        $this->log("persisting users to database");
        $em->flush();
    }

    // ----------------------------------------------------------------------
    //                                                               licences
    //

    public function importLicences() {
        $this->log("import licences");
        $em = $this->container->get('doctrine')->getManager();
        if(($licences = $this->mw_data->licences()) === false) {
            $this->error("can't load licences from mw_data");
        }
        foreach($licences["list"] as $mw_licence) {
            $this->log("creating new licence ".$mw_licence["nom"]);
            $licence = new Licence();
            $this->ignoreIdGenerator($licence);
            $licence->setId($mw_licence["id"]);
            $licence->setName($mw_licence["nom"]);
            $licence->setUrl($mw_licence["url"]);
            $em->persist($licence);
        }
        $this->log("persisting licences to database");
        $em->flush();
    }

    // ----------------------------------------------------------------------
    //                                                                authors
    //

    public function importAuthors() {
        $this->log("import authors");
        $em = $this->container->get('doctrine')->getManager();
        $userRepo = $em->getRepository(User::class);
        if(($groupes = $this->mw_data->groupes()) === false) {
            $this->error("can't load authors from mw_data");
        }
        $upload_manager = $this->container->get('jq_file_upload.upload_manager');
        foreach($groupes["list"] as $mw_groupe) {
            $this->log("creating new author ".$mw_groupe["nom"]);
            if(!($user = $userRepo->find($mw_groupe["id_user"]))) {
                $this->log("can't load user ".$mw_groupe["id_user"]);
                $this->log("WARNING skiping author : ".$mw_groupe["nom"]);
                continue;
            }
            $this->loginAs($user);
            $author = new Author();
            $this->ignoreIdGenerator($author);
            $author->setId($mw_groupe["id"]);
            $author->setName($mw_groupe["nom"]);
            $author->setUser($user);
            $author->setDescription($mw_groupe["description"]);
            $author->setEmail($mw_groupe["email"]);
            $author->setHasContactForm($mw_groupe["contact_form"] == 1);
            $author->setUseCaptcha($mw_groupe["captcha"] == 1);
            $em->persist($author);
            $em->flush();
            if($mw_groupe["image"]) {
                $upload_manager->init("sourceml_author_logo", $author->getId());
                $media = $upload_manager->importMediaFromLocalFile(
                    $this->webDir."/".$this->mw_env->path("content")
                    ."/uploads/".$mw_groupe["image"]
                );
                if(isset($media)) {
                    if($error = $media->getError()) {
                        $this->log("can't make media from author logo");
                        $this->log($error);
                        $this->log("WARNING skiping author logo for : ".$mw_groupe["nom"]);
                    }
                    else {
                        $em->persist($media);
                        $author->setImage($media);
                        $em->flush();
                    }
                }
                else {
                    $this->log("can't make media from author logo");
                    $this->log("WARNING skiping author logo for : ".$mw_groupe["nom"]);
                }
            }

        }
    }

    // ----------------------------------------------------------------------
    //                                                                sources
    //

    public function importSources() {
        $this->log("import sources");
        $em = $this->container->get('doctrine')->getManager();
        $sm = $this->container->get('sourceml.source_manager');
        $sw = $this->container->get('sourceml.source_waveform');
        $authorRepo = $em->getRepository(Author::class);
        $licenceRepo = $em->getRepository(Licence::class);
        $authorRoleRepo = $em->getRepository(AuthorRole::class);
        $sourceTypeRepo = $em->getRepository(SourceType::class);
        if(($sources = $this->mw_data->sources(array())) === false) {
            $this->error("can't load sources from mw_data");
        }
        $upload_manager = $this->container->get('jq_file_upload.upload_manager');
        foreach($sources["list"] as $mw_source) {
            $this->log("import source: ".$mw_source["titre"]);
            $adminAthorInfos = $this->mw_data->get_admin_groupe(
                $this->mw_data->source_groupes($mw_source["id"])
            );
            if(!($author = $authorRepo->find($adminAthorInfos["id"]))) {
                $this->log("can't find author ".$adminAthorInfos["nom"]." (id ".$adminAthorInfos["id"].")");
                $this->log("WARNING skiping source : ".$mw_source["titre"]);
                continue;
            }
            if(!($sourceType = $sourceTypeRepo->find($mw_source["id_class"]))) {
                $this->log("can't find sourceType ".$mw_source["id_class"]);
                $this->log("WARNING skiping source : ".$mw_source["titre"]);
                continue;
            }
            $this->loginAs($author->getUser());
            $sm->refreshUser();
            $source = new Source();
            $this->ignoreIdGenerator($source);
            $source->setId($mw_source["id"]);
            $source->setTitle($mw_source["titre"]);
            if(isset($mw_source["licence"]["id"]) && $mw_source["licence"]["id"]) {
                if(!($licence = $licenceRepo->find($mw_source["licence"]["id"]))) {
                    $this->log("can't find licence ".$mw_source["licence"]["id"]);
                    $this->log("WARNING skiping source: ".$mw_source["titre"]);
                    continue;
                }
                $source->setLicence($licence);
            }
            if(isset($mw_source["date_creation"]) && $mw_source["date_creation"]) {
                try {
                    $creationDate = new \DateTime($mw_source["date_creation"]);
                    $source->setCreationDate($creationDate);
                }
                catch(\Exception $e) {
                    $this->log("WARNING creation date invalid for: ".$mw_source["titre"]);
                }
            }
            if(isset($mw_source["reference"]["xml"]["url"]) && $mw_source["reference"]["xml"]["url"]) {
                $source->setReferenceUrl($mw_source["reference"]["xml"]["url"]);
            }
            if(isset($mw_source["description"]) && $mw_source["description"]) {
                $source->setInfo('description', $mw_source["description"]);
            }
            if(isset($mw_source["ordre"])) {
                if(
                    $mw_composition = $this->mw_data->source_compositions(
                        array(
                            "id_source" => $mw_source["id"]
                        )
                    )
                ) {
                    $compositions[$v_rst["id_source"]][] = $v_rst["id_composition"];
                    $source->setInfo('ordre', $mw_source["ordre"]);
                }
            }
            $source->setSourceType($sourceType);
            $em->persist($source);
            $em->flush();
            if(isset($mw_source["date_inscription"]) && $mw_source["date_inscription"]) {
                try {
                    $publicationDate = new \DateTime($mw_source["date_inscription"]);
                    $source->setPublicationDate($publicationDate);
                    $em->flush();
                }
                catch(\Exception $e) {
                    $this->log("WARNING publication date invalid for: ".$mw_source["titre"]);
                }
            }
            foreach($mw_source["groupes"] as $mw_groupe) {
                if(!($author = $authorRepo->find($mw_groupe["id"]))) {
                    $this->log("can't find author ".$mw_groupe["id"]);
                    $this->log("WARNING skiping this author for source : ".$mw_source["titre"]);
                    continue;
                }
                if(!($authorRole = $authorRoleRepo->find($mw_groupe["id_groupe_status"]))) {
                    $this->log("can't find authorRole ".$mw_groupe["id_groupe_status"]);
                    $this->log("WARNING skiping this author for source : ".$mw_source["titre"]);
                    continue;
                }
                $sourceAuthor = new SourceAuthor();
                $sourceAuthor->setSource($source);
                $sourceAuthor->setAuthor($author);
                $sourceAuthor->setAuthorRole($authorRole);
                $sourceAuthor->setIsValid(true);
                $em->persist($sourceAuthor);
            }
            $em->flush();
            if($mw_source["image"]) {
                $upload_manager->init("sourceml_source_image", $source->getId());
                $media = $upload_manager->importMediaFromLocalFile(
                    $this->webDir."/".$this->mw_env->path("content")
                    ."/uploads/".$mw_source["image"]
                );
                if(isset($media)) {
                    if($error = $media->getError()) {
                        $this->log("can't make media from author logo");
                        $this->log($error);
                        $this->log("WARNING skiping source logo for : ".$mw_source["titre"]);
                    }
                    else {
                        $em->persist($media);
                        $source->setImage($media);
                        $em->flush();
                    }
                }
                else {
                    $this->log("can't make media from source logo");
                    $this->log("WARNING skiping source logo for : ".$mw_source["titre"]);
                }
            }
            if(($mw_documents = $this->mw_data->source_documents($mw_source["id"])) === false) {
                $this->log("can't load source documents for source: ".$mw_source["id"]);
                $this->log("WARNING skiping source documents for source: ".$mw_source["titre"]);
            }
            else {
                foreach($mw_documents as $mw_document) {
                    $sourceDocument = new SourceDocument();
                    $sourceDocument->setSource($source);
                    $sourceDocument->setName($mw_document["nom"]);
                    $sourceDocument->setUrl($mw_document["url"]);
                    $em->persist($sourceDocument);
                    $source->addDocument($sourceDocument);
                    try {
                        $sw->updateWaveform($source);
                    }
                    catch(\Exception $e) {
                    }
                }
                if($mw_documents) {
                    $em->flush();
                }
            }
        }
    }

    // ----------------------------------------------------------------------
    //                                                           compositions
    //

    public function importSourcesCompositions() {
        $this->log("import compositions");
        $em = $this->container->get('doctrine')->getManager();
        $sm = $this->container->get('sourceml.source_manager');
        $sourceRepo = $em->getRepository(Source::class);
        if(($sources = $this->mw_data->sources(array())) === false) {
            $this->error("can't load sources from mw_data");
        }
        $hasCompositionsToPersist = false;
        foreach($sources["list"] as $mw_source) {
            $mw_compositions = $this->mw_data->source_compositions(
                array(
                    "id_composition" => $mw_source["id"]
                )
            );
            if(isset($mw_compositions[$mw_source["id"]])) {
                foreach($mw_compositions[$mw_source["id"]] as $id_source) {
                    if(!($composition = $sourceRepo->find($mw_source["id"]))) {
                        $this->log("can't find source composition: ".$mw_source["id"]);
                        $this->log("WARNING skiping source composition for : ".$mw_source["titre"]);
                        continue;
                    }
                    if(!($source = $sourceRepo->find($id_source))) {
                        $this->log("can't find composition source: ".$id_source);
                        $this->log("WARNING skiping source composition for : ".$mw_source["titre"]);
                        continue;
                    }
                    $sm->setComposition($source, $composition);
                    $hasCompositionsToPersist = true;
                }
            }
        }
        if($hasCompositionsToPersist) {
            $em->flush();
        }
    }

    // ----------------------------------------------------------------------
    //                                                                  order
    //

    public function importSourcesOrders() {
        $this->log("import orders");
        $em = $this->container->get('doctrine')->getManager();
        $sourceRepo = $em->getRepository(Source::class);
        if(($sources = $this->mw_data->sources(array())) === false) {
            $this->error("can't load sources from mw_data");
        }
        foreach($sources["list"] as $mw_source) {
            if(isset($mw_source["ordre"])) {
                if(
                    $mw_composition = $this->mw_data->source_compositions(
                        array(
                            "id_source" => $mw_source["id"]
                        )
                    )
                ) {
                    if(!($source = $sourceRepo->find($mw_source["id"]))) {
                        $this->log("can't find source: ".$id_source);
                        $this->log("WARNING skiping source order for : ".$mw_source["titre"]);
                        continue;
                    }
                    foreach($source->getCompositions() as $sourceComposition) {
                        $sourceComposition->setPosition($mw_source["ordre"]);
                    }
                }
            }
        }
        $em->flush();
    }

    // ----------------------------------------------------------------------
    //                                                            derivations
    //

    public function importDerivations() {
        $this->log("import derivations");
        $em = $this->container->get('doctrine')->getManager();
        $sourceRepo = $em->getRepository(Source::class);
        if(($derivations = $this->mw_data->list_sml_source_derivations()) === false) {
            $this->error("can't load derivations from mw_data");
        }
        foreach($derivations["list"] as $mw_derivation) {
            if(!($source = $sourceRepo->find($mw_derivation["id_source"]))) {
                $this->log("can't find source: ".$mw_derivation["id_source"]);
                $this->log("WARNING skiping source derivation for source id: ".$mw_derivation["id_source"]);
                continue;
            }
            $derivationSource = new DerivationSource();
            $derivationSource->setSource($source);
            $xmlUrl = $mw_derivation["derivation"];
            if($source_id = $this->isLocalSourceXML($xmlUrl)) {
                $xmlUrl = $this->getXmlUrl($source_id);
            }
            $derivationSource->setUrl($xmlUrl);
            $em->persist($derivationSource);
            $source->addDerivation($derivationSource);
        }
        $em->flush();
    }

    // ----------------------------------------------------------------------
    //                                                                  utils
    //

    public function log($content) {
        $entry = $this->formatLog($content);
        $this->logs[] = $entry;
        if(php_sapi_name() == 'cli') {
            $this->printLog($content);
        }
    }

    public function printLog($content) {
        echo $this->formatLog($content)."\n";
    }

    public function error($content) {
        throw new \Exception($this->formatLog(" ERROR ".$content));
    }

    public function formatLog($content) {
        return "[".date("Y-m-d H:i:s")."] ".$content;
    }

    public function getLogs() {
        return $this->logs;
    }

    public function notifyNewPassword(User $user, $password) {
        if($webmasterMailAddress = $this->mw_env->path("webmaster_mail_address")) {
            $webmasterMailAddress = substr($webmasterMailAddress, 0, -1);
            $this->log("send new password to user ".$user->getUsername()." (".$user->getEmail().")");
            mail(
                $user->getEmail(),
                '[SourceML update] new password',
                $this->container->get('twig')->render(
                    'Sources/Email/newPassword.txt.twig',
                    array(
                        "user" => $user,
                        "password" => $password,
                        "baseUrl" => $this->mw_env->path("base_url"),
                    )
                ),
                 "From: ".$webmasterMailAddress."\r\n"
                ."Reply-To: ".$webmasterMailAddress."\r\n"
                ."Return-Path: ".$webmasterMailAddress."\r\n"
                ."Content-Type: text/plain; charset=UTF-8\r\n"
            );
        }
    }

    public function ignoreIdGenerator($entity) {
        $em = $this->container->get('doctrine')->getManager();
        $metadata = $em->getClassMetaData(get_class($entity));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
        $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
    }

    public function loginAs(User $user) {
        $token = new UsernamePasswordToken(
            $user,
            $user->getPassword(),
            "secured_area",
            $user->getRoles()
        );
        $this->container->get("security.token_storage")->setToken($token);
        $this->container->get("event_dispatcher")->dispatch(
            "security.interactive_login",
            new InteractiveLoginEvent(
                $this->container->get('request_stack')->getCurrentRequest(),
                $token
            )
        );
    }

    public function isLocalUrl($url) {
        $v_url = explode("/", $url);
        if(count($v_url) >= 3 && $v_url[2]) {
            $em = $this->container->get('doctrine')->getManager();
            $configurationRepo = $em->getRepository(Configuration::class);
            return $v_url[2] == $configurationRepo->getConfigurationByName("install_domain");
        }
        return false;
    }

    public function isLocalSourceXML($url) {
        if($this->isLocalUrl($url)) {
            $v_url = explode("/", $url);
            if(count($v_url) >= 4 && $v_url[3]) {
                $baseUrl = $v_url[0].$v_url[1].$v_url[2];
                $xmlBaseUri = dirname(substr($url, strlen($baseUrl) + 3));
                if(
                        $xmlBaseUri == $this->webUri.($this->webUri ? "/" : "")."content/sources"
                    &&  preg_match("/^([0-9]+)\.xml$/", basename($url), $matches)
                ) {
                    return $matches[1];
                }
            }
        }
        return false;
    }

    public function getXmlUrl($source_id) {
        $em = $this->container->get('doctrine')->getManager();
        $configurationRepo = $em->getRepository(Configuration::class);
        $base_url = "http://".$configurationRepo->getConfigurationByName("install_domain");
        return $base_url."/app.php".$this->container->get('router')->generate(
            'source_xml',
            array(
                "source" => $source_id
            )
        );
    }

}
