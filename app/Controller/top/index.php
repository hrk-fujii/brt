<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Informations;


// TOPページのコントローラ
$app->get('/', function (Request $request, Response $response) {

	$data=[];

    // Render index view
    return $this->view->render($response, 'top/index.twig', $data);
});

