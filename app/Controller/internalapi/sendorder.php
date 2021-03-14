<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\MailUtil;
use Model\Dao\Users;
use Model\Dao\Order_history;
use Util\OrderUtil;

$app->get('/internalapi/sendorder', function (Request $request, Response $response) {
    $userTable = new Users($this->db);
    $ret = OrderUtil::pullOrderFromDeadlineAt(time() - 3600 * 24, time() + 30);
    if (empty($ret)){
        return FALSE;
    }

    // 本文のリスト部分作成
    $orderStr = "";
    $orderHistoryTable = new Order_history($this->db);
    foreach ($ret as $b){
        $orderStr = $orderStr. "<". $b["name"]. "  計". $b["quantity"]. "個>\n(". $b["orderDeadlineAtStr"]. "締切分) \n";
        foreach ($b["order"] as $o){
            $orderStr = $orderStr. "・". $o["name"]. " ". $o["studentNo"]. ": ". $o["quantity"]. "個\n";
        }
        $orderStr = $orderStr. "\n";
    }

    // ログを記録
    $orderNo = orderUtil::addHistory($orderStr);

    // メール本文
    $text = "このメールは、BRT管理者宛に配信しています。\n\n以下の予約を回収いたしましたので、内容をご確認の上、処理願います。\n\n----内容--(NO". $orderNo. ")----\n". $orderStr. "BRT自動配信システム";

    // メール送信
    foreach ($userTable->selectFromType(USER_TYPE_ADMIN) as $u){
        $ret = MailUtil::send("予約を回収しました", $text, "noreply", $u["mail"]);
    }
    echo($ret);
});
