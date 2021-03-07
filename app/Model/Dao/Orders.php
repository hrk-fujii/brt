<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class Orders extends Dao{
    // idから取得
    function selectFromId($id){
        return $this->select([
            "id"=> $id
        ]);
    }

    // IDから削除
    function deleteFromId($id){
        return $this->delete([
            "id"=> $id
        ]);
    }

    // 追加
    function insertItem($bentoId, $quantity, $userId){
        return $this->insert([
            "bento_id"=> $bentoId,
            "quantity"=> $quantity,
            "users_id"=> $userId,
            "ordered_at"=> time()
        ]);
    }

    // 弁当IDから抽出
    function selectFromBentoId($bentoId){
        return $this->select([
            "bento_id"=> $bentoId
        ], $sort = "id", $order = "ASC", $limit = 2100000000, $fetch_all = TRUE);
    }
}
