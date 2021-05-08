<?php

namespace Util;

use Doctrine\DBAL\Query\QueryBuilder;
use PDO;
use Model\Dao\Orders;
use Model\Dao\Order_history;

class OrderUtil{
    static function getBentoFromTime($start, $end, $order="ASC"){
        // ログインされていなければFALSE
        if (empty($_SESSION["brt-userId"])){
            return FALSE;
        }

        global $container;
        
        //クエリビルダをインスタンス化
        $queryBuilder = new QueryBuilder($container->get("db"));

        //クエリ構築
        $queryBuilder
            ->select('*')
            ->from("bento INNER JOIN orders ON orders.bento_id = bento.id")
            ->andWhere("bento.end_sale_at BETWEEN ". (int)$start. " AND ". (int)$end)
            ->andWhere("orders.users_id = ". (int)$_SESSION["brt-userId"]);

        $queryBuilder->orderBy("bento.end_sale_at", $order);
        $queryBuilder->setMaxResults(100);

        //クエリ実行
        $query = $queryBuilder->execute();

        return $query->FetchALL(PDO::FETCH_NAMED);
    }

    // 予約を回収
    static function pullOrderFromDeadlineAt($start, $end){
        global $container;
        
        //クエリビルダをインスタンス化
        $queryBuilder = new QueryBuilder($container->get("db"));
        //クエリ構築
        $queryBuilder
            ->select('users.first_name, users.last_name, users.student_no, users.mail, orders.id as orders_id, bento.id as bento_id, SUM(orders.quantity) as quantity, orders.flag as flag')
            ->groupBy("bento.id, users.id, orders.flag")
            ->from("bento INNER JOIN orders ON orders.bento_id = bento.id INNER JOIN users ON orders.users_id = users.id")
            ->andWhere("bento.order_deadline_at BETWEEN ". (int)$start. " AND ". (int)$end)
            ->andWhere(BENTO_ORDER_CLOSED. " != (bento.flag & ".BENTO_ORDER_CLOSED . ")");
        $queryBuilder->orderBy("bento.end_sale_at", "ASC");
        $queryBuilder->setMaxResults(2100000000);
        //クエリ実行
        $query = $queryBuilder->execute();
        $orderData = $query->FetchALL();

        // 弁当カウント
        //クエリビルダをインスタンス化
        $queryBuilder = new QueryBuilder($container->get("db"));
        //クエリ構築
        $queryBuilder
            ->select("bento.id as bento_id, bento.name, SUM(orders.quantity) as quantity, bento.order_deadline_at, bento.start_sale_at")
            ->groupBy("bento.id")
            ->from("bento LEFT OUTER JOIN orders ON orders.bento_id = bento.id")
            ->andWhere("bento.order_deadline_at BETWEEN ". (int)$start. " AND ". (int)$end)
            ->andWhere(BENTO_ORDER_CLOSED. " != (bento.flag & ".BENTO_ORDER_CLOSED . ")");
        $queryBuilder->orderBy("bento.end_sale_at", "ASC");
        $queryBuilder->setMaxResults(2100000000);
        //クエリ実行
        $query = $queryBuilder->execute();
        $bentoData = $query->FetchALL();
        if (empty($bentoData)){
            return FALSE;
        }
        if (empty($orderData)){
            $orderTable = new Orders($container->get("db"));
            foreach ($orderData as $r){
                $orderTable->update([
                    "id"=> $r["orders_id"],
                    "flag"=> $r["flag"] | BENTO_ORDER_CLOSED
                ]);
            }
        }
        // 弁当の締め切り
        $queryBuilder = new QueryBuilder($container->get("db"));
        $queryBuilder
            ->update('bento')
            ->set("flag", "flag | ". BENTO_ORDER_CLOSED)
            ->where("order_deadline_at <= ". (int)$end);
            $query = $queryBuilder->execute();
        
        // 結果の生成
        foreach ($bentoData as $b){
            $ret[$b["bento_id"]] = ["name"=> $b["name"], "quantity"=> (int)$b["quantity"], "orderDeadlineAtStr"=> date("Y-m-d,H:i", $b["order_deadline_at"]), "startSaleAtStr"=> date("Y-m-d,H:i", $b["start_sale_at"]), "order"=> []];
        }
        foreach ($orderData as $o){
            array_push($ret[$o["bento_id"]]["order"], ["name"=> $o["last_name"]. " ". $o["first_name"], "mail"=> $o["mail"], "studentNo"=> $o["student_no"], "quantity"=> $o["quantity"], "flag"=> $o["flag"]]);
        }
        
        return $ret;
    }
    
    // 予約取次ログ追加
    static function addHistory($logStr){
        global $container;    
        $orderHistoryTable = new Order_history($container->get("db"));
        return $orderHistoryTable->insert([
            "log"=> $logStr,
            "time"=> time()
        ]);
    }
}
