<?php

function setSessionFromEditMenu($input){
    $message = "";
    $startSaleAt = $input["startSaleDate"] + $input["startSaleHour"] * 60 * 60 + $data["startSaleMinute"] * 60;
    if ($startSaleAt <= time()){
        $message = $message. "・販売開始日時は、過去の日時を指定できません。\n";
    }
    $input["startSaleAt"] = $startSaleAt;
    $input["startSaleStr"] = date("n月j日", $startSaleAt). DAY_JP[date("w", $startSaleAt)]. "曜日". date("H時i分", $startSaleAt);
    $orderDeadlineAt = $input["startSaleDate"] - $input["orderDeadlineDate"] * 60 * 60 * 24 + $input["orderDeadlineHour"] * 60 * 60 + $input["orderDeadlineMinute"] * 60;
    if ($orderDeadlineAt > $startSaleAt){
        $message = $message. "・注文締切日時は、販売開始日時よりも前でなければなりません。\n";
    }
    if ($orderDeadlineAt <= time()){
        $message = $message. "・注文締切日時は、現在日時よりも後でなければなりません。\n";
    }
    $input["orderDeadlineAt"] = $orderDeadlineAt;
    $input["orderDeadlineStr"] = date("n月j日", $orderDeadlineAt). DAY_JP[date("w", $orderDeadlineAt)]. "曜日". date("H時i分", $orderDeadlineAt);
    if ($input["saleLengthHour"] < 0 or $input["saleLengthMinute"] < 0){
        $message = $message. "・販売期間の設定が不正です。\n";
    }
    $input["endSaleAt"] = $startSaleAt + $input["saleLengthHour"] * 60 * 60 + $input["saleLengthMinute"] * 60;
    $isArray = FALSE;
    foreach ($input["name"] as $k=> $v){
        if ($input["name"][$k]==="" && (isset($input["discription"][$k]) && $input["discription"][$k]!=="")){
            $message = $message. "・説明のみでは、メニューの登録ができません。\n";
            return substr($message, 0, -1);
        } elseif (mb_strlen($input["name"][$k], "utf-8") > 60){
            $message = $message. "・メニューには、最大60文字までです。\n";
            return substr($message, 0, -1);
        } elseif (isset($input["name"][$k]) && $input["name"][$k]!==""){
            $isArray = TRUE;
        }
    }
    if (!$isArray){
        $message = $message. "・メニューを設定してください。\n";
    }
    if ($message===""){
        $_SESSION["brt-confirmEditMenu"] = $input;
    }
    return substr($message, 0, -1);
}
