<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Model\Dao\Users;
use Model\Dao\Confirm_mails;


// ユーザー登録
$app->get('/entry', function (Request $request, Response $response) {
    // メール認証データベース
	cMailTable = new Confirm_mails($this->db);
    
    // 古いデータは削除
    $cMailTable->delete([
        "set_at"=> ["<", time() - 180]
    ]);

    // パラメータ取得
    if (!empty($request->getUri()->getQueryParams()["session"])){
        $param = $request->getUri()->getQueryParams()["session"];
    }

    // パラメータを確認
    if (!empty($param)){
        cMailData = cMailTable->select([
            "session_id"=> $param
        ]);
    } 

    if (empty($cMailData)){ // 未確認ならばメールアドレス認証へ
        return confirmMailCtrl($response, $this->view, $cMailTable);
    } else{ // 確認済みならばユーザー登録またはパスワードリセットへ
        $_SESSION["brt-cMailData"] = $cMailData;
        return userEntryCtrl($response, $this->view, $this->db);
    }
});

$app->post('/entry', function (Request $request, Response $response) {
    $input = $request->getParsedBody();

    // メール確認データベース
    $cMailTable = new Confirm_mails($this->db);
    
    // メールアドレス確認
    if (!empty($input["newMail"])){
        $message = ValidationUtil::checkString("ntut-email", $input["newMail"]);
        if ($message===""){ // メールアドレスが正常
            return sendConfirmMailCtrl($request, $response, $this->view, $cMailTable, $input["newMail"]);
        } else{
            $message = ValidationUtil::checkString("email", $input["mail"]);
            if ($message==="" && $_SESSION["brt-userType"]===USER_TYPE_ADMIN){
                return sendConfirmMailCtrl($request, $response, $this->view, $cMailTable, $input["mail"]);
            } elseif (empty($message)){
                $message = "このメールアドレスは、認証に利用できません。大学発行のメールアドレスでやり直してください。";
            }
        }
        // 認証再試行
        return confirmMailCtrl($response, $this->view, $cMailTable, $message){
    
    // ユーザー登録
    } elseif (!empty($input["new-confirmMail"])){
        if (empty($_SESSION["brt-cMailData"])){
            return ViewUtil::error($response, $this->view);
        } else{
            $userTable = new Users($this->db);
            
            $message = ""; // バリデーション
            if ($input["new-confirmMail"]===$_SESSION["brt-cMailData"]["mail"]){ // メールアドレス有効
                $message = $message. "・メールアドレスが謝っています。\n";
            }
            $message = $message. ValidationUtil::checkString("userName", $input["name"], "・", "\n");
            $message = $message. ValidationUtil::checkString("userPassword", $input["password"], "・", "\n");
            if (!empty($userTable->select(["name"=> $input["name"]]))){
                $message = $message. "・このユーザー名は、すでに使用されています。\n";
            }
            if ($input["password"]!==$input["confirmPassword"]){
                $message = $message. "・パスワードと、パスワードの確認が一致していません。\n";
            }
            if (!empty($message)){ // 再試行
                return userEntryCtrl($response, $this->view, $this->db, mb_substr($message, 0, -1), ["mail"=> $input["update-confirmMail"], "name"=> $input["name"]]);
            }
            
            // 新規登録
            $userData = $userTable->select([
                "mail"=> $input["new-confirmMail"]
            ]);
            if (empty($userData)){
                $param = hash('sha256', random_int(PHP_INT_MIN, PHP_INT_MAX));
                $userId = $userTable->insert([
                    "name"=> $input["name"],
                    "mail"=> $input["new-confirmMail"],
                    "password_hash"=> password_hash($input["password"]),
                    "url_param"=> $param,
                    "last_updated_at"=> time(),
                    "last_logdin_at"=> time(),
                    "type"=> USER_TYPE_GENERAL
                ]);
            }
            if (!empty($userId) && $userId!==FALSE && !empty($userTable->select([
                "id"=> $userId,
                "name"=> $input["name"]
            ]))){
                $_SESSION["brt-userId"] = $userId;
                $_SESSION["brt-userName"] = $input["name"];
                $data = ["name"=> $input["name"], "url"=> $request->getUri->getBaseUrl()."/?id=".$param];
                return $this->view->render($response, 'entry/confirm.wig', $data);
            } else{
                return ViewUtil::error($response, $this->view);
            }
        }

    // パスワード更新
    } elseif (!empty($input["update-confirmMail"])){
        if (empty($_SESSION["brt-cMailData"])){
            return ViewUtil::error($response, $this->view);
        } else{
            $userTable = new Users($this->db);
            
            $message = ""; // バリデーション
            if ($input["update-confirmMail"]===$_SESSION["brt-cMailData"]["mail"]){ // メールアドレス有効
                $message = $message. "・メールアドレスが謝っています。\n";
            }
            $message = $message. ValidationUtil::checkString("userPassword", $input["password"], "・", "\n");
            if (!empty($userTable->select(["name"=> $input["name"]]))){
                $message = $message. "・このユーザー名は、すでに使用されています。\n";
            }
            if ($input["password"]!==$input["confirmPassword"]){
                $message = $message. "・パスワードと、パスワードの確認が一致していません。\n";
            }
            if (!empty($message)){ // 再試行
                return userEntryCtrl($response, $this->view, $this->db, mb_substr($message, 0, -1), ["mail"=> $input["update-confirmMail"], "name"=> $input["name"]]);
            }
            
            // 新規登録
            $userData = $userTable->select([
                "mail"=> $input["update-confirmMail"]
            ]);
            if (empty($userData)){
                $param = hash('sha256', random_int(PHP_INT_MIN, PHP_INT_MAX));
                $userId = $userTable->insert([
                    "name"=> $input["name"],
                    "mail"=> $input["new-confirmMail"],
                    "password_hash"=> password_hash($input["password"]),
                    "url_param"=> $param,
                    "last_updated_at"=> time(),
                    "last_logdin_at"=> time(),
                    "type"=> USER_TYPE_GENERAL
                ]);
            }
            if (!empty($userId) && $userId!==FALSE && !empty($userTable->select([
                "id"=> $userId,
                "name"=> $input["name"]
            ]))){
                $_SESSION["brt-userId"] = $userId;
                $_SESSION["brt-userName"] = $input["name"];
                $data = ["name"=> $input["name"], "url"=> $request->getUri->getBaseUrl()."/?id=".$param];
                return $this->view->render($response, 'entry/confirm.wig', $data);
            } else{
                return ViewUtil::error($response, $this->view);
            }
        }
    }
});

// メールアドレス確認フォーム
function confirmMailCtrl($response, $view, $cMailTable, $message=""){
    $data = ["message"=> $message];
    return $view->render($response, 'entry/send.twig', $data);
}

// 確認メール送信
function sendConfirmMailCtrl($request, $request, $view, $cMailTable, $mail){
    $data = ["mail"=> $mail];
    $param = hash('sha256', random_int(PHP_INT_MIN, PHP_INT_MAX));

    // db登録
    $cMailTable->insert([
        "mail"=> $mail,
        "session_id"=> $param,
        "set_at"=> time()
    ]);
    
    // メール送信
    $title = "BRT メールアドレスの確認"
    $text = "BRTのご利用、ありがとうございます。\nユーザー登録、パスワードをリセットする場合は、以下のURLにアクセスしてください。\n\n".
        $request->getUri()->getBaseUrl(). "/entry?session=". $param.
        "\n\nBRT運営チーム"
    
    return $view->render($response, 'entry/send.twig', $data);
}

// ユーザー登録フォーム
function userEntryCtrl($response, $view, $db, $message="", $previousData=["mail"=> "", "name"=> ""]){
    $data=["message"=> $message, "mail"=> $previousData["mail"], "name"=> $previousData["name"]];
    
    // ユーザー登録情報確認
    $userTable = new Users($db, $message="");
    $userData = $userTable->select([
        "mail"=> $_SESSION["brt-cMailData"]
    ]);
    if (empty($userData)){ // ユーザー登録
        return $view->render($response, 'entry/new.twig', $data);
    } else{ // パスワード更新
        return $view->render($response, 'entry/password.twig', $data);
    }
}
