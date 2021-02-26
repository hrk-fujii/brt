<?php

use Util\ViewUtil;

// Application middleware
// e.g: $app->add(new \Slim\Csrf\Guard);


$app->add(new DataBaseTransactionHandler($app->getContainer()));
$app->add(new AccessHandler($app->getContainer()));

class DataBaseTransactionHandler{

	private $container;

	public function __construct($container) {
		$this->container = $container;
	}

	//DBのトランザクションの開始・停止を行う
	public function __invoke($request, $response, $next){
		$this->container->get("db")->beginTransaction();
		$this->container->get("db")->setAutoCommit(false);
		$this->container->get("logger")->info("DB: start transaction");
		$response = $next($request, $response);
		$this->container->get("db")->commit();
		$this->container->get("logger")->info("DB: commit transaction");
		return $response;
	}
}

class AccessHandler{

	private $container;

	public function __construct($container) {
		$this->container = $container;
	}

	// アクセス制御
	public function __invoke($request, $response, $next){
		$path = explode("/",$request->getUri()->getPath());
		
		// 管理画面はadminのみ
		if (!empty($path[1]) && $path[1]==="manage" && (int)$_SESSION["brt-userType"]!==USER_TYPE_ADMIN){
			return ViewUtil::error($response, $this->container->get("view"), "このページにアクセスするには、管理者ユーザーでログインしてください。");
		}
		return $next($request, $response);
	}
}

set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline, array $errcontext){
	$GLOBALS["app"]->getContainer()->get("logger")->error("ERROR lv.".$errno." ".$errstr." at ".$errfile." line:".$errline);
	if ($GLOBALS["app"]->getContainer()->get("db")->isTransactionActive()){
		$GLOBALS["app"]->getContainer()->get("logger")->info("DB rollback");
		$GLOBALS["app"]->getContainer()->get("db")->rollBack();
	}
	return false;
});
