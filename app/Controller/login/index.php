<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Users;
use Util\MemberUtil;

// ログイン
$app->get("/login", function (request $request, response $response){
    // とりあえずセッション没収
    $_SESSION = [];

    // ログインフォームを表示
    return showLoginForm($this->view, $response);
});

// ログアウト
$app->get("/logout", function (request $request, response $response){
    // セッション変数没収
    $_SESSION = [];

    // ログインが完了したらリダイレクト
    return $response->withRedirect($request->getUri()->getBasePath()."/");
});

// ログインPOST
$app->post("/login", function (request $request, response $response){
    $inputData = $request->getParsedBody();
    
    // ログインを試行
    // エラーがあれば再入力、なけばログイン
    if (!loginFromInput($inputData["mail"], $inputData["password"], $this->db)){
        return showLoginForm($this->view, $response, $inputData, "メールアドレス、またはパスワードが謝っています。");
    }
    
    // ログインが完了したらリダイレクト
    return $response->withRedirect($request->getUri()->getBasePath()."/");
});

// ログインフォームを表示
// 再表示時は、前回入力データ、エラーメッセージを元にフォームを構成
function showLoginForm($view, $response, $previousData="", $errorMessage=""){
    if (empty($previousData)){
        $data = [];
    } else{
        $data = $previousData;
    }
    
    $data["message"] = $errorMessage;

    // Render view
    return $view->render($response, 'login/index.twig', $data);
}

// ログイン
function loginFromInput($mail, $password, $db){
    if (empty($mail)){
        return FALSE;
    }
    
    // ユーザー情報確認
    $userTable = new Users($db);
    $userData = $userTable->selectFromMail($mail);

    if (empty($userData)){
        return FALSE;
    }
    if (!password_verify($password, $userData["password_hash"])){
        return FALSE;
    }

    MemberUtil::login($userData["id"], $userData["mail"]);

    return TRUE;
}

