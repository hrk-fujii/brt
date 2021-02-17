<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Util\MailUtil;
use Model\Dao\Users;



// ユーザー登録
$app->get('/manage/menu', function (Request $request, Response $response) {
    $data = [];
    return $this->view->render($response, 'manage/menu.twig', $data);
});

$app->get('/manage/menu/new', function (Request $request, Response $response) {
    return editMenuCtrl($response, $this->view);
});


$app->post('/manage/menu', function (Request $request, Response $response) {
    $input = $request->getParsedBody();

    // 編集なら
    if (!empty($input["editTarget"])){
        editMailCtrl($response, $this->view, $input, $input["editTarget"]);
    
    // 編集結果書き込み
    } elseif (!empty($input["editSubmit"])){
        writeMenuCtrl($response, $this->view, $this->db, $input, $input["editSubmit"]);
    
    // 新規登録
    } elseif (!empty($input["newSubmit"])){
        writeMenuCtrl($response, $this->view, $this->db, $input);
    }
});

$app->post('/manage/menu/new', function (Request $request, Response $response) {
    $input = $request->getParsedBody();

    // 編集直後なら
    if (!empty($input["goToConfirm"])){
        $_SESSION["brt-confirmEditMenu"] = $input;
        return confirmEditMenuCtrl($response, $this->view);
    }
});

// メニュー編集フォーム
function editMenuCtrl($response, $view, $data=[], $menuId=NULL, $message=""){
    $data["menuId"] = $menuId;
    $data["startSaleDateArray"] = [];
    if (empty($data["startSaleHour"])){
        $data["startSaleHour"] = DEFAULT_START_SALE_HOUR;
    }
    if (empty($data["startSaleMinute"])){
        $data["startSaleMinute"] = DEFAULT_START_SALE_MINUTE;
    }
    if (empty($data["saleLengthHour"])){
        $data["saleLengthHour"] = DEFAULT_SALE_LENGTH_HOUR;
    }
    if (empty($data["saleLengthMinute"])){
        $data["saleLengthMinute"] = DEFAULT_SALE_LENGTH_MINUTE;
    }
    if (empty($data["orderDeadlineDate"])){
        $data["orderDeadlineDate"] = DEFAULT_ORDER_DEADLINE_DATE_BEFORE;
    }
    if (empty($data["orderDeadlineHour"])){
        $data["orderDeadlineHour"] = DEFAULT_ORDER_DEADLINE_HOUR;
    }
    if (empty($data["orderDeadlineMinute"])){
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
    $startSaleAt = $_SESSION["brt-confirmEditMenu"]["startSaleDate"] + $_SESSION["brt-confirmEditMenu"]["startSaleHour"] * 60 * 60 + $_SESSION["brt-confirmEditMenu"]["startSaleMinute"] * 60;
    $data["startSaleStr"] = date("n月j日", $startSaleAt). DAY_JP[date("w", $startSaleAt)]. "曜日". date("H時i分", $startSaleAt);
    $orderDeadlineAt = $_SESSION["brt-confirmEditMenu"]["startSaleDate"] - $_SESSION["brt-confirmEditMenu"]["orderDeadlineDate"] * 60 * 60 * 24 + $_SESSION["brt-confirmEditMenu"]["orderDeadlineHour"] * 60 * 60 + $_SESSION["brt-confirmEditMenu"]["orderDeadlineMinute"] * 60;
    $data["orderDeadlineStr"] = date("n月j日", $orderDeadlineAt). DAY_JP[date("w", $orderDeadlineAt)]. "曜日". date("H時i分", $orderDeadlineAt);
    return $view->render($response, 'manage/confirmEditMenu.twig', $data);
}
