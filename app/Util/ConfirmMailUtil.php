<?php

namespace Util;

use Model\Dao\Confirm_mails;


class ConfirmMailUtil{
    static function push($mail, $target){
        global $container;
        $cMailTable = new Confirm_mails($container->get("db"));

        $param = MemberUtil::makeRandomId();

        // 古いデータを削除してDB登録
        if (!empty($cMailTable->selectFromMail($mail))){ // 古いのは削除
            $cMailTable->deleteFromMail($mail);
        }
        $cMailTable->insertItem($mail, $param, $target);
        return $param;
    }

    static function pop($param, $target){
        global $container;
        $cMailTable = new Confirm_mails($container->get("db"));

        // 古いデータを削除してDB取得
        $cMailTable->deleteOldItems();
        $data = $cMailTable->selectFromParam($param, $target);
        $cMailTable->deleteFromParam($param);
        
        if (empty($data)){
            return FALSE;
        } else{
            return $data["mail"];
        }
    }
}
