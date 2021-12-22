# Bento Reservation Terminal(BRT)

## 概要
- 筑波技術大学学食弁当のデジタルメニュー、および予約票取り次ぎシステム


## 動作環境
- PHP7.4以上
- nginx1.19
- mariadb10.5


## 手元で動かすには
1. ここをクローン
1. 例に従い、ディレクトリを構成。
1. sample_docker-compose.ymlの設定例に従い、docker-compose.ymlを構成し、ファイルを配置
1. sample_default.confを参考にnginxを設定し、default.confを配置
1. 設定例をほとんど変更していないなら、以下のようにファイルを配置し、serviceディレクトリで以下を実行
    $docker-compose up -d
1. mariadbにsetup.sql読み込み
1. いったんコンテナをdownして、docker-compose.ymlのcommand: "composer install"をコメントアウト
1. 再び、コンテナ起動
1. localhost:10201にアクセスして動作を確認

## ディレクトリ構成
service
  www (ここ)
  nginx
    conf
      default.conf
    logs
  mariadb
  dockerfile
  docker-compose.yml


## 利用ライブラリ
- slim/twig-view
- twig/extensions
- doctorine/dbal
- bryanjhv/slim-session
- Bootstrap4
  phpmailer
