<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\MailUtil;
use Model\Dao\Users;
use Model\Dao\Bento;
use Model\Dao\Orders;
use Util\ViewUtil;


$app->post('/order', function (Request $request, Response $response) {
    // 多重注文防止
    if (empty($_SESSION["brt-orderReady"])){
        return ViewUtil::error($response, $this->view, "無効なアクセスが検出されたため、サービスの継続ができません。恐れ入りますが、トップページに戻り、最初からやり直してください。");
    }
    $_SESSION["brt-orderReady"] = FALSE;

    $input = $request->getParsedBody();

    // ターゲットが指定されていれば注文登録
    if (!empty($input["orderTarget"])){
        return orderProcessCtrl($response, $this->view, $this->db, $input);
    }

});

function orderProcessCtrl($response, $view, $db, $input){
    $bentoTable = new Bento($db);
    $userTable = new Users($db);
    $orderTable = new orders($db);
    $bentoData = $bentoTable->selectFromId($input["orderTarget"]);
    $userData = $userTable->selectFromId($_SESSION["brt-userId"]);

    // 有効性確認
    if (empty($bentoData)){
        return ViewUtil::error($response, $view, "予約処理に失敗しました、一時的なエラー、または、弁当が削除された可能性があります。恐れ入りますが、最新のメニューをご確認の上、しばらくたってから再試行してください。");
    }
    
    // 締切確認
    if (((int)$bentoData["flag"]&BENTO_ORDER_CLOSED===BENTO_ORDER_CLOSED) || ($bentoData["order_deadline_at"] <= time())){
        return orderMessageCtrl($response, $view, $bentoData["start_sale_at"], "この弁当は、すでに予約が締め切られています。在庫状況等につきましては、店頭にてご確認ください。");
    }

    // 個数の確認
    if (empty($input["quantity"]) || ($input["quantity"]-(int)$input["quantity"]!==0) || ($input["quantity"] > 99) || ($input["quantity"] < 1)){
        return orderMessageCtrl($response, $view, $bentoData["start_sale_at"], "「予約」ボタンの左の入力欄で、予約個数を設定してください。なお、誤入力防止のため、1度に100個以上の予約はできません。");
    }
    
    // 分量選択
    if (!isset($input["serving"]) || !($input["serving"]==="0" || $input["serving"]==BENTO_LARGE1)){
        return orderMessageCtrl($response, $view, $bentoData["start_sale_at"], "「予約」ボタン左のコンボボックスで、サイズを選択してください。なお、サイズを選択して予約操作を行った場合、確認画面は表示されず、予約が確定します。");
    }

    // 予約登録
    if (!empty($orderTable->insertItem($bentoData["id"], $input["quantity"], $_SESSION["brt-userId"], (int)$input["serving"]))){
        $servingPrice = 0;
        if (($bentoData["flag"] & BENTO_LARGE1)===BENTO_LARGE1){
            $servingPrice = BENTO_LARGE1_PRICE;
        } elseif (($bentoData["flag"] & BENTO_LARGE2)===BENTO_LARGE2){
            $servingPrice = BENTO_LARGE2_PRICE;
        }
        $serving = "";
        if ($input["serving"]==BENTO_LARGE1){
            $serving = "（大盛り）";
        }
        $body = $userData['last_name']. " ". $userData['first_name']. "様\nBRTをご利用いただき、ありがとうございます。\n以下の内容で予約を受け付けました。\n現在の予約状況につきましては、マイページにてご確認いただけます。\n\n内容\n".
            "・". $bentoData['name']. $serving. "    ". $input['quantity']. "個\n".
            "合計金額: ". ($bentoData["price"] + $servingPrice) * $input["quantity"]. "円\n".
            "受取期間: ". date("n月j日", $bentoData["start_sale_at"]). "(". DAY_JP[date("w", $bentoData["start_sale_at"])]. ")". date("H:i", $bentoData["start_sale_at"]). " から ". date("H:i", $bentoData["end_sale_at"] - ORDER_TAKE_LIMIT_BEFORE_MINUTE * 60). " まで".
            "\n\n".
            "販売所にて、現金と引き替えにお渡しいたします。\n上記受取期間内に、必ずお受け取りください。\n".
            "なお、予約の締め切り時刻までは、マイページから予約を取り消すことができます。\n\nBRT運営チーム";
        MailUtil::send("弁当を予約しました", $body, "noreply", $userData["mail"]);
        return orderMessageCtrl($response, $view, $bentoData["start_sale_at"], $bentoData['name']. $serving. "、". $input['quantity']. "個の予約を受け付けました。\n受け取り期間内に、必ずお受け取りください。販売所にて、担当者に氏名をお伝えください。現金と引き換えでのお渡しとなります。\n予約内容を記載したメールを送信しましたので、ご確認ください。予約状況の確認、締め切り前の予約の取り消し等は、マイページから行うことができます。");
    }

    return orderMessageCtrl($response, $view, $bentoData["start_sale_at"], "予約処理に失敗しました。しばらくたってから再試行してください。");
}

function orderMessageCtrl($response, $view, $startSaleAt, $message){
    $data = ["saleDate"=> strtotime(date("Y-m-d", $startSaleAt)), "message"=> $message];

    return $view->render($response, 'order/message.twig', $data);
}
