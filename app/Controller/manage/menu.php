<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Util\MailUtil;
use Model\Dao\Users;
use Model\Dao\Bento;



// ユーザー登録
$app->get('/manage/menu', function (Request $request, Response $response) {
    $_SESSION["brt-confirmEditMenu"] = [];
    return menuManageCtrl($response, $this->view, $this->db);
});

$app->get('/manage/menu/new', function (Request $request, Response $response) {
    $_SESSION["brt-confirmEditMenu"] = [];
    return editNewMenuCtrl($response, $this->view);
});


$app->post('/manage/menu', function (Request $request, Response $response) {
    $input = $request->getParsedBody();

    // 削除なら
    if (!empty($input["deleteTarget"])){
        return deleteMenuCtrl($response, $this->view, $this->db, $input["deleteTarget"]);
    
    // 表示日付変更なら
    } elseif (!empty($input["showSaleDate"])){
        return menuManageCtrl($response, $this->view, $this->db, $input["showSaleDate"]);
    }

});

$app->post('/manage/menu/new', function (Request $request, Response $response) {
    $input = $request->getParsedBody();

    // 編集直後なら
    if (!empty($input["goToConfirm"])){
        // バリデーションとセッション変数セット
        $message = setSessionFromEditMenu($input);
        if (empty($message)){
            return confirmEditMenuCtrl($response, $this->view);
        } else{
            return editNewMenuCtrl($response, $this->view, $input, $message);

        }
    
    // 編集やり直し動作
    } elseif (!empty($input["reEdit"])){
        return editNewMenuCtrl($response, $this->view, $_SESSION["brt-confirmEditMenu"]);
    
    // 確定動作
    } elseif (!empty($input["editSubmit"])){
        return writeMenuCtrl($response, $this->view, $this->db);
    }
});

// 新規メニュー編集フォーム
function editNewMenuCtrl($response, $view, $data=[], $message=""){
    $data["startSaleDateArray"] = [];
    $data["message"] = $message;
    if (!isset($data["startSaleHour"])){
        $data["startSaleHour"] = DEFAULT_START_SALE_HOUR;
    }
    if (!isset($data["startSaleMinute"])){
        $data["startSaleMinute"] = DEFAULT_START_SALE_MINUTE;
    }
    if (!isset($data["saleLengthHour"])){
        $data["saleLengthHour"] = DEFAULT_SALE_LENGTH_HOUR;
    }
    if (!isset($data["saleLengthMinute"])){
        $data["saleLengthMinute"] = DEFAULT_SALE_LENGTH_MINUTE;
    }
    if (!isset($data["orderDeadlineDate"])){
        $data["orderDeadlineDate"] = DEFAULT_ORDER_DEADLINE_DATE_BEFORE;
    }
    if (!isset($data["orderDeadlineHour"])){
        $data["orderDeadlineHour"] = DEFAULT_ORDER_DEADLINE_HOUR;
    }
    if (!isset($data["orderDeadlineMinute"])){
        $data["orderDeadlineMinute"] = DEFAULT_ORDER_DEADLINE_MINUTE;
    }
    for ($count = 0; $count <= 10; $count++){
        $val = strtotime(date("Y-m-d", time() + (60*60*24*$count)));
        $data["startSaleDateArray"][$val] = date("n月j日", $val). DAY_JP[date("w", $val)]. "曜日";
    }
    if ($menuId===NULL){
        return $view->render($response, 'manage/newMenu.twig', $data);
    } else{
        return $view->render($response, 'manage/editMenu.twig', $data);
    }
}

// メニュー確認フォーム
function confirmEditMenuCtrl($response, $view){
    $data = $_SESSION["brt-confirmEditMenu"];

    return $view->render($response, 'manage/confirmEditMenu.twig', $data);
}

// メニュー書き込み
function writeMenuCtrl($response, $view, $db){
    $bentoTable = new Bento($db);
    $data = $_SESSION["brt-confirmEditMenu"];
    foreach ($data["name"] as $k=> $v){
        if (!empty($data["name"][$k])){
            $ret = $bentoTable->insertItem($data["name"][$k], $data["discription"][$k], $data["orderDeadlineAt"], $data["startSaleAt"], $data["endSaleAt"], NULL);
        }
    }
    
    // 一時データを削除して成功ビューへ
    $_SESSION["brt-confirmEditMenu"] = [];
    return $view->render($response, 'manage/newMenuOk.twig', $data);
}

function menuManageCtrl($response, $view, $db, $saleDate=NULL){
    if ($saleDate===NULL){ // 何もなければ今日の日付
        $saleDate = strtotime(date("Y-m-d", time()));
    }
    $data = [];
    $bentoTable = new Bento($db);
    $data["bentoArray"] = $bentoTable->selectFromStartSaleAt($saleDate, $saleDate + 60 * 60 * 24 - 1);
    $data["saleDateArray"] = [];
    foreach ($data["bentoArray"] as &$b){
        $b["startSaleStr"] = date("n/j H:i", $b["start_sale_at"]);
        $b["saleLengthMinuteOnly"] = (int)(($b["end_sale_at"]-$b["start_sale_at"])/60);
        $b["orderDeadlineStr"] = date("n/j H:i", $b["order_deadline_at"]);
    }
    $data["saleDateArray"] = [];
    for ($count = 0; $count <= 10; $count++){
        $val = strtotime(date("Y-m-d", time() + (60*60*24*$count)));
        array_push($data["saleDateArray"], ["unix"=> $val, "str"=> date("n月j日", $val). DAY_JP[date("w", $val)]. "曜日"]);
    }
    return $view->render($response, 'manage/menu.twig', $data);
}

function deleteMenuCtrl($response, $view, $db, $deleteTarget){
    $bentoTable = new Bento($db);
    $time = $bentoTable->selectFromId($deleteTarget)["start_sale_at"];
    $date = strtotime(date("Y-m-d", $time));
    // メニュー削除
    $bentoTable->deleteFromId($deleteTarget);

    return menuManageCtrl($response, $view, $db, $date);
}
