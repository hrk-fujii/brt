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
    
    // ロック状況確認
    if (empty($input["unlock"])){
        return orderMessageCtrl($response, $view, $bentoData["start_sale_at"], "「予約」ボタン左の「予約確認」にチェックを入れてから操作してください。誤操作防止のために必要です。なお、チェックを入れて予約操作を行った場合、確認画面は表示されず、予約が確定します。");
    }

    // 予約登録
    if (!empty($orderTable->insertItem($bentoData["id"], $input["quantity"], $_SESSION["brt-userId"]))){
        $body = $userData['last_name']. " ". $userData['first_name']. "様\nBRTをご利用いただき、ありがとうございます。\n以下の内容で予約を受け付けました。\n現在の予約状況につきましては、マイページにてご確認いただけます。\n\n内容\n".
            "・". $bentoData['name']. "    ". $input['quantity']. "個\n\n".
            "なお、予約の締め切り時刻までは、マイページから予約を取り消すことができます。\n\nBRT運営チーム";
        MailUtil::send("弁当を予約しました", $body, "noreply", $userData["mail"]);
        return orderMessageCtrl($response, $view, $bentoData["start_sale_at"], $bentoData['name']. "、". $input['quantity']. "個の予約を受け付けました。予約の締め切り時刻までは、マイページから取り消しができます。");
    }

    return orderMessageCtrl($response, $view, $bentoData["start_sale_at"], "予約処理に失敗しました。しばらくたってから再試行してください。");
}

function orderMessageCtrl($response, $view, $startSaleAt, $message){
    $data = ["saleDate"=> strtotime(date("Y-m-d", $startSaleAt)), "message"=> $message];

    return $view->render($response, 'order/message.twig', $data);
}
