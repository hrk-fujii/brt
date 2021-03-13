<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Users;
use Util\ViewUtil;
use Util\ValidationUtil;


// 管理メニュー表示
$app->get('/manage/edituser', function (Request $request, Response $response) {
    return selectEditUserCtrl($response, $this->view, $this->db);
});


$app->post('/manage/edituser', function (Request $request, Response $response) {
    $input = $request->getParsedBody();
    $userTable = new Users($this->db);
    
    // ユーザー情報編集
    if (!empty($input["editTarget"])){
        
        $message = ""; // バリデーション
        $message = $message. ValidationUtil::checkString("katakanaLastName", $input["lastName"], "・", "\n");
        $message = $message. ValidationUtil::checkString("katakanaFirstName", $input["firstName"], "・", "\n");
        $studentNoValidate = ValidationUtil::checkString("studentNo", $input["studentNo"], "・", "\n");
        if (!empty($studentNoValidate) && !empty($input["studentNo"])){
            $message = $message. $studentNoValidate;
        }
        if ($input["userType"]=="general"){
            $userType = USER_TYPE_GENERAL;
        } elseif ($input["userType"]=="admin"){
            $userType = USER_TYPE_ADMIN;
        } elseif ($input["userType"]=="disable"){
            $userType = USER_TYPE_DISABLE;
        } else{
            $message = $message. "・ユーザー種別を選択してください。\n";
        }
        if (!empty($message)){ // 再試行
            return userEditCtrl($response, $this->view, $this->db, mb_substr($message, 0, -1), ["firstName"=> $input["firstName"], "lastName"=> $input["lastName"], "studentNo"=> $input["studentNo"]], $input["editTarget"]);
        }
        
        // 編集反映
        $userData = $userTable->selectFromId($input["editTarget"]);
        if (!empty($userData)){
            if (empty($input["studentNo"])){
                $userTable->updateInfoFromId($input["editTarget"], $input["firstName"], $input["lastName"], NULL, $userType);
            } else{
                $userTable->updateInfoFromId($input["editTarget"], $input["firstName"], $input["lastName"], $input["studentNo"], $userType);
            }
            return $this->view->render($response, 'manage/editUserOk.twig', []);
        } else{
            return ViewUtil::error($response, $this->view);
        }

    } elseif (!empty($input["goToEdit"])){
        if (empty($input["editTargetRadio"])){
            return selectEditUserCtrl($response, $this->view, $this->db, "ユーザーを選択してください。");
        }
        $userData = $userTable->selectFromId($input["editTargetRadio"]);
        if (empty($userData)){
            return ViewUtil::error($response, $this->view);
        }
        return userEditCtrl($response, $this->view, $this->db, "", ["firstName"=> $userData["first_name"], "lastName"=> $userData["last_name"], "studentNo"=> $userData["student_no"]], $input["editTargetRadio"]);
    }
});

// ユーザー選択
function selectEditUserCtrl($response, $view, $db, $message=""){
    $userTable = new Users($db);
    $userArray = $userTable->selectAll("last_name");
    return $view->render($response, 'manage/selectEditUser.twig', ["userArray"=> $userArray, "message"=> $message]);
}

// ユーザー情報編集
function userEditCtrl($response, $view, $db, $message="", $previousData, $editTargetId){
    $data=["message"=> $message, "firstName"=> $previousData["firstName"], "lastName"=> $previousData["lastName"], "studentNo"=> $previousData["studentNo"], "editTarget"=> $editTargetId];

    return $view->render($response, 'manage/editUser.twig', $data);
}
