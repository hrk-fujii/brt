<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Util\MailUtil;
use Model\Dao\Users;
use Model\Dao\Bento;



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
        writeMenuCtrl($response, $this->view, $this->db, $input["editSubmit"]);
    
    // 新規登録
    } elseif (!empty($input["newSubmit"])){
        writeMenuCtrl($response, $this->view, $this->db);
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
            return editMenuCtrl($response, $this->view, $input, NULL, $message);

        }
    
    // 確定動作
    } elseif (!empty($input["editSubmit"])){
        return writeMenuCtrl($response, $this->view, $this->db);
    }
});

// メニュー編集フォーム
function editMenuCtrl($response, $view, $data=[], $menuId=NULL, $message=""){
    $data["menuId"] = $menuId;
    $data["startSaleDateArray"] = [];
    $data["message"] = $message;
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

    return $view->render($response, 'manage/confirmEditMenu.twig', $data);
}

// メニュー書き込み
function writeMenuCtrl($response, $view, $db, $menuId=NULL){
    $bentoTable = new Bento($db);
    $data = $_SESSION["brt-confirmEditMenu"];
    foreach ($data["name"] as $k=> $v){
        if (!empty($data["name"][$k])){
            if (!empty($data["menuId"]) && $data["menuId"]===$menuId && !empty($bentoTable->selectFromId($data["menuId"]))){
                return $bentoTable->updateFromId($data["menuId"], $data["name"][$k], $data["discription"][$k], $data["orderDeadlineAt"], $data["startSaleAt"], $data["endSaleAt"], NULL);
            } else{
                $ret = $bentoTable->insertItem($data["name"][$k], $data["discription"][$k], $data["orderDeadlineAt"], $data["startSaleAt"], $data["endSaleAt"], NULL);
            }
        }
    }
    var_dump($ret);
}
