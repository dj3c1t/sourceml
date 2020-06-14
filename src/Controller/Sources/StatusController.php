<?php

namespace Sourceml\Controller\Sources;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sourceml\Http\REST\RestResponse;

class StatusController extends Controller {

    public function urlOpenAction(Request $request) {
        return new RestResponse(
            json_encode(
                array(
                    "status" => true,
                )
            )
    	);
    }

}
