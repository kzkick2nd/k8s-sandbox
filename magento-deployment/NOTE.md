## 手順
- Docker イメージを push
- CloudSQL を起動
- GKE を起動
    - 環境変数設定
    - magento-app deploy
    - magento-cron deploy
    - nfs deploy

## 予定作業
nfs, smtp, elasticsearch 連携が出来て1ステップ
dokcer コンテナ再構築で2ステップ
deployment manager 構成して3ステップ
github からビルドと監視ができて4ステップ

## Docker イメージ作業
TODO Nginx 化
    - ジョブ化するならサーバーはオフにした方が適切
    - Nginx ポッドとの通信経路どうなる？
        - socket 共有をよく見るが、ベターな方法なのか再度調査必要

TODO Docker イメージのログ出力
    - STDOUTへ動かしたいなら、既存 logger を上書きする必要ある
    - https://devdocs.magento.com/guides/v2.3/config-guide/log/log-intro.html

TODO bin/setup に頼らない初期化に
    - 基礎イメージビルド
    - composer コマンドを実行
    - ローカルへマウント
    - ※ デザイン編集、モジュール追加のワークフローを一回やって差分をみる
    - commit push & pull 起動に対応

TODO イメージ最小化 1GB スタート
    - マルチビルド化
    - && 削除

## GKE 設定追加
TODO env.php の扱い
    - 環境変数からの扱いにするか、ファイルをConfigMapで持つか
    - dev 環境
        - docker compose の変数読み込みで対応できる
    - gke 環境
        - getenv() 埋め込み
        - pod 環境変数 埋め込み
        - ConfigMap > 環境変数化
    - 初期化処理やワークフローによればenv.phpを残す必要はなくなる

TODO Assets 共有ディスク => pub/media/upload
    - 通常pvcディスク NG = 共有できない・中身消える
        - recalim policy = delete <= 変更するためには pv 作る必要あり
    - Google Cloud Filestore => 高すぎNG
    - 共有ディスク => NFSイメージ使うのが良さそう volume_nfs:0.8

TODO Cron
    - CronJobでjobのpodを設定できる
    - CronJob専用のイメージを用意した方が良い？
        - とてもめんどくさい
        - 起動時に初期設定が必須になる
            - DBの共有
            - 環境変数
        - var/log に書き込もうとしてパーミッションエラーを起こしている
            - cron 処理の前段にログファイルの存在を必要としているらしい。キツイな。
        - cron実行にメモリ不足
            - [2019-07-29 09:06:52] setup-cron.ERROR: Your current PHP memory limit is 128M. Magento 2 requires it to be set to 756M or more. As a user with root privileges, edit your php.ini file to increase memory_limit. (The command php --ini tells you where it is located.) After that, restart your web server and try again. [] []
            - php memory limit 756M に変更が必要

TODO メール
    - Mailgun/Sendgrid via SMTP を追加する

TODO セキュリティ関連 確認
    TODO IAM
    TODO HTTPS
    TODO ネットワーク, サブネット指定
    DONE コンテナ非公開 = Container Registry 非公開設定でもプロジェクト内は利用 OK
    TODO システムファイル書き込み不可設定（パーミッションとACL）
    TODO fastly or cloudflare WAF
    TODO Google Cloud Armor WAF?

TODO ドメイン紐付け手順の確認

TODO 構成管理自動化 Cloud Deployment Manager
TODO 開発環境 Docker 用 /app/etc/env.php のサンプルを用意（消滅時の復旧方法も必要）
TODO GKE イメージ起動時になにかコマンド実行できるのか？
TODO node-pool の node にどう分散するの？

## 運用系（ある程度行ったら一回回してみる）
TODO 初期化と継続開発
TODO デザイン開発
TODO モジュール開発
TODO Magento アップデート運用
TODO アプリのデプロイ運用フロー

PEND init したのに database 空っぽ => 初期化コマンド必要？ => 二度目大丈夫だった謎。

---

DONE CloudSQL の プロキシー接続
    - 環境変数で env.php を用意するのが先 OK
    - プロキシ化ではなく閉じたVPC peering にプライベート接続でOK
