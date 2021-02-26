<?php

namespace Util;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailUtil{
	// 定数
	const FROM_TEXT = [
		"no-reply"=> "BRT受付システム"
	];
	
	// メール一斉送信（タイトル、本文、差出アドレス、宛先配列）
	static function sends($title, $body, $from, $bccArray){
		try{
			$mail = new PHPMailer();
			$mail->isSMTP();
			$mail->SMTPAuth = TRUE;
			$mail->Host = $_ENV["MAIL_HOST"];
			$mail->Username = $from. $_ENV["MAIL_USERBASE"];
			$mail->Password = $_ENV["MAIL_PASSWORD"];
			$mail->SMTPSecure = $_ENV["MAIL_SECURE"];
			$mail->Port = $_ENV["MAIL_PORT"];

			// メール内容
			$mail->CharSet = "UTF-8";
			$mail->Encoding = "base64";
			$mail->setFrom($from. $_ENV["MAIL_ADDRESSBASE"], self::FROM_TEXT["$from"]);
			$mail->addAddress($from. $_ENV["MAIL_ADDRESSBASE"]);
			foreach ($bccArray as $bcc){
				$mail->addBCC($bcc);
			}
			$mail->Subject = $title;
			$mail->Body  = $body;

			$mail->send();
			return TRUE;
		} catch (Exception $e){
			error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
			return FALSE;
		}
	}

	// メール送信（タイトル、本文、差出アドレス、宛先）
	static function send($title, $body, $from, $to){
		try{
			$mail = new PHPMailer();
			$mail->isSMTP();
			$mail->SMTPAuth = TRUE;
			$mail->Host = $_ENV["MAIL_HOST"];
			$mail->Username = $from. $_ENV["MAIL_USERBASE"];
			$mail->Password = $_ENV["MAIL_PASSWORD"];
			$mail->SMTPSecure = $_ENV["MAIL_SECURE"];
			$mail->Port = $_ENV["MAIL_PORT"];

			// メール内容
			$mail->CharSet = "UTF-8";
			$mail->Encoding = "base64";
			$mail->setFrom($from. $_ENV["MAIL_ADDRESSBASE"], self::FROM_TEXT["$from"]);
			$mail->addAddress($to);
			$mail->Subject = $title;
			$mail->Body  = $body;

			$mail->send();
			return TRUE;
		} catch (Exception $e){
			error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
			return FALSE;
		}
	}
}
