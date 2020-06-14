<?php

namespace Sourceml\Controller\JQFileUpload;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ServerController extends Controller {

    public function handleAction(Request $request, $handlerName, $id = null) {
        $upload_manager = $this->get('jq_file_upload.upload_manager');
        try {
            $upload_manager->init($handlerName, $id);
            $upload_manager->handle($request);
        }
        catch(\Exception $e) {
            $upload_manager->error($e->getMessage());
        }
        exit;
    }

}
