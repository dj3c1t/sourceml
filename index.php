<?php

    $baseUri = dirname($_SERVER['SCRIPT_NAME']);
    $baseUri .= substr($baseUri, -1) == "/" ? "" : "/";
    header("Location: ".$baseUri."public/");
