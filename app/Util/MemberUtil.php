<?php
namespace Util;

use Model\Dao\Users;

class MemberUtil{
	// ログイン処理
	static function login($id, $mail){
		global $container;
		$userTable = new Users($container->get("db"));
		$userData = $userTable->selectFromId($id);
		if (!empty($userData) && $userData["mail"]===$mail){
			$_SESSION["brt-userId"] = $id;
			$_SESSION["brt-userMail"] = $mail;
			$userTable->loginFromId($id);
			return TRUE;
		} else{
			return FALSE;
		}
	}

	// ランダムID生成
	static function makeRandomId(){
		return hash('sha256', random_int(PHP_INT_MIN, PHP_INT_MAX));
	}
}
