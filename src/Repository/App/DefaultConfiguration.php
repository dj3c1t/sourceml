<?php

namespace Sourceml\Repository\App;

class DefaultConfiguration {

    public static function getDefaultValues() {
        return array(
            "site_title" => "App",
        );
    }

    public static function getDefaultValue($config_name) {
        $config = DefaultConfiguration::getDefaultValues();
        return (isset($config[$config_name]) ? $config[$config_name] : "");
    }

}
