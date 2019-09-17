## NOTE
var/.maintenance.flag
が必要になる
https://devdocs.magento.com/guides/v2.3/install-gde/install/cli/install-cli-subcommands-maint.html

たまに使う Magento コマンド
bin/magento admin:user:unlock
bin/magento setup:store-config:set --base-url="http://34.102.208.132/"

## ServiceAccount
    IAM + ServiceAccoun
## VPC/SubNet
    gcloud OK
## 限定公開クラスタの設定
    https://cloud.google.com/kubernetes-engine/docs/how-to/private-clusters?hl=ja
## NetWorkPolicy
    => 先にNamespaceの設計必要 OK
    https://cloud.google.com/kubernetes-engine/docs/concepts/network-overview?hl=ja
    https://kubernetes.io/docs/concepts/services-networking/network-policies/
    app:magento component: frontend <=> svc
    app:magento component: admin <=> svc, IP
    app:magento component: cron <=> nil
    app:redis <=> app:magento
    nfs <=> internal port
## Admin側のIP制限
    NW Policy で実施
## CloudBuild
    GitOps
## Spec
    preemptive 2core + 2.75GB + 50GB
## SSL
    負荷分散からマネージドSSL ?

## SecurityContext OK
    readOnlyRootFilesystem: true
## PodSecurityPolicy OK
    個別設定でOK
## Labelの設計 OK
    app, component

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
