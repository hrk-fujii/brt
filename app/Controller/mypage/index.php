<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Util\OrderUtil;
use Model\Dao\Users;
use Model\Dao\Bento;



// メニュー表示その他
$app->get('/mypage', function (Request $request, Response $response) {
    $data = [];
    $userTable = new Users($this->db);
    $userData = $userTable->selectFromId($_SESSION["brt-userId"]);
    $data["firstName"] = $userData["first_name"];
    $data["lastName"] = $userData["last_name"];
    
    // 受け取り前のリストを作成
    $orderArray = OrderUtil::getBentoFromTime(time(), time() + 3600 * 24 * 360);

    foreach ($orderArray as &$b){
        $b["startSaleStr"] = date("n月j日", $b["start_sale_at"]). "(". DAY_JP[date("w", $b["start_sale_at"])]. ")". date("H:i", $b["start_sale_at"]);
        $b["takeDeadlineStr"] = date("H:i", $b["end_sale_at"] - ORDER_TAKE_LIMIT_BEFORE_MINUTE * 60);
        $b["saleLengthMinuteOnly"] = (int)(($b["end_sale_at"]-$b["start_sale_at"])/60);
        if ($b["flag"][0]&BENTO_ORDER_CLOSED===BENTO_ORDER_CLOSED){
            $b["orderDeadlineStr"] = NULL; // 予約できない
        } else{
            $b["orderDeadlineStr"] = date("j日", $b["order_deadline_at"]). "(". DAY_JP[date("w", $b["order_deadline_at"])]. ")". date("H:i", $b["order_deadline_at"]);
        }
        // 弁当大盛り対応
        $b["flag"][0] = (int)$b["flag"][0];
        $b["flag"][1] = (int)$b["flag"][1];
        if (($b["flag"][1]===BENTO_LARGE1) && (($b["flag"][0]&BENTO_LARGE1)===BENTO_LARGE1)){
            $b["totalPrice"] = ($b["price"]+BENTO_LARGE1_PRICE) * $b["quantity"];
            $b["name"] = $b["name"]. "（大盛り）";
        } elseif (($b["flag"][1]===BENTO_LARGE1) && (($b["flag"][0]&BENTO_LARGE2)===BENTO_LARGE2)){
            $b["totalPrice"] = ($b["price"]+BENTO_LARGE2_PRICE) * $b["quantity"];
            $b["name"] = $b["name"]. "（大盛り）";
        } else{
            $b["totalPrice"] = $b["price"] * $b["quantity"];
        }
    }
    $data["bentoArray"] = $orderArray;
    
    // 挨拶パターン
    if (date("H", time()) < 2 || date("H", time()) >= 21){
        $data["hello"] = "お疲れ様です";
    } elseif (date("H", time()) >= 18){
        $data["hello"] = "こんばんは";
    } elseif (date("H", time()) >= 10){
        $data["hello"] = "こんにちは";
    } elseif (date("H", time()) >= 4){
        $data["hello"] = "おはようございます";
    } else{
        $data["hello"] = "ようこそ";
    }
    return $this->view->render($response, 'mypage/index.twig', $data);
});
