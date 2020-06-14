<?php

namespace Sourceml\Controller\Sources;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ImportController extends Controller {

    public function previousVersionAction(Request $request) {
        $importPreviousVersion = $this->get('sourceml.import_previous_version');
        if(php_sapi_name() !== 'cli') {
            $importPreviousVersion->log(" ERROR command line only");
            return new Response("");
        }
        try {
            $importPreviousVersion->init();
            $importPreviousVersion->import();
            $importPreviousVersion->log("import done");
        }
        catch(\Exception $e) {
            $importPreviousVersion->log(" ERROR ".$e->getMessage());
        }
        return new Response();
    }

}
