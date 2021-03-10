<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\MailUtil;
use Util\ViewUtil;
use Model\Dao\Users;



// フォーム表示
$app->get('/manage/sendmail', function (Request $request, Response $response) {
    $_SESSION["brt-confirmSendMail"] = [];
    return sendMailCtrl($response, $this->view, $this->db);
});


$app->post('/manage/sendmail', function (Request $request, Response $response) {
    if (empty($_SESSION["brt-sendMailReady"])){
        return ViewUtil::error($response, $this->view, "無効なアクセスが検出されたため、サービスの継続ができません。恐れ入りますが、トップページに戻り、最初からやり直してください。");
    }
    
    $input = $request->getParsedBody();

    if (!empty($input["goToPreview"])){ // プレビューへ
        $_SESSION["brt-confirmSendMail"] = $input;
        return sendMailPreviewCtrl($response, $this->view, $this->db);

    } elseif (!empty($input["send"]) && !empty($_SESSION["brt-confirmSendMail"]["mailTitle"]) && !empty($_SESSION["brt-confirmSendMail"]["mailBody"])){
        return sendMailProcess($response, $this->view, $this->db);

    } else{
        return ViewUtil::error($request, $this->view, "メールを送信できません。もう一度やり直してください。");
    }


});


// 一斉メール送信フォーム
function sendMailCtrl($response, $view, $db, $message=""){
    $_SESSION["brt-sendMailReady"] = TRUE;
    $data["message"] = $message;
    if (!empty($_SESSION["brt-confirmSendMail"]["mailTitle"])){
        $data["mailTitle"] = $_SESSION["brt-confirmSendMail"]["mailTitle"];
    }
    if (!empty($_SESSION["brt-confirmSendMail"]["mailBody"])){
        $data["mailBody"] = $_SESSION["brt-confirmSendMail"]["mailBody"];
    }
    
    return $view->render($response, 'manage/sendMail.twig', $data);
}

// メールプレビュー
function sendMailPreviewCtrl($response, $view, $db){
    $userTable = new Users($db);
    $userData = $userTable->selectFromId($_SESSION["brt-userId"]);
    MailUtil::send($_SESSION["brt-confirmSendMail"]["mailTitle"]. "<プレビュー>", $_SESSION["brt-confirmSendMail"]["mailBody"], "noreply", $userData["mail"]);
    $data = ["mailTitle"=> $_SESSION["brt-confirmSendMail"]["mailTitle"], "mailBody"=> $_SESSION["brt-confirmSendMail"]["mailBody"]];
    return $view->render($response, 'manage/sendMailPreview.twig', $data);
}

// メール送信
function sendMailProcess($response, $view, $db){
    $_SESSION["brt-sendMailReady"] = FALSE;
    $userTable = new Users($db);
    $userData = $userTable->selectAll();
    $mailArray = [];
    foreach ($userData as $u){
        array_push($mailArray, $u["mail"]);
    }
    MailUtil::sends($_SESSION["brt-confirmSendMail"]["mailTitle"], $_SESSION["brt-confirmSendMail"]["mailBody"], "noreply", $mailArray);
    return $view->render($response, 'manage/sendMailOk.twig', []);
}
