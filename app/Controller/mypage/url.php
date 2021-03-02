<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Users;
use Util\MemberUtil;



// URL管理表示
$app->get('/mypage/url', function (Request $request, Response $response) {
    // 予約メニュー管理
    return showMyUrlManageCtrl($request, $response, $this->view, $this->db);
});

// 月指定メニュー管理表示
$app->post('/mypage/url', function (Request $request, Response $response) {
    $input = $request->getParsedBody();
    return showMyUrlManageCtrl($request, $response, $this->view, $this->db, $input);
});


function showMyUrlManageCtrl($request, $response, $view, $db, $input=NULL){
    // ユーザー情報
    $userTable = new Users($db);
    $userData = $userTable->selectFromId($_SESSION["brt-userId"]);

    $message = "";
    $param = $userData["url_param"];
    if (!empty($input["changeUrlParam"])){
        $param = MemberUtil::makeRandomId();
        $userTable->updateUrlParamFromId($_SESSION["brt-userId"], $param);
        $message = "ログイン用URLを変更しました。";
    }

    $data = ["message"=> $message, "url"=> $request->getUri()->getBaseUrl()."/?id=".$param];

    return $view->render($response, 'mypage/url.twig', $data);
    
}
