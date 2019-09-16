# 初期設定
gcloud init
gcloud auth configure-docker

# GKE クラスター作成
gcloud beta container --project "magento-gke-248206" clusters create "high-cpu-cluster-1" --region "asia-northeast1" --no-enable-basic-auth --cluster-version "1.13.7-gke.8" --machine-type "n1-highcpu-4" --image-type "COS" --disk-type "pd-ssd" --disk-size "32" --metadata disable-legacy-endpoints=true --node-taints cloud.google.com/gke-preemptible=true:NoSchedule --scopes "https://www.googleapis.com/auth/devstorage.read_only","https://www.googleapis.com/auth/logging.write","https://www.googleapis.com/auth/monitoring","https://www.googleapis.com/auth/servicecontrol","https://www.googleapis.com/auth/service.management.readonly","https://www.googleapis.com/auth/trace.append" --preemptible --num-nodes "1" --enable-stackdriver-kubernetes --enable-ip-alias --network "projects/magento-gke-248206/global/networks/default" --subnetwork "projects/magento-gke-248206/regions/asia-northeast1/subnetworks/default" --default-max-pods-per-node "110" --enable-network-policy --addons HorizontalPodAutoscaling,HttpLoadBalancing --enable-autoupgrade --enable-autorepair --maintenance-window "18:00" --enable-vertical-pod-autoscaling --enable-shielded-nodes --shielded-secure-boot && gcloud beta container --project "magento-gke-248206" node-pools create "pool-1" --cluster "high-cpu-cluster-1" --region "asia-northeast1" --node-version "1.13.7-gke.8" --machine-type "f1-micro" --image-type "COS" --disk-type "pd-ssd" --disk-size "32" --scopes "https://www.googleapis.com/auth/devstorage.read_only","https://www.googleapis.com/auth/logging.write","https://www.googleapis.com/auth/monitoring","https://www.googleapis.com/auth/servicecontrol","https://www.googleapis.com/auth/service.management.readonly","https://www.googleapis.com/auth/trace.append" --num-nodes "1" --enable-autoupgrade --enable-autorepair --shielded-secure-boot

gcloud container clusters get-credentials [NAME] --region=[REGION]

# Helm 準備
kubectl create serviceaccount -n kube-system tiller
kubectl create clusterrolebinding tiller-cluster-rule --clusterrole=cluster-admin --serviceaccount=kube-system:tiller
helm init --service-account tiller
> TODO: tiller アカウントのセキュリティ問題
helm init --upgrade --service-account tiller

helm install --name redis -f helm-redis-values.yaml stable/redis
kubectl get secret --namespace default redis -o jsonpath="{.data.redis-password}" | base64 --decode
> NOTE: REDIS_PASS P68MEvdAXV

# CloudSQL 準備
gcloud beta sql instances create [NAME] --tier=db-f1-micro --region=asia-northeast1 --network=default --no-assign-ip
> NOTE: DB_PrivateIp 10.125.192.9
gcloud sql databases create magento -i [NAME] --charset=utf8mb4
gcloud sql users create magento --host=% --instance=[NAME] --password=kP7YnpzX
> NOTE: SQL_NAME cloud-sql DB_NAME magento DB_USER magento kP7YnpzX

# NFS 準備
kubectl apply -f nfs-deploy.yaml
kubectl apply -f nfs-pvc.yaml

# Init 開始
kubectl apply -f app-init.yaml

kubectl get po
kubectl exec -it [INIT_POD] -- bash

bin/magento setup:install \
--base-url=http://localhost/ \
--db-host=10.125.192.9 \
--db-name=magento \
--db-user=magento \
--db-password=kP7YnpzX \
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

bin/magento setup:config:set --cache-backend=redis --cache-backend-redis-server=redis-master.default.svc.cluster.local --cache-backend-redis-db=0 --cache-backend-redis-password=[REDIS_PASS]

bin/magento setup:config:set --page-cache=redis --page-cache-redis-server=redis-master.default.svc.cluster.local --page-cache-redis-db=1 --page-cache-redis-password=[REDIS_PASS]

bin/magento setup:config:set --session-save=redis --session-save-redis-host=redis-master.default.svc.cluster.local --session-save-redis-log-level=3 --session-save-redis-db=2 --session-save-redis-password=[REDIS_PASS]

bin/magento deploy:mode:set production
bin/magento setup:static-content:deploy ja_JP
> TODO: Magento2 メンテナンスモードファイルの配布

# Admin 準備
kubectl apply -f app-admin-deploy.yaml
kubectl apply -f app-admin-svc.yaml
> NOTE: External IP 34.84.184.248
bin/magento setup:store-config:set --base-url="http://34.84.184.248/"
> Change Admin URL は 管理画面から 店舗 > 設定 > 高度な設定 > 管理者 > ベースURL

# App 準備
kubectl delete -f app-init.yaml
kubectl apply -f app-front-deploy.yaml
> NOTE: ingress を導入する前に暖気が必要。3分待つ。
kubectl apply -f app-front-ing.yaml
> NOTE: 外部 IP が払い出されるまで待機 ingress_IP 34.102.147.110
> NOTE: さらにingress の暖気を待つ

NOTE: DNS に ingress IP 設定
bin/magento setup:store-config:set --base-url="[DOMAIN_NAME]"

# Cron 準備
kubectl apply -f app-cron.yaml

# FIN

> TODO: IAM, ServiceAccount, RBAC, Network,

> MEMO: GoogleDomain だろうと NS の変更は必要
> MEMO: CDN は専用ルールが必要
> MEMO: インスタンスの公開IP出てる
> NOTE: 一部 Node 止める gcloud compute instances stop https://cloud.google.com/compute/docs/instances/stop-start-instance#stopping_an_instance
> NONTE redis と nfs 落ちるとどうしようもない => taints つけて回避