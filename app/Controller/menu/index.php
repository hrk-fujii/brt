<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Util\MailUtil;
use Model\Dao\Users;
use Model\Dao\Bento;



// メニュー表示
$app->get('/menu', function (Request $request, Response $response) {
    return showMenuCtrl($response, $this->view, $this->db);
});

$app->post('/menu', function (Request $request, Response $response) {
    $input = $request->getParsedBody();

    // 表示日付変更なら
    if (!empty($input["showSaleDate"])){
        return showMenuCtrl($response, $this->view, $this->db, $input["showSaleDate"]);
    }

});

function showMenuCtrl($response, $view, $db, $saleDate=NULL){
    // 多重予約ロックを解除
    $_SESSION["brt-orderReady"] = TRUE;
    
    if ($saleDate===NULL){ // 何もなければ今日の日付
        $saleDate = strtotime(date("Y-m-d", time()));
    }
    $data = [];
    $bentoTable = new Bento($db);
    $data["bentoArray"] = $bentoTable->selectFromStartSaleAt($saleDate, $saleDate + 60 * 60 * 24 - 1);
    $data["saleDateArray"] = [];
    foreach ($data["bentoArray"] as &$b){
        $b["startSaleStr"] = date("H:i", $b["start_sale_at"]);
        $b["saleLengthMinuteOnly"] = (int)(($b["end_sale_at"]-$b["start_sale_at"])/60);
        $b["orderDeadlineStr"] = date("j日", $b["order_deadline_at"]). "(". DAY_JP[date("w", $b["order_deadline_at"])]. ")". date("H:i", $b["order_deadline_at"]);
    }
    $data["saleDateArray"] = [];
    for ($count = 0; $count <= 10; $count++){
        $val = strtotime(date("Y-m-d", time() + (60*60*24*$count)));
        array_push($data["saleDateArray"], ["unix"=> $val, "str"=> date("n月j日", $val). DAY_JP[date("w", $val)]. "曜日"]);
    }
    return $view->render($response, 'menu/index.twig', $data);
}
