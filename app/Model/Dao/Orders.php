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

    // 追加
    function insertItem($bentoId, $quantity, $userId){
        return $this->insert([
            "bento_id"=> $bentoId,
            "quantity"=> $quantity,
            "users_id"=> $userId,
            "ordered_at"=> time()
        ]);
    }
}
