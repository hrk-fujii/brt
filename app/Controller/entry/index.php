<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Util\MemberUtil;
use Util\MailUtil;
use Util\ConfirmMailUtil;
use Util\ViewUtil;
use Model\Dao\Users;
use Model\Dao\Confirm_mails;


// ユーザー登録
$app->get('/entry', function (Request $request, Response $response) {
    // パラメータから取得
    if (!empty($request->getQueryParams()["session"])){
        $param = $request->getQueryParams()["session"];
        $mail = ConfirmMailUtil::pop($param, "entry");
        if (empty($mail)){ // タイムアウト
            return ViewUtil::error($response, $this->view, "この認証用URLは、有効期限が切れています。");
        }
    }

    if (empty($mail)){ // 未確認ならばメールアドレス認証へ
        return confirmMailCtrl($response, $this->view, $cMailTable);
    } else{ // 確認済みならばユーザー登録またはパスワードリセットへ
        $_SESSION["brt-confirmParam"] = $param;
        $_SESSION["brt-confirmMail"] = $mail;
        return userEntryCtrl($response, $this->view, $this->db);
    }
});

$app->post('/entry', function (Request $request, Response $response) {
    $input = $request->getParsedBody();

    // メールアドレス確認
    if (!empty($input["newMail"])){
        $message = ValidationUtil::checkString("ntut-mail", $input["newMail"]);
        if ($message===""){ // メールアドレスが正常
            return sendConfirmMailCtrl($request, $response, $this->view, $input["newMail"]);
        } else{
            $message = ValidationUtil::checkString("mail", $input["newMail"]);
            if (empty($message) && (int)$_SESSION["brt-userType"]===USER_TYPE_ADMIN){
                return sendConfirmMailCtrl($request, $response, $this->view, $input["newMail"]);
            } elseif (empty($message)){
                $message = "このメールアドレスは、認証に利用できません。大学発行のメールアドレスでやり直してください。";
            }
        }
        // 認証再試行
        return confirmMailCtrl($response, $this->view, $message);
    
    // ユーザー登録
    } elseif (!empty($input["new-confirmMail"])){
        $userTable = new Users($this->db);
        
        if (empty($_SESSION["brt-confirmMail"])){
            return ViewUtil::error($response, $this->view);
        } else{
            $message = ""; // バリデーション
            if ($input["new-confirmMail"]!==$_SESSION["brt-confirmMail"]){ // メールアドレス無効
                $message = $message. "・メールアドレスが謝っています。\n";
            }
            $message = $message. ValidationUtil::checkString("katakanaLastName", $input["lastName"], "・", "\n");
            $message = $message. ValidationUtil::checkString("katakanaFirstName", $input["firstName"], "・", "\n");
            $studentNoValidate = ValidationUtil::checkString("studentNo", $input["studentNo"], "・", "\n");
            if (!empty($studentNoValidate) && !empty($input["studentNo"])){
                $message = $message. $studentNoValidate;
            }
            $message = $message. ValidationUtil::checkString("userPassword", $input["password"], "・", "\n");
            if (!empty($userTable->selectFromName($input["name"]))){
                $message = $message. "・このユーザー名は、すでに使用されています。\n";
            }
            if ($input["password"]!==$input["confirmPassword"]){
                $message = $message. "・パスワードと、パスワードの確認が一致していません。\n";
            }
            if (!empty($message)){ // 再試行
                return userEntryCtrl($response, $this->view, $this->db, mb_substr($message, 0, -1), ["mail"=> $input["new-confirmMail"], "firstName"=> $input["firstName"], "lastName"=> $input["lastName"], "studentNo"=> $input["studentNo"]]);
            }
            
            // 新規登録
            $userData = $userTable->selectFromMail($input["new-confirmMail"]);
            if (empty($userData)){
                $param = MemberUtil::makeRandomId();
                $userId = $userTable->insertUser($input["new-confirmMail"] , $input["lastName"], $input["firstName"], $input["studentNo"], password_hash($input["password"], PASSWORD_DEFAULT), $param, USER_TYPE_GENERAL);
                if ($userId!==FALSE){
                    MemberUtil::login($userId, $input["new-confirmMail"]);
                    $data = ["mail"=> $input["new-confirmMail"], "lastName"=> $input["lastName"], "firstName"=> $input["firstName"], "studentNo"=> $input["studentNo"], "url"=> $request->getUri()->getBaseUrl()."/?id=".$param];
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
            if ($input["update-confirmMail"]!==$_SESSION["brt-confirmMail"]){ // メールアドレス有効
                $message = $message. "・メールアドレスが謝っています。\n";
            }
            $message = $message. ValidationUtil::checkString("userPassword", $input["password"], "・", "\n");
            if ($input["password"]!==$input["confirmPassword"]){
                $message = $message. "・パスワードと、パスワードの確認が一致していません。\n";
            }
            if (!empty($message)){ // 再試行
                return userEntryCtrl($response, $this->view, $this->db, mb_substr($message, 0, -1), ["mail"=> $input["update-confirmMail"]]);
            }
            
            // 更新
            $userData = $userTable->selectFromMail($input["update-confirmMail"]);
            if (!empty($userData)){
                $param = MemberUtil::makeRandomId();
                if ($userTable->updatePassword_hashFromId($userData["id"], password_hash($input["password"], PASSWORD_DEFAULT), $param)==TRUE){
                    MemberUtil::login($userData["id"], $userData["mail"]);
                    $data = ["mail"=> $input["update-confirmMail"], "lastName"=> $userData["last_name"], "firstName"=> $userData["first_name"], "url"=> $request->getUri()->getBaseUrl()."/?id=".$param];
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
function confirmMailCtrl($response, $view, $message=""){
    $data = ["message"=> $message];
    return $view->render($response, 'entry/index.twig', $data);
}

// 確認メール送信
function sendConfirmMailCtrl($request, $response, $view, $mail){
    $data = ["mail"=> $mail];
    // db登録
    $param = ConfirmMailUtil::push($mail, "entry");
    
    // メール送信
    $title = "BRT メールアドレスの確認";
    $text = "BRTのご利用、ありがとうございます。\nユーザー登録、パスワードをリセットする場合は、以下のURLにアクセスしてください。\n\n".
        $request->getUri()->getBaseUrl(). "/entry?session=". $param.
        "\n\nBRT運営チーム";
    
    MailUtil::send($title, $text, "no-reply", $mail);
        return $view->render($response, 'entry/send.twig', $data);
}

// ユーザー登録フォーム
function userEntryCtrl($response, $view, $db, $message="", $previousData=["mail"=> "", "firstName"=> "", "lastName"=> ""]){
    $data=["message"=> $message, "mail"=> $previousData["mail"], "firstName"=> $previousData["firstName"], "lastName"=> $previousData["lastName"], "studentNo"=> $previousData["studentNo"]];
    
    // ユーザー登録情報確認
    $userTable = new Users($db, $message="");
    $userData = $userTable->selectFromMail($_SESSION["brt-confirmMail"]);
    if (empty($userData)){ // ユーザー登録
        return $view->render($response, 'entry/new.twig', $data);
    } else{ // パスワード更新
        return $view->render($response, 'entry/update.twig', $data);
    }
}
