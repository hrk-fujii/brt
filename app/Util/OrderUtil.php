<?php

namespace Util;

use Doctrine\DBAL\Query\QueryBuilder;

class OrderUtil{
    static function getBentoFromTime($start, $end, $order="ASC"){
        global $container;
        
        //クエリビルダをインスタンス化
        $queryBuilder = new QueryBuilder($container->get("db"));

        //クエリ構築
        $queryBuilder
            ->select('*')
            ->from("orders INNER JOIN bento ON orders.bento_id = bento.id")
            ->andWhere("bento.end_sale_at BETWEEN ". (int)$start. " AND ". (int)$end);

        $queryBuilder->orderBy("bento.end_sale_at", $order);
        $queryBuilder->setMaxResults(100);

        //クエリ実行
        $query = $queryBuilder->execute();

        return $query->FetchALL();
    }
}
