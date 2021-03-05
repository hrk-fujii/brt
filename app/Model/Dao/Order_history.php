<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class Order_history extends Dao{
    // IDから取得
    function selectFromId($id){
        return $this->select([
            "id"=> $id
        ]);
    }
}
