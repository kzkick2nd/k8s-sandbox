
TODO CloudSQL 置き換え
TODO /app/etc/ を永続化 or 環境変数化（環境変数化するならDBのスキーマ作る機能が必要になる）
TODO 開発環境 Docker 用に /app/etc/env.php のサンプルを用意（復旧方法も必要）
TODO Dockerfile で magento2 エラーログ吐き出す場所指定？
TODO HTTPS
TODO セッション
TODO キャッシュデータ
TODO アセット
TODO Cron
TODO node-pool の node にどう分散するの？

PEND init したのに database 空っぽ => 初期化コマンド必要？ => 二度目大丈夫だった謎。
DONE /app/etc/env.php の扱い => .dockerignoreで除外
DONE Resource Requests の記載（magento, mysql）
DONE Dockerfile で www-data にユーザー変更

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

docker run -d IMAGE_NAME
docker rm CONTAINER_NAME
docker rmi REPO:TAG => タグ指定削除