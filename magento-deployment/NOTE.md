
TODO /app/etc/ 対応
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

TODO Assets 共有ディスク => pub/media/upload
    - 通常pvcディスク NG = 共有できない。中身消える recalim policy = delete <= 変更するにはpv作るところから
    - Google Cloud Filestore => いけそう => 高すぎ
    - 共有ディスクどうしよう問題 => NFS型使うか
TODO Cron
TODO メール

TODO WAF、ネットワーク防御
TODO HTTPS
TODO ドメイン紐付け
TODO Magento アップデート運用

TODO Dockerfile で magento2 エラーログ吐き出す場所指定？
TODO node-pool の node にどう分散するの？
TODO 構成管理 Cloud Deployment Manager
TODO GKE イメージ起動時になにかコマンド実行できるのか？
TODO 開発環境 Docker 用に /app/etc/env.php のサンプルを用意（消滅時の復旧方法も必要）

PEND init したのに database 空っぽ => 初期化コマンド必要？ => 二度目大丈夫だった謎。
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