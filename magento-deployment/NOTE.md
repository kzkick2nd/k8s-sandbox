## 手順
- Docker イメージを push
- CloudSQL を起動
- GKE を起動
    - 環境変数設定
    - magento-app deploy
    - magento-cron deploy
    - nfs deploy

## NOTE
www-data でコンテナ起動 OK
www-data で NFS マウント OK（Groupでなら）
    nfs-server 実行ユーザー root じゃないとマウントできない
    nfs-server の特権について理解したい
NFS クラスター OK
Init コンテナでNFSに各キャッシュ注入 OK
    - setup
    - redis
    - base-url
APP コンテナ3起動 NFS マウント OK

Admin コンテナ1起動 NFS マウント
    - cron いり
    - admin 専用
    - App コンテナの管理画面潰す
APP production
deploy:mode:set produciton
=> アクセス中は処理できない
setup:static-content:deploy ja_JP
=> 画面真っ白
APP Magento log => STDOUT
redis レプリケーション2 + sentinel 3起動
	=> kubernetes/examples 利用

## STEP
OK nfs OK, SendGrid OK, elasticsearch OK 連携が出来て1ステップ
OK CronJob を構成して2ステップ
OK dokcer コンテナ再構成で3ステップ
deployment yaml 構成して4ステップ
    - 初期化コマンドを実行しなければならない
    - 実行ユーザーを www-data にしなければならない
deployment manager 構成して5ステップ
github からビルドができて6ステップ
監視ができて6ステップ

https://devdocs.magento.com/guides/v2.3/config-guide/redis/config-redis.html

bin/magento setup:config:set --cache-backend=redis --cache-backend-redis-server=10.52.12.20 --cache-backend-redis-db=0
bin/magento setup:config:set --page-cache=redis --page-cache-redis-server=10.52.12.20 --page-cache-redis-db=1
bin/magento setup:config:set --session-save=redis --session-save-redis-host=10.52.12.20 --session-save-redis-log-level=3 --session-save-redis-db=2

bin/magento setup:store-config:set --base-url="http://35.200.109.253/"

## TODO NOTE
stdout,stderror 拡張
SendGrid 拡張
複数台のときの Cache 類の制御どうしよう
    - NFS でシェアしちゃう
実行ユーザーの un-root 化
redis-masterにslave追加した方がいいかも？
CronJob で実行は不安定になる
    nfs 接続時に何か起きてる？
    admin コンテナで cron 回した方がいいかも
nfs-server はサイドカーの方が良いかも？
    サイドカーの再デプロイ時はどうなる？
            capabilities:
              add:
                - DAC_READ_SEARCH
                - SYS_RESOURCE
    app/etc/env.php 環境変数化
    app/etc
        config.php が増える
    pub/static
        frontend, adminhtml が増える
    pub/media/upload
        - TODO: ユーザーパーミッション
    pub/static
        - TODO: ユーザーパーミッション
        - NFS 共有 OK
        - htaccess 設置 OK
            - 1 nfs_static/ に htaccess をコピーする
            - 2 static/ を削除する
            - 3 nfs_static/ を static/ にリンクする
    NFSマウント検討
        generated => ln マウント OK
        app/etc => ln マウント OK
        pub/static => ln マウント OK
        var/view_preprocessed => 直接マウント OK
        pub/media/upload => 直接マウント OK

## Docker イメージ作業
TODO bin/setup に頼らない初期化に
    - 基礎イメージビルド OK
    - composer コマンドを実行 OK
    - ローカルへマウント
    - env.php ファイルの扱いどうするか環境変数から渡すのが吉
        - entrypoint.sh で環境変数から置き換えるのが良さそう
        - https://dev.classmethod.jp/cloud/creds-design-pattern-in-docker/
    - ※ デザイン編集、モジュール追加のワークフローを一回やって差分をみる
        - 開発 => ビルド => push
    - commit push & pull 起動に対応
    - メモリ足りてない
        - php.ini 756

TODO Docker イメージのログ出力
    - STDOUTへ動かしたいなら、既存 logger を上書きする必要ある
    - https://devdocs.magento.com/guides/v2.3/config-guide/log/log-intro.html

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
    - 自動設定されている環境変数
        - REDIS_MASTER_SERVICE_HOST

TODO Cron
    - CronJobでjobのpodを設定できる
    - CronJob専用のイメージを用意した方が良い？
        - とてもめんどくさい
            - Apache を切ってコンテナ起動で代替
        - 起動時に初期設定が必須になる
            - DBの共有
            - 環境変数
        - var/log に書き込もうとしてパーミッションエラーを起こしている
            - cron 処理の前段にログファイルの存在を必要としているらしい。キツイな。
        - cron実行にメモリ不足
            - [2019-07-29 09:06:52] setup-cron.ERROR: Your current PHP memory limit is 128M. Magento 2 requires it to be set to 756M or more. As a user with root privileges, edit your php.ini file to increase memory_limit. (The command php --ini tells you where it is located.) After that, restart your web server and try again. [] []
            - php memory limit 756M に変更が必要

TODO ElasticSearch 初期化コマンド
    - 接続先を含めて初期化が必要?（DBに記載するから初期実行でも良い気がするけど）

TODO GKE の操作方法
    - CloudShell がベターだとは思う

