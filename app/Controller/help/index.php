<?php

use Slim\Http\Request;
use Slim\Http\Response;


// ヘルプページのコントローラ
$app->get('/help', function (Request $request, Response $response) {
    
    $data=[];

    // Render index view
    return $this->view->render($response, 'help/index.twig', $data);
});

