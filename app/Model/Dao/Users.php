<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class Users extends Dao{
    // 新規ユーザー
    function insertUser($name, $mail, $password_hash, $param, $type){
        return $this->insert([
            "name"=> $name,
            "mail"=> $mail,
            "password_hash"=> $password_hash,
            "url_param"=> $param,
            "type"=> $type,
            "last_updated_at"=> time(),
            "last_logdin_at"=> time()
        ]);
    }

    // パスワードハッシュ更新
    function updatePassword_hashFromId($id, $password_hash, $param){
        return $this->update([
            "id"=> $id,
            "password_hash"=> $password_hash,
            "url_param"=> $param,
            "last_updated_at"=> time(),
            "last_logdin_at"=> time()
        ]);
    }

    // ログイン日時更新
    function loginFromId($id){
        return $this->update([
            "id"=> $id,
            "last_logdin_at"=> time()
        ]);
    }

    // IDから取得
    function selectFromId($id){
        return $this->select([
            "id"=> ["=", $id]
        ]);
    }

    // ユーザー名から取得
    function selectFromName($name){
        return $this->select([
            "name"=> ["=", $name]
        ]);
    }

    // メールアドレスから取得
    function selectFromMail($mail){
        return $this->select([
            "mail"=> $mail
        ]);
    }
}
