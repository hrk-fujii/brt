<?php
namespace Util;

use Model\Dao\Users;

class MemberUtil{
	// ログイン処理
	static function login($id, $name){
		global $container;
		$userTable = new Users($container->get("db"));
		$userData = $userTable->selectFromId($id);
		if (!empty($userData) && $userData["name"]===$name){
			$_SESSION["brt-userId"] = $id;
			$_SESSION["brt-userName"] = $name;
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
