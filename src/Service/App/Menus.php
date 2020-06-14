<?php

namespace Sourceml\Service\App;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class Menus {

    protected $menusConfigFiles;

    protected $menu;
    protected $menuName;

    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function getMenusConfigFiles() {
        if(!isset($this->menusConfigFiles)) {
            $this->menusConfigFiles = [];
            $menusDir = dirname($this->container->get('kernel')->getRootDir()).'/config/menus';
            if(is_dir($menusDir)) {
                if($dh = opendir($menusDir)) {
                    while(($file = readdir($dh)) !== false) {
                        if(strtolower(substr($file, -5)) === ".yaml") {
                            $this->menusConfigFiles[] = $menusDir."/".$file;
                        }
                    }
                    closedir($dh);
                }
            }

        }
        return $this->menusConfigFiles;
    }

    public function getMenu($route) {
        $this->menu = array();
        $this->menuName = null;
        foreach($this->getMenusConfigFiles() as $bundleName) {
            $this->loadMenuName($bundleName, $route);
            if(isset($this->menuName)) {
                break;
            }
        }
        foreach($this->getMenusConfigFiles() as $bundleName) {
            $this->loadMenus($bundleName);
        }
        return $this->menu;
    }

    public function getMenuByName($menuName) {
        $this->menu = array();
        $this->menuName = $menuName;
        $bundles = $this->container->getParameter('kernel.bundles');
        foreach($bundles as $bundleName => $bundleValues) {
            $this->loadMenus($bundleName);
        }
        return $this->menu;
    }

    public function loadMenuName($bundleName, $route) {
        try {
            $yaml = Yaml::parse(file_get_contents($bundleName));
            if(isset($yaml["sourceml_menus"])) {
                foreach($yaml["sourceml_menus"] as $app_name => $menus) {
                    foreach($menus as $menuName => $menuValues) {
                        if($menuValues["routes"]) {
                            foreach($menuValues["routes"] as $menu_route) {
                                if($route == $menu_route) {
                                    $this->menuName = $menuName;
                                    return;
                                }
                            }
                        }
                    }
                }
            }
        }
        catch(\Exception $e) {
        }
    }

    public function loadMenus($bundleName) {
        if(isset($this->menuName)) {
            try {
                $yaml = Yaml::parse(file_get_contents($bundleName));
                foreach($yaml["sourceml_menus"] as $app_name => $menus) {
                    foreach($menus as $menuName => $menuValues) {
                        if($menuName == $this->menuName) {
                            foreach($menuValues["items"] as $menu_item_infos) {
                                $menu_item = $menu_item_infos;
                                if(isset($menu_item_infos["service"])) {
                                    $menu_item = array();
                                    if(isset($menu_item_infos["method"])) {
                                        $menu_service = $this->container->get($menu_item_infos["service"]);
                                        $menu_method = $menu_item_infos["method"];
                                        $menu_items = $menu_service->$menu_method();
                                        foreach($menu_items as $menu_item) {
                                            if(isset($menu_item["route"])) {
                                                $this->menu[] = $menu_item;
                                            }
                                        }
                                    }
                                }
                                else {
                                    if(isset($menu_item["route"])) {
                                        $this->menu[] = $menu_item;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            catch(\Exception $e) {
            }
        }
        return array();
    }

}
