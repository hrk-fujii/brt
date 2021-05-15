<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Util\OrderUtil;
use Util\ViewUtil;
use Util\MailUtil;
use Model\Dao\Users;
use Model\Dao\Bento;
use Model\Dao\Orders;



// メニュー管理表示
$app->get('/mypage/menu', function (Request $request, Response $response) {
    // 予約メニュー管理
    return showMyMenuManage($response, $this->view, $this->db);
});

// 月指定メニュー管理表示
$app->post('/mypage/menu', function (Request $request, Response $response) {
    // 予約メニュー管理
    $input = $request->getParsedBody();
    return showMyMenuManage($response, $this->view, $this->db, $input);
});

$app->post('/mypage/menu/edit', function (Request $request, Response $response) {
    // 多重注文防止
    $input = $request->getParsedBody();

    // ターゲットが指定されていれば削除登録
    if (!empty($input["deleteTarget"])){
        return deleteOrderProcessCtrl($response, $this->view, $this->db, $input);
    }

});

function deleteOrderProcessCtrl($response, $view, $db, $input){
    $userTable = new Users($db);
    $userData = $userTable->selectFromId($_SESSION["brt-userId"]);
    $bentoTable = new Bento($db);
    $orderTable = new Orders($db);

    // 有効性確認
    if (empty($input["deleteTarget"])){
        return ViewUtil::error($response, $view, "不正なアクセスが検出されたため、サービスを継続できません。");
    }
    $orderData = $orderTable->selectFromId($input["deleteTarget"]);

    if (empty($orderData) || $orderData["users_id"]!==$_SESSION["brt-userId"]){
        return ViewUtil::error($response, $view, "取り消し処理に失敗しました、一時的なエラー、または、予約がすでに削除されている可能性があります。恐れ入りますが、しばらくたってから再試行してください。");
    }
    $bentoData = $bentoTable->selectFromId($orderData["bento_id"]);

    // 締切確認
    if (((int)$bentoData["flag"]&BENTO_ORDER_CLOSED===BENTO_ORDER_CLOSED) || ($bentoData["order_deadline_at"] <= time())){
        return deleteOrderMessageCtrl($response, $view, $bentoData["end_sale_at"], "この弁当の予約はすでに締め切られており、取り消すことができません。");
    }

    // ロック状況確認
    if (empty($input["unlock"])){
        return deleteOrderMessageCtrl($response, $view, $bentoData["end_sale_at"], "「取消」ボタン左の「取り消し確認」にチェックを入れてから操作してください。誤操作防止のために必要です。なお、チェックを入れて取り消し操作を行った場合、確認画面は表示されず、予約が確定します。");
    }

    // 取り消し
    if (((int)$orderData["flag"]&BENTO_LARGE1)===BENTO_LARGE1){
        $bentoName = $bentoData["name"]. "（大盛り）";
    } else{
        $bentoName = $bentoData["name"];
    }
    
    $orderTable->deleteFromId($input["deleteTarget"]);
    $body = $userData['last_name']. " ". $userData['first_name']. "様\nBRTをご利用いただき、ありがとうございます。\n以下の予約を取り消しました。\n\n内容\n".
        "・". $bentoName. "    ". $orderData['quantity']. "個\n\n".
        "なお、予約の締め切り時刻までは、再度予約することもできます。\n\nBRT運営チーム";
    MailUtil::send("弁当の予約を取り消しました", $body, "noreply", $userData["mail"]);
    return deleteOrderMessageCtrl($response, $view, $bentoData["end_sale_at"], $bentoData['name']. "、". $orderData['quantity']. "個の予約を取り消しました。予約の締め切り時刻までは、再度予約することも可能です。");
}

function deleteOrderMessageCtrl($response, $view, $startSaleAt, $message){
    $data = ["saleDate"=> strtotime(date("Y-m-d", $startSaleAt)), "message"=> $message];

    return $view->render($response, 'mypage/menuMessage.twig', $data);
}


function showMyMenuManage($response, $view, $db, $input=NULL){
    $data = [];
    if (!empty($input)){
        $data = $input;
    }
    
    // 日付があれば、調整して適用
    if (!empty($input["showTargetMonth"])){
        $targetMonth = strtotime(date("Y-m-1", $input["showTargetMonth"]));
        $orderArray = OrderUtil::getBentoFromTime($targetMonth, $targetMonth + 3600 * 24 * date("t", $targetMonth) - 1);
    } else{
        $orderArray = OrderUtil::getBentoFromTime(strtotime(date("Y-m-1", time())), strtotime(date("Y-m-1", time())) + 3600 * 24 * date("t", time()) - 1);
    }
    
    // 月一覧
    $monthTmp = strtotime(date("Y-m-1", time() + 11 * 3600 * 24));
    $data["showTargetMonthArray"] = [];
    for ($i=0; $i<=2; $i++){
        array_push($data["showTargetMonthArray"], ["str"=> date("Y年n月", $monthTmp), "unix"=> $monthTmp]);
        $monthTmp = strtotime(date("Y-m-1", $monthTmp - 1));
    }

    foreach ($orderArray as &$b){
        $b["id"] = $b["id"][1];
        $b["startSaleStr"] = date("n月j日", $b["start_sale_at"]). "(". DAY_JP[date("w", $b["start_sale_at"])]. ")". date("H:i", $b["start_sale_at"]);
        $b["takeDeadlineStr"] = date("H:i", $b["end_sale_at"] - ORDER_TAKE_LIMIT_BEFORE_MINUTE * 60);
        $b["saleLengthMinuteOnly"] = (int)(($b["end_sale_at"]-$b["start_sale_at"])/60);
        $b["orderDeadlineStr"] = date("j日", $b["order_deadline_at"]). "(". DAY_JP[date("w", $b["order_deadline_at"])]. ")". date("H:i", $b["order_deadline_at"]);
        if (((int)$b["flag"][0]&BENTO_ORDER_CLOSED)===BENTO_ORDER_CLOSED){
            $b["orderDeadlineOver"] = "sent";
        } elseif($b["order_deadline_at"] > time()){
            $b["orderDeadlineOver"] = "no";
        } else{
            $b["orderDeadlineOver"] = "over";
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

    return $view->render($response, 'mypage/menu.twig', $data);
}

