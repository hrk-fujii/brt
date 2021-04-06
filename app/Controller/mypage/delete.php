<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Util\OrderUtil;
use Util\MemberUtil;
use Model\Dao\Users;



// アカウント削除入力
$app->get('/mypage/delete', function (Request $request, Response $response) {
    $data = [];

    return $this->view->render($response, 'mypage/delete.twig', $data);
});

// パスワード変更
$app->post('/mypage/delete', function (Request $request, Response $response) {
    // 削除申請内容確認
    $input = $request->getParsedBody();

    // メールアドレスを半角小文字に強制
    $input["mail"] = strtolower(mb_convert_kana($input["mail"], "a"));
    
    $message = "";
    $userTable = new Users($this->db);
    $userData = $userTable->selectFromId($_SESSION["brt-userId"]);
    if (!password_verify($input["password"], $userData["password_hash"])){
        $message = $message. "・現在のパスワードが謝っています。\n";
    }
    if ($input["mail"] !== $userData["mail"]){
        $message = $message. "・メールアドレスが謝っています。\n";
    }
    if (empty($input["confirmDelete"])){
        $message = $message. "・削除確認にチェックを入れてください。\n";
    }

    if (!empty($message)){ // エラーの時
        return $this->view->render($response, 'mypage/delete.twig', ["message"=> mb_substr($message, 0, -1)]);
    } else{
        $userTable = new Users($this->db);
        $userTable->deleteFromId($_SESSION["brt-userId"]);
        $_SESSION = [];
        return $this->view->render($response, 'mypage/deleteOk.twig', []);
    }
});

