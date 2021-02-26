<?php

namespace Util;


class ViewUtil{
    // エラー表示
    static function error($response, $view, $message="エラーが発生しました。恐れ入りますが、操作をやり直してください。\nブラウザの「戻る」機能を利用されますと、正常に処理できない場合があります。"){
        $data = ["message"=> $message];
        return $view->render($response, 'util/error.twig', $data);
    }
}
