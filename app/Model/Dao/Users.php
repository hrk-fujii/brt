<?php

namespace Model\Dao;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;

class Users extends Dao{
    // 新規ユーザー
    function insertUser($mail, $lastName, $firstName, $studentNo, $password_hash, $param, $type){
        return $this->insert([
            "last_name"=> $lastName,
            "first_name"=> $firstName,
            "mail"=> $mail,
            "student_no"=> $studentNo,
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

    // ログイン用URL更新
    function updateUrlParamFromId($id, $param){
        return $this->update([
            "id"=> $id,
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

    // URLから取得
    function selectFromParam($param){
        return $this->select([
            "url_param"=> $param
        ]);
    }

    // メールアドレスから取得
    function selectFromMail($mail){
        return $this->select([
            "mail"=> $mail
        ]);
    }
}
