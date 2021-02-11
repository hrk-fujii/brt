<?php

namespace Util;

use JsonSchema;

class ValidationUtil{

	// 各種文字列の正規表現パターン
	const MAIL_PATTERN = "/^.+@.+\\.[a-zA-Z]+$/";
	const NTUT_MAIL_PATTERN = "/^.+@.*\\.tsukuba-tech\\.ac\\.jp$/";
	const USER_NAME_PATTERN = "@^[a-z0-9._\\-]{6,30}$@";
	const USER_PASSWORD_PATTERN = "@^[a-zA-Z0-9\\.,_\\-\\(\\)\\[\\]]{8,30}$@";

	const PATTERN_ARRAY = [
		"userName"=>[self::USER_NAME_PATTERN, "ユーザー名は、6～30文字で指定してください。英小文字、数字、記号（._-）が使用できます。"],
		"mail"=> [self::MAIL_PATTERN, "メールアドレスが謝っています。"],
		"userPassword"=>[self::USER_PASSWORD_PATTERN, "パスワードは、8文字以上30文字以内で指定してください。英数字、記号（.,_-()[]）が使用できます。大文字、小文字は区別されます。"],
		"ntut-mail"=>[self::NTUT_MAIL_PATTERN, "このメールアドレスは利用できません。大学発行のアドレスをご利用ください。"]
	];

	// 文字列を検査してエラーメッセージか""を返す
	static function checkString($key, $word, $prefix="", $sufix=""){
		if (preg_match(self::PATTERN_ARRAY[$key][0], $word)){
			return "";
		} else{
			return $prefix. self::PATTERN_ARRAY[$key][1]. $sufix;
		}
	}
}