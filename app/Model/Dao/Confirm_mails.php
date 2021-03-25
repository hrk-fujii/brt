<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class Confirm_mails extends Dao{
    // 古いデータを削除
    function deleteOldItems($sec=3600){
        $this->delete([
            "set_at"=> ["<", time() - $sec]
        ]);
    }

    // パラメータから取得
    function selectFromParam($param, $target=NULL){
        if (empty($param)){return FALSE;}
        if ($target===NULL){
            return $this->select([
                "session_id"=> $param
            ]);
        } else{
            return $this->select([
                "target"=> $target,
                "session_id"=> $param
            ]);
        }
    } 

    // メールアドレスから取得
    function selectFromMail($mail){
        if (empty($mail)){return FALSE;}
        return $this->select([
            "mail"=> $mail
        ]);
    } 

    // パラメータから削除
    function deleteFromParam($param){
        if (empty($param)){return FALSE;}
        return $this->delete([
            "session_id"=> $param
        ]);
    }

    // メールアドレスから削除
    function deleteFromMail($mail){
        if (empty($mail)){return FALSE;}
        return $this->delete([
            "mail"=> $mail
        ]);
    }

    // 新規
    function insertItem($mail, $param, $target){
        return $this->insert([
            "mail"=> $mail,
            "target"=> $target,
            "session_id"=> $param,
            "set_at"=> time()
        ]);
    }
}