DONE /app/etc/ 対応
    永続化 => 変化に耐えない
    環境変数化 => ベターではないがとりあえずの手段
    config map => ファイル読み込み版。試してみる
        - config map --from-file 作成 OK
            - kubectl create configmap magento-env --from-file=
            - kubectl delete configmap magento-env
        - deployments.yaml にconfigmap 作成（ファイル直指定できた） OK
        - ログインして読み込み確認 OK
        - subPath だと更新されない★
            - "Note: A container using a ConfigMap as a subPath volume will not receive ConfigMap updates."
        - 環境変数で渡すのがベスト => Magento でこれを行う方法を考える
        - credentials あるので secret 使う方が良い
            - kubectl create secret generic magento-env --from-file=
            - すごい扱いにくい。やはり ConfigMap が良い
DONE セッション OK、キャッシュ OK => redisを立ててenv.php => クラスターを組む場合どうしよう
DONE バッチ運用 magento コマンド運用
    - 実行不能 No information is available: the Magento application is not installed. なぜ？
        - env.php が入ってなかった
    - schema の追加ができればOK
        - SSH してコマンド直うち
        - $ bin/magento setup:db-schema:upgrade
        - $ bin/magento setup:db-data:upgrade
    - サイト基本設定
        - 最初のデータを入れるのだけコマンド必要？
            - ログインしないと打ち込めず
        - $ bin/magento setup:store-config:set --base-url=
                --language=ja_JP \
                --currency=JPY \
                --timezone=Asia/Tokyo \
                --use-rewrites=1
        - bin/magento admin:user:create --admin-user=admin --admin-password=dnut8hic --admin-email=aruga.kazuki@gmail.com --admin-firstname=kazuki --admin-lastname=aruga
        - $ bin/magento cache:flush
DONE CloudSQL 置き換え
DONE /app/etc/env.php の扱い => .dockerignoreで除外
DONE Resource Requests の記載（magento, mysql）
DONE Dockerfile で www-data にユーザー変更

記録付き更新
kubectl apply -f magento.yaml --record

credentials 設定（入力文字列から）
kubectl create secret generic mysql --from-literal=password=YOUR_PASSWORD
https://buddy.works/guides/magento-kubernetes

credentials 読み込み
kubectl get secret credentials -o yaml
https://cloud.google.com/kubernetes-engine/docs/concepts/secret?hl=ja

kubectl 操作 gke 変更
gcloud container clusters get-credentials $CLUSTER_NAME --zone=$ZONE_OF_CLUSTER
https://qiita.com/tkow/items/e256c0a50c4b2c832c52

shell ログイン
kubectl exec -it shell-demo -- /bin/bash
https://kubernetes.io/docs/tasks/debug-application-cluster/get-shell-running-container/

cloudsql
[Google Kubernetes Engine から接続する  |  Cloud SQL for MySQL  |  Google Cloud](https://cloud.google.com/sql/docs/mysql/connect-kubernetes-engine?hl=ja)

作成、プライベートIP、service networking api

アカウントunlock
./bin/magento admin:user:unlock admin

docker run -d IMAGE_NAME
docker rm CONTAINER_NAME
docker rmi REPO:TAG => タグ指定削除
asia.gcr.io/magento-gke/magento

#### tag つけて push
docker tag ImageName asia.gcr.io/magento-gke/magento:1
docker push asia.gcr.io/magento-gke/magento:1

#### サンプル magento setup コマンド
bin/magento setup:install \
--base-url=http:// \
--db-host= \
--db-name=magento \
--db-user=root \
--db-password=password \
--backend-frontname=admin \
--admin-firstname=admin \
--admin-lastname=admin \
--admin-email=aruga.kazuki@gmail.com \
--admin-user=admin \
--admin-password=Passw0rd! \
--language=ja_JP \
--currency=JPY \
--timezone=Asia/Tokyo \
--use-rewrites=1

Redis
[Use Redis for the Magento page and default cache | Magento 2 Developer Documentation](https://devdocs.magento.com/guides/v2.3/config-guide/redis/redis-pg-cache.html)

Docker イメージづくりのヒント（composer keyの渡し方）に
[Dockerセキュリティ: 今すぐ役に立つテクニックから，次世代技術まで](https://www.slideshare.net/AkihiroSuda/docker-125002128)