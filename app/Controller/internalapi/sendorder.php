<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\MailUtil;
use Model\Dao\Users;
use Util\OrderUtil;

$app->get('/internalapi\sendorder', function (Request $request, Response $response) {
    $ret = OrderUtil::pullOrderFromDeadlineAt(time() - 3600 * 24, time() + 30);
    if (empty($ret)){
        return FALSE;
    }

    // ログを記録
    $idArray = [];
    foreach ($ret as $o){
        array_push($idArray, orderUtil::addHistory($o["student_no"], $o["first_name"], $o["last_name"], $o["name"], $o["quantity"], $o["order_deadline_at"], $o["start_sale_at"]));
    }


    // 本文のリスト部分作成
    $orderStr = "";
    foreach ($idArray as $i){
        $orderHistoryTable- = new Order_history($this->db);
        $ret = $orderHistoryTable->selectFromId($id);
        $orderStr = $orderStr. "No".$ret["id"]. "  ". $ret["last_name"]. " ". $ret["first_name"]. "\n・". $ret["bento_name"]. " (". $ret["order_deadline_at"]. "締切) ". $ret["quantity"]. "個\n\n";
    }

    // メール本文
    $text = "BRT管理者様\n\n以下の予約を回収いたしましたので、お知らせいたします。担当者は内容をご確認の上、処理願います。\n\n----内容----\n\n". $orderStr. "BRT自動配信システム"

    // メール送信
    foreach ($userTable->selectFromType(USER_TYPE_ADMIN) as $u){
        $ret = MailUtil::send("予約を回収しました", $text, "no-reply", $u["mail"]);
    }
    echo($ret);
});
