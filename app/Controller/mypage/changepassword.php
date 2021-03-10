<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Util\OrderUtil;
use Util\MemberUtil;
use Util\ViewUtil;
use Model\Dao\Users;



// パスワード変更入力
$app->get('/mypage/changepassword', function (Request $request, Response $response) {
    $_SESSION["brt-changePasswordReady"] = TRUE;
    $data = [];

    return $this->view->render($response, 'mypage/changepassword.twig', $data);
});

// パスワード変更
$app->post('/mypage/changepassword', function (Request $request, Response $response) {
    // パスワード変更内容確認へ
    $input = $request->getParsedBody();

    $message = "";
    $userTable = new Users($this->db);
    $userData = $userTable->selectFromId($_SESSION["brt-userId"]);
    if (!password_verify($input["oldPassword"], $userData["password_hash"])){
        $message = $message. "・現在のパスワードが謝っています。\n";
    }
    $message = $message. ValidationUtil::checkString("userPassword", $input["password"], "・", "\n");
    if ($input["password"]!==$input["confirmPassword"]){
        $message = $message. "・パスワードと、パスワードの確認が一致していません。\n";
    }
    
    if (!empty($message)){ // エラーの時
        return $this->view->render($response, 'mypage/changepassword.twig', ["message"=> mb_substr($message, 0, -1)]);
    } else{
        // パスワード変更のリロード対策
        if (empty($_SESSION["brt-changePasswordReady"])){
            return ViewUtil::error($response, $this->view, "無効なアクセスが検出されたため、サービスの継続ができません。恐れ入りますが、トップページに戻り、最初からやり直してください。");
        }
        $_SESSION["brt-changePasswordReady"] = FALSE;
        $param = MemberUtil::makeRandomId();
        $userTable = new Users($this->db);
        $userTable->updatePassword_hashFromId($_SESSION["brt-userId"], password_hash($input["password"], PASSWORD_DEFAULT), $param);
        $data = ["url"=> $request->getUri()->getBaseUrl()."/?id=".$param];
        return $this->view->render($response, 'mypage/changepwConfirm.twig', $data);
    }
});

