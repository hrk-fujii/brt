<?php

use Slim\Http\Request;
use Slim\Http\Response;


// 管理メニュー表示
$app->get('/manage', function (Request $request, Response $response) {
    return $this->view->render($response, 'manage/index.twig', []);
});