TODO GKE パフォーマンス
    - レプリカ数一つでもいいかも？ => デプロイを考えると複数台は必須

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
PEND Varnish
    - GKE 構成で Vanish 扱うの難しい（キャッシュをノードごとに配置することに）
        - HAProxy の方がいいかもな
    - GCR にイメージなし（ミラーも見当たらず）
    - 独自に pull して用意するしかないかな
        - [Magento2 with Varnish — Varnish Wiki documentation](https://www.varnish-software.com/wiki/content/tutorials/magento2/index.html)
        - [Configure and use Varnish | Magento 2 Developer Documentation](https://devdocs.magento.com/guides/v2.3/config-guide/varnish/config-varnish.html)
        - [Varnish in Magento 2 [Basic Settings & Practical Use]](https://amasty.com/blog/use-varnish-with-magento-2-for-better-end-user-experience/)
    - とりあえずイメージ用意だけできた

---
DONE イメージ最小化 1GB スタート
    - マルチビルド化 OK
    - && 削除

DONE メール
    - Sendgrid via SMTP
        - 標準機能になかった
        - mageplaza SMTP 拡張を利用する https://www.mageplaza.com/magento-2-smtp/
            - インストールして運用に対応できてない
            - mageplaza のアカウント情報が必要
    - SMTP エラー出てて使えない
        - https://github.com/magento/magento2/issues/20033
        - https://github.com/magento/magento2/issues/23645
    - Sendgrid via API(Magento module)
        - MarketPlace で購入 https://sendgrid.com/docs/for-developers/partners/magento/
        - composer require
            - auth 必要
            - php 5.6系に依存してる
            - 2.2 に依存していて使えない
        - SendGrid 公式の拡張について連絡してみる？
        - SendGrid 拡張のソースをゲットして修正する？

DONE web, app 分割
    - ジョブ化するなら Apache プロセスは不要
    - CMD 上書きで対応する OK

DONE Elasticsearch
    - 月$17のマネージド・サービス
    - 接続情報はDBに含まれている
    - 管理画面かコマンドラインで設定する
    - 疎通確認 OK
    - $ bin/magento indexer:reindex

DONE Assets 共有ディスク => pub/media/upload
    - 通常pvcディスク NG = 共有できない・中身消える
        - recalim policy = delete <= 変更するためには pv 作る必要あり
    - Google Cloud Filestore => 高すぎNG
    - 共有ディスク => NFSイメージ使うのが良さそう volume_nfs:0.8
        - 起動共有 OK
        - 構成イメージ APP mount => nfs pvc > nfs pv > nfs-sv > nfs-sv pvc
        - nfs pvc で nfs-sv の IP を指定する箇所がある。ここをサービス名で取得できないか？
            - dns 名が利用できる https://github.com/kubernetes/examples/blob/master/staging/volumes/nfs/nfs-pv.yaml
            - nfs-server.default.svc.cluster.local

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
        - $ bin/magento setup:store-config:set --language=ja_JP --currency=JPY --timezone=Asia/Tokyo --use-rewrites=1
        - $ bin/magento admin:user:create --admin-user=admin --admin-password=dnut8hic --admin-email=aruga.kazuki@gmail.com --admin-firstname=kazuki --admin-lastname=aruga
        - $ bin/magento cache:flush
        - production
            - php -d memory_limit=-1 bin/magento setup:static-content:deploy -f ja_JP
            - https://devdocs.magento.com/guides/v2.3/config-guide/cli/config-cli-subcommands-static-view.html
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
--base-url=http://34.84.184.248/ \
--db-host=10.125.192.5 \
--db-name=magento \
--db-user=magento \
--db-password=Dnut8hic \
--backend-frontname=admin \
--admin-firstname=admin \
--admin-lastname=admin \
--admin-email=aruga.kazuki@gmail.com \
--admin-user=admin \
--admin-password=Dnut8hic \
--language=ja_JP \
--currency=JPY \
--timezone=Asia/Tokyo \
--use-rewrites=1

Redis
[Use Redis for the Magento page and default cache | Magento 2 Developer Documentation](https://devdocs.magento.com/guides/v2.3/config-guide/redis/redis-pg-cache.html)

Docker イメージづくりのヒント（composer keyの渡し方）に
[Dockerセキュリティ: 今すぐ役に立つテクニックから，次世代技術まで](https://www.slideshare.net/AkihiroSuda/docker-125002128)

- "cp -f /var/www/html/app/etc/* /var/www/html/app/nfs_etc \
&& rm -rf /var/www/html/app/etc \
&& ln -sf /var/www/html/app/nfs_etc /var/www/html/app/etc \
&& cp -f /var/www/html/generated/.htaccess /var/www/html/nfs_generated \
&& rm -rf /var/www/html/generated \
&& ln -sf /var/www/html/nfs_generated /var/www/html/generated \
&& cp -f /var/www/html/pub/static/.htaccess /var/www/html/pub/nfs_static \
&& rm -rf /var/www/html/pub/static \
&& ln -sf /var/www/html/pub/nfs_static /var/www/html/pub/static"

起動したディスクによってはエラー
==> var/log/system.log <==
[2019-08-29 11:29:51] main.ERROR: Unable to resolve the source file for 'frontend/Magento/luma/ja_JP/Magento_Customer/js/zxcvbn.js.map' [] []
[2019-08-29 11:29:51] main.CRITICAL: Unable to resolve the source file for 'frontend/Magento/luma/ja_JP/Magento_Customer/js/zxcvbn.js.map' [] []
[2019-08-29 11:30:04] main.ERROR: Warning: include(/var/www/html/generated/code/Magento//Backend/Model/Auth/Proxy.php): failed to open stream: No such file or directory in /var/www/html/vendor/composer/ClassLoader.php on line 444 [] []
[2019-08-29 11:30:10] main.ERROR: Warning: include(/var/www/html/generated/code/Magento//Framework/App/Config/FileResolver/Proxy.php): failed to open stream: No such file or directory in /var/www/html/vendor/composer/ClassLoader.php on line 444
