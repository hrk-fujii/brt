<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Util\MemberUtil;
use Util\MailUtil;
use Model\Dao\Users;
use Model\Dao\Confirm_mails;


// ユーザー登録
$app->get('/entry', function (Request $request, Response $response) {
    // メール認証データベース
	$cMailTable = new Confirm_mails($this->db);
    
    // パラメータ取得
    if (!empty($request->getQueryParams()["session"])){
        $param = $request->getUri()->getQueryParams()["session"];
        $cMailData = $cMailTable->selectFromParam($param);
    }

    if (empty($cMailData)){ // 未確認ならばメールアドレス認証へ
        return confirmMailCtrl($response, $this->view, $cMailTable);
    } else{ // 確認済みならばユーザー登録またはパスワードリセットへ
        $_SESSION["brt-confirmParam"] = $cMailData["session_id"];
        $_SESSION["brt-confirmMail"] = $cMailData["mail"];
        $cMailTable->deleteFromParam($param);
        return userEntryCtrl($response, $this->view, $this->db);
    }
});

$app->post('/entry', function (Request $request, Response $response) {
    $input = $request->getParsedBody();

    // メール確認データベース
    $cMailTable = new Confirm_mails($this->db);
    
    // メールアドレス確認
    if (!empty($input["newMail"])){
        $message = ValidationUtil::checkString("ntut-mail", $input["newMail"]);
        if ($message===""){ // メールアドレスが正常
            return sendConfirmMailCtrl($request, $response, $this->view, $cMailTable, $input["newMail"]);
        } else{
            $message = ValidationUtil::checkString("mail", $input["mail"]);
            if ($message==="" && $_SESSION["brt-userType"]===USER_TYPE_ADMIN){
                return sendConfirmMailCtrl($request, $response, $this->view, $cMailTable, $input["mail"]);
            } elseif (empty($message)){
                $message = "このメールアドレスは、認証に利用できません。大学発行のメールアドレスでやり直してください。";
            }
        }
        // 認証再試行
        return confirmMailCtrl($response, $this->view, $cMailTable, $message);
    
    // ユーザー登録
    } elseif (!empty($input["new-confirmMail"])){
        $userTable = new Users($this->db);
        
        if (empty($_SESSION["brt-confirmMail"])){
            return ViewUtil::error($response, $this->view);
        } else{
            $message = ""; // バリデーション
            if ($input["new-confirmMail"]===$_SESSION["brt-concirmMail"]){ // メールアドレス有効
                $message = $message. "・メールアドレスが謝っています。\n";
            }
            $message = $message. ValidationUtil::checkString("userName", $input["name"], "・", "\n");
            $message = $message. ValidationUtil::checkString("userPassword", $input["password"], "・", "\n");
            if (!empty($userTable->selectFromName($input["name"]))){
                $message = $message. "・このユーザー名は、すでに使用されています。\n";
            }
            if ($input["password"]!==$input["confirmPassword"]){
                $message = $message. "・パスワードと、パスワードの確認が一致していません。\n";
            }
            if (!empty($message)){ // 再試行
                return userEntryCtrl($response, $this->view, $this->db, mb_substr($message, 0, -1), ["mail"=> $input["update-confirmMail"], "name"=> $input["name"]]);
            }
            
            // 新規登録
            $userData = $userTable->selectFromMail($input["new-confirmMail"]);
            if (empty($userData)){
                $param = MemberUtil::makeRandomId();
                $userId = $userTable->insertUser($input["name"], $input["new-confirmMail"], password_hash($input["password"]), $param);
                if ($userId!==FALSE){
                    MemberUtil::login($userId);
                    $data = ["name"=> $input["name"], "url"=> $request->getUri->getBaseUrl()."/?id=".$param];
                    return $this->view->render($response, 'entry/newOk.twig', $data);
                } else{
                    return ViewUtil::error($response, $this->view);
                }
            } else{
                return ViewUtil::error($response, $this->view);
            }
        }

    // パスワード更新
    } elseif (!empty($input["update-confirmMail"])){
        $userTable = new Users($this->db);

        if (empty($_SESSION["brt-confirmMail"])){
            return ViewUtil::error($response, $this->view);
        } else{
            $message = ""; // バリデーション
            if ($input["update-confirmMail"]===$_SESSION["brt-concirmMail"]){ // メールアドレス有効
                $message = $message. "・メールアドレスが謝っています。\n";
            }
            $message = $message. ValidationUtil::checkString("userName", $input["name"], "・", "\n");
            $message = $message. ValidationUtil::checkString("userPassword", $input["password"], "・", "\n");
            if ($input["password"]!==$input["confirmPassword"]){
                $message = $message. "・パスワードと、パスワードの確認が一致していません。\n";
            }
            if (!empty($message)){ // 再試行
                return userEntryCtrl($response, $this->view, $this->db, mb_substr($message, 0, -1), ["mail"=> $input["update-confirmMail"], "name"=> $input["name"]]);
            }
            
            // 更新
            $userData = $userTable->selectFromMail($input["update-confirmMail"]);
            if (!empty($userData)){
                $param = MemberUtil::makeRandomId();
                if ($userTable->updatePassword_hashFromId($userData["id"], password_hash($input["password"]), $param)===TRUE){
                    MemberUtil::login($userData["id"]);
                    $data = ["name"=> $input["name"], "url"=> $request->getUri->getBaseUrl()."/?id=".$param];
                    return $this->view->render($response, 'entry/updateOk.twig', $data);
                } else{
                    return ViewUtil::error($response, $this->view);
                }
            } else{
                return ViewUtil::error($response, $this->view);
            }
        }
    }
});

// メールアドレス確認フォーム
function confirmMailCtrl($response, $view, $cMailTable, $message=""){
    $data = ["message"=> $message];
    return $view->render($response, 'entry/index.twig', $data);
}

// 確認メール送信
function sendConfirmMailCtrl($request, $response, $view, $cMailTable, $mail){
    $data = ["mail"=> $mail];
    $param = MemberUtil::makeRandomId();

    // db登録
    if (!empty($cMailTable->selectFromMail($mail))){ // 古いのは削除
        $cMailTable->deleteFromMail($mail);
    }
    $cMailTable->insertItem($mail, $param);
    
    // メール送信
    $title = "BRT メールアドレスの確認";
    $text = "BRTのご利用、ありがとうございます。\nユーザー登録、パスワードをリセットする場合は、以下のURLにアクセスしてください。\n\n".
        $request->getUri()->getBaseUrl(). "/entry?session=". $param.
        "\n\nBRT運営チーム";
    
    MailUtil::send($title, $text, "no-reply", $mail);
        return $view->render($response, 'entry/send.twig', $data);
}

// ユーザー登録フォーム
function userEntryCtrl($response, $view, $db, $message="", $previousData=["mail"=> "", "name"=> ""]){
    $data=["message"=> $message, "mail"=> $previousData["mail"], "name"=> $previousData["name"]];
    
    // ユーザー登録情報確認
    $userTable = new Users($db, $message="");
    $userData = $userTable->selectFromMail($_SESSION["brt-confirmMail"]);
    if (empty($userData)){ // ユーザー登録
        return $view->render($response, 'entry/new.twig', $data);
    } else{ // パスワード更新
        return $view->render($response, 'entry/update.twig', $data);
    }
}
