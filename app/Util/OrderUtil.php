<?php

namespace Util;

use Doctrine\DBAL\Query\QueryBuilder;

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
}
