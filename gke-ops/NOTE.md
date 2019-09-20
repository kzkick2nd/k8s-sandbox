## NOTE
var/.maintenance.flag
が必要になる
https://devdocs.magento.com/guides/v2.3/install-gde/install/cli/install-cli-subcommands-maint.html

たまに使うコマンド
gcloud sql databases delete [DB_NAME] -i [INSTANCE_NAME]
bin/magento admin:user:unlock
bin/magento setup:store-config:set --base-url=
bin/magento setup:static-content:deploy ja_JP

gcloud iam service-accounts list
gcloud info --format='value(config.project)'

kubectl scale deployment --replicas=0 [DEPLOY]
kubectl get pods | grep Evicted | awk '{print $1}' | xargs kubectl delete pod

magento-app コンテナのローリングアップデートはだいたい3分で完了する

upload の write 権限外すと起動できない

## 進捗
    - 構成手順
    - システム説明構成図
    - 動作テスト
    - 継続的デリバリー

    - クロス ZONE PV エラー => Node のオートスケールを許可 OK
    - ServiceAccount => 時間が必要 OK

## CloudBuild
    GitOps
    - Cloud Build でコンテナ イメージを作成する
    - 継続的インテグレーション パイプラインを作成する
    - 継続的デリバリー パイプラインを作成する

## SecurityContext
    readOnlyRootFilesystem: true
    => NFS マウントできない
    => 部分的に書き込み可能にできない？
    privillage: true
    => 部分的にできないか？

## Admin側のIP制限
    Ingress で制限かけられない？

## GCE のファイアーウォール？
    - 外部 IP が構成されている

## クラスタ マスター アクセス用の承認済みネットワークの追加
    マスターアクセスネットワーク制御。GCPだけにすればよい？

## 限定公開クラスタの設定
    完全スタンドアローンからホワイトリスト、過激すぎない？
    https://cloud.google.com/kubernetes-engine/docs/how-to/private-clusters?hl=ja

---
## NFS 系 OK
- nfs がボトルネック。他の構成方法ない？ => NFS 一部に
- M2 メンテナンスモード var/.maintenance の配布方法 => 複数 Pod に kubectl exec 打ち込む運用に

## IAM, Uer RBAC, GSA, KSA OK
    IAM=RBAC 関連項目の用意 OK
    Node 起動 GSA の作成必須 OK
        https://cloud.google.com/kubernetes-engine/docs/how-to/hardening-your-cluster?hl=ja#use_least_privilege_sa
        - モニタリング閲覧者
        - モニタリング指標の書き込み
        - ログ書き込み
        - ストレージオブジェクト閲覧者
    Pod 用 GKE=KSA の作成 OK
        - Pod 用 KSA<=>GSA の作成と連携
        - 最初は API 権限不要？
## Shielded VM OK
    https://cloud.google.com/shielded-vm/?hl=ja
## SSL OK
    静的IP > 「負荷分散」マネージドSSL
    https://cloud.google.com/kubernetes-engine/docs/tutorials/configuring-domain-name-static-ip?hl=ja
    https://cloud.google.com/kubernetes-engine/docs/how-to/managed-certs?hl=ja
## Spec OK
    small + preemptive 2Core + 3GB + SSD50GB = $100
## VPC/SubNet OK
    gcloud cmd
## PodSecurityPolicy OK
    個別設定でOK
## Labelの設計 OK
    app, component
## NetWorkPolicy OK
    => 先にNamespaceの設計必要 OK
    https://cloud.google.com/kubernetes-engine/docs/concepts/network-overview?hl=ja
    https://kubernetes.io/docs/concepts/services-networking/network-policies/
    app:magento <=> app:redis

## gcloud 準備
gcloud init
gcloud container images list --repository=asia.gcr.io/magento-gke-248206
gcloud container images list-tags asia.gcr.io/magento-gke-248206/magento
gcloud container images delete asia.gcr.io/magento-gke-248206/magento
gcloud auth configure-docker
gcloud compute networks list

## gcloud GKE 操作
gcloud container clusters create [NAME]
gcloud container clusters delete [NAME] --region=[REGION]
gcloud container clusters get-credentials [NAME]

## kubectl 基本操作
kubectl apply -f
kubectl get node
kubectl describe node
kubectl top node

## CloudSQL 操作
gcloud sql tiers list
gcloud beta sql instances create [NAME] --tier=db-f1-micro --region=asia-northeast1 --network=default --no-assign-ip
gcloud sql databases create [NAME] -i [NAME] --charset=utf8mb4
gcloud sql users create [USER_NAME] --host=[HOST] --instance=[INSTANCE] --password=[PASSWORD]
gcloud sql users create [USER_NAME] --host=% --instance=[INSTANCE] --password=[PASSWORD]
gcloud sql databases list -i [NAME]
gcloud sql databases delete

## Helm 操作
kubectl create serviceaccount -n kube-system tiller
kubectl create clusterrolebinding tiller-cluster-rule --clusterrole=cluster-admin --serviceaccount=kube-system:tiller
helm init --service-account tiller
helm init --upgrade --service-account tiller
kubectl --namespace kube-system get pods | grep tiller
