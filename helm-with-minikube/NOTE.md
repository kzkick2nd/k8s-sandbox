helm => k8s の構成パッケージを管理する。デフォがセキュリティ危険だが、ローカル利用ならデフォで OK

minikube 設定変更
minikube config set memory 4096

minikube クラスター起動する
minikube start --vm-driver=hyperkit

CLI と Tiller の起動
helm init --history-max 200

パッケージインストールとリリース
helm install XXX

ステータス確認
helm status XXX

リリース一覧
helm ls

リリース削除
helm delete XXX

設定変更
helm upgrade

Magento
helm install provider/charts
helm upgrade XXX provider/charts --set key=value,key=value

誤字ポイント
stable/magento => create PR done

--set
    ロードバランサーのIP設定必要
    rootパスワードとmadiadbパスワード必要

    $ minikube start --vm-driver=hyperkit --memory 4096 --disk-size 30g
    $ helm install bitnami/magento --set elasticsearch.sysctlImage.enabled=true
    $ minikube tunnel
    $ helm upgrade helm_name bitnami/magento \
      --set magentoHost=$APP_HOST,magentoPassword=$APP_PASSWORD,mariadb.db.password=$APP_DATABASE_PASSWORD

helm 使うなら GKE ACL 設定が必要
Example: Service account with cluster-admin role
https://helm.sh/docs/using_helm/#tiller-and-role-based-access-control