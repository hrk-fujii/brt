<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\MailUtil;
use Model\Dao\Users;
use Model\Dao\Bento;



// トップページ表示
$app->get('/', function (Request $request, Response $response) {
    // 多重予約ロックを解除
    $_SESSION["brt-orderReady"] = TRUE;
    
    $bentoTable = new Bento($this->db);
    // 日付の設定。今日販売の弁当がなければ最短販売日の弁当
    $saleDate = strtotime(date("Y-m-d", time()));
    $bentoArray = NULL;
    $data["day"] = NULL;
    for ($i=0; $i<=9; $i++){
        $bentoArray = $bentoTable->selectFromStartSaleAt($saleDate, $saleDate + 60 * 60 * 24 - 1);
        if (!empty($bentoArray) && count($bentoArray)!=0){
            if ($i===0){
                $data["day"] = "本日";
            }elseif ($i===1){
                $data["day"] = "明日";
            }else{
                $data["day"] = date("n月j日", $saleDate);
            }
            break;
        } else{
            $saleDate = $saleDate + 60 * 60 * 24;
        }
    }

    $data["bentoArray"] = $bentoArray;
    $data["saleDateArray"] = [];
    foreach ($data["bentoArray"] as &$b){
        $b["startSaleStr"] = date("H:i", $b["start_sale_at"]);
        $b["saleLengthMinuteOnly"] = (int)(($b["end_sale_at"]-$b["start_sale_at"])/60);
        if ($b["flag"]&BENTO_ORDER_CLOSED===BENTO_ORDER_CLOSED){
            $b["orderDeadlineStr"] = NULL; // 予約できない
        } else{
            $b["orderDeadlineStr"] = date("j日", $b["order_deadline_at"]). "(". DAY_JP[date("w", $b["order_deadline_at"])]. ")". date("H:i", $b["order_deadline_at"]);
        }
    }
    $data["saleDateArray"] = [];
    for ($count = 0; $count <= 10; $count++){
        $val = strtotime(date("Y-m-d", time() + (60*60*24*$count)));
        array_push($data["saleDateArray"], ["unix"=> $val, "str"=> date("n月j日", $val). DAY_JP[date("w", $val)]. "曜日"]);
    }
    return $this->view->render($response, 'top/index.twig', $data);
});
