<?php

use Slim\Http\Request;
use Slim\Http\Response;


// ヘルプページのコントローラ
$app->get("/about", function (Request $request, Response $response) {
    
    $data=["contactEmail" => $_ENV["CONTACT_EMAIL"]];

    // Render index view
    return $this->view->render($response, 'about/index.twig', $data);
});

