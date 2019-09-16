## NOTE
var/.maintenance.flag
が必要になる
https://devdocs.magento.com/guides/v2.3/install-gde/install/cli/install-cli-subcommands-maint.html

たまに使う Magento コマンド
bin/magento admin:user:unlock
bin/magento setup:store-config:set --base-url="http://34.102.208.132/"

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
