<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class Bento extends Dao{
    // IDから取得
    function selectFromId($id){
        return $this->select([
            "id"=> $id
        ]);
    }

    // IDからアップデート
    function updateFromId($id, $name, $discription, $orderDeadlineAt, $startSaleAt, $endSaleAt, $stock){
        return $this->update([
            "id"=> $id,
            "name"=> $name,
            "discription"=> $discription,
            "order_deadline_at"=> $orderDeadlineAt,
            "start_sale_at"=> $startSaleAt,
            "end_sale_at"=> $endSaleAt,
            "stock"=> $stock
        ]);
    }

    // アイテム追加
    function insertItem($name, $discription, $price, $orderDeadlineAt, $startSaleAt, $endSaleAt, $stock){
        return $this->insert([
            "name"=> $name,
            "discription"=> $discription,
            "price"=> $price,
            "order_deadline_at"=> $orderDeadlineAt,
            "start_sale_at"=> $startSaleAt,
            "end_sale_at"=> $endSaleAt,
            "stock"=> $stock
        ]);
    }

    // 販売日時指定抽出
    function selectFromStartSaleAt($start, $end){
        return $this->select([
            "start_sale_at"=> [$start, $end]
        ], $sort = "id", $order = "ASC", $limit = 50, $fetch_all = TRUE);
    }

    // IDから削除
    function deleteFromId($id){
        return $this->delete(["id"=> $id]);
    }
}
