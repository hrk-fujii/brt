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

        return $query->FetchALL();
    }

    // 予約を回収
    static function pullOrderFromDeadlineAt($start, $end){
        global $container;
        
        //クエリビルダをインスタンス化
        $queryBuilder = new QueryBuilder($container->get("db"));

        //クエリ構築
        $queryBuilder
            ->select('*')
            ->from("bento INNER JOIN orders ON orders.bento_id = bento.id INNER JOIN users ON orders.users_id = users.id")
            ->andWhere("bento.order_deadline_at BETWEEN ". (int)$start. " AND ". (int)$end)
            ->andWhere(BENTO_ORDER_CLOSED. " != (bento.flag & ".BENTO_ORDER_CLOSED . ")");

        $queryBuilder->orderBy("bento.end_sale_at", $order);
        $queryBuilder->setMaxResults(100);

        //クエリ実行
        $query = $queryBuilder->execute();

        $ret = $query->FetchALL(PDO::FETCH_NAMED);
        if (empty($ret)){
            return FALSE;
        } else{
            $orderTable = new Orders($container->get("db"));
            foreach ($ret as $r){
                $orderTable->update([
                    "id"=> $r["id"][1],
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
        
        return $ret;
    }
    
    // 予約取次ログ追加
    static function addHistory($studentNo, $firstName, $lastName, $bentoName, $quantity, $orderDeadlineAt, $startSaleAt){
        global $container;    
        $orderHistoryTable = new Order_history($container->get("db"));
        return $orderHistoryTable->insert([
            "student_no"=> $studentNo,
            "last_name"=> $lastName,
            "first_name"=> $firstName,
            "bento_name"=> $bentoName,
            "quantity"=> $quantity,
            "order_deadline_at"=> $orderDeadlineAt,
            "start_sale_at"=> $startSaleAt
        ]);
    }
}
