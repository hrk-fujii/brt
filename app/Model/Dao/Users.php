<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class Users extends Dao{
    // 新規ユーザー
    static function insertUser($name, $mail, $password_hash, $param){
        return $this->insert([
            "name"=> $name,
            "mail"=> $mail,
            "password_hash"=> $password_hash,
            "url_param"=> $param,
            "last_updated_at"=> time(),
            "last_logdin_at"=> time()
        ]);
    }

    // パスワードハッシュ更新
    static function updatePassword_hashFromId($id, $password_hash, $param){
        return $this->update([
            "id"=> $id,
            "password_hash"=> $password_hash,
            "url_param"=> $param,
            "last_updated_at"=> time(),
            "last_logdin_at"=> time()
        ]);
    }

    // IDから取得
    static function selectFromId($id){
        return $this->select([
            "id"=> ["=", $id]
        ]);
    }

    // メールアドレスから取得
    static function selectFromMail($mail){
        return $this->select([
            "mail"=> $mail
        ]);
    }
}
