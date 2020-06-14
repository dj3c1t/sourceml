<?php

namespace Sourceml\Service\Sources;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\App\Configuration;
use Sourceml\Entity\Sources\Author;

class SourceMLMenus {

    private $container;
    private $sourceAuthorRepo;
    private $user;

    public function __construct(Container $container) {
        $this->container = $container;
        $em = $this->container->get('doctrine')->getManager();
        $this->sourceAuthorRepo = $em->getRepository(SourceAuthor::class);
        if($token = $this->container->get('security.token_storage')->getToken()) {
            $this->user = $token->getUser();
        }
    }

    public function newSources() {
        $items = array();
        if($sourceAuthors = $this->sourceAuthorRepo->getNotValidatedSourceAuthors($this->user)) {
            $items[] = array(
                "route" => "account_source_author_new",
                "label" => "Nouvelle sources (".count($sourceAuthors).")",
            );
        }
        return $items;
    }

    public function toggleAutoPlayNextTrackButton() {
        $items = array();
        $items[] = array(
            "route" => "source_toggle_auto_play_next_track",
            "label" => "Lecture automatique",
            "glyphicon" => "glyphicon-ok-circle",
            "class" => "toggle_auto_play_next_track",
        );
        return $items;
    }

    public function getSourceMLMainLinks() {
        $items = array();
        $em = $this->container->get('doctrine')->getManager();
        $configurationRepo = $em->getRepository(Configuration::class);
        $header_menu_authors = $configurationRepo->getConfiguration("sourceml.header_menu.authors");
        $header_menu_authors = isset($header_menu_authors) ? ($header_menu_authors ? true : false) : false;
        $header_menu_albums = $configurationRepo->getConfiguration("sourceml.header_menu.albums");
        $header_menu_albums = isset($header_menu_albums) ? ($header_menu_albums ? true : false) : false;
        $header_menu_tracks = $configurationRepo->getConfiguration("sourceml.header_menu.tracks");
        $header_menu_tracks = isset($header_menu_tracks) ? ($header_menu_tracks ? true : false) : false;
        $header_menu_sources = $configurationRepo->getConfiguration("sourceml.header_menu.sources");
        $header_menu_sources = isset($header_menu_sources) ? ($header_menu_sources ? true : false) : false;
        if($header_menu_authors) {
            $items[] = array(
                "route" => "author_index",
                "label" => "Auteurs",
            );
        }
        if($header_menu_albums) {
            $items[] = array(
                "route" => "source_index",
                "parameters" => array("sourceType" => 'album'),
                "label" => "Albums",
            );
        }
        if($header_menu_tracks) {
            $items[] = array(
                "route" => "source_index",
                "parameters" => array("sourceType" => 'track'),
                "label" => "Morceaux",
            );
        }
        if($header_menu_sources) {
            $items[] = array(
                "route" => "source_index",
                "parameters" => array("sourceType" => 'source'),
                "label" => "Pistes",
            );
        }
        return $items;
    }

}
