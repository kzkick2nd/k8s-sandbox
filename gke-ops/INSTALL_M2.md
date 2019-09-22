# 初期設定
gcloud init
gcloud config set project [PROJECT_NAME]
gcloud services enable container.googleapis.com
gcloud services enable servicenetworking.googleapis.com

# VPC 作成
gcloud compute --project=magento2-gke networks create magento --description="VPC for Magento2 GKE" --subnet-mode=auto
gcloud compute --project=magento2-gke firewall-rules create magento-allow-internal --description="ネットワーク IP 範囲内の任意の送信元からネットワーク上の任意のインスタンスへの、すべてのプロトコルを使用した接続を許可します。" --direction=INGRESS --network=magento --action=ALLOW --rules=all --source-ranges=10.128.0.0/9

# IAM 設定
プロジェクト管理者 = 「編集者」権限を与えてください

その他の役割においては GKE 運用に関連する IAM 権限グループを必要に応じて付与してください
- Kubernetes Engine
- Compute Engine
- Cloud SQL
- Cloud Build
- ストレージ
- Stackdriver

# CloudSQL 準備
PrivateIP 割当が発生するので WEB Console から新規作成する
- 暖気に時間がとられるので注意
- network は magento を指定
- PrivateIP 接続を選択
> NOTE: DB_RootPass =
> NOTE: DB_PrivateIp =

$ gcloud sql databases create magento -i magento-sql --charset=utf8mb4
$ gcloud sql users create magento --host=% --instance=magento-sql --password=[DB_PASS]
> NOTE: INSTANCE magento-sql DB_NAME magento DB_USER magento Passw0rd [DB_PASS]

# Node 用 GSA 作成（権限：モニタリング閲覧者, モニタリング指標の書き込み, ログ書き込み, ストレージオブジェクト閲覧者)
- GSA for Node: magento-gke

$ gcloud iam service-accounts create magento-gke \
    --display-name=magento-gke
$ gcloud projects add-iam-policy-binding magento2-gke \
    --member "serviceAccount:magento-gke@magento2-gke.iam.gserviceaccount.com" \
    --role roles/logging.logWriter
$ gcloud projects add-iam-policy-binding magento2-gke \
    --member "serviceAccount:magento-gke@magento2-gke.iam.gserviceaccount.com" \
    --role roles/monitoring.metricWriter
$ gcloud projects add-iam-policy-binding magento2-gke \
    --member "serviceAccount:magento-gke@magento2-gke.iam.gserviceaccount.com" \
    --role roles/monitoring.viewer
$ gcloud projects add-iam-policy-binding magento2-gke \
    --member "serviceAccount:magento-gke@magento2-gke.iam.gserviceaccount.com" \
    --role roles/storage.objectViewer

# GKE クラスター作成
- small-pool * 3
- preemptible-pool CPU2 3.75GB SSD50GB * 3

※ 以下は GUI から生成したコマンドだが結果が違うので GUI でやった方が良いかも(2019/09/18)
$ gcloud beta container --project "magento2-gke" clusters create "magento-cluster" --region "asia-northeast1" --no-enable-basic-auth --release-channel "regular" --machine-type "custom-2-3072" --image-type "COS" --disk-type "pd-ssd" --disk-size "50" --metadata disable-legacy-endpoints=true --node-taints cloud.google.com/gke-preemptible=true:NoSchedule --service-account "magento-gke@magento2-gke.iam.gserviceaccount.com" --preemptible --num-nodes "1" --enable-stackdriver-kubernetes --enable-ip-alias --network "projects/magento2-gke/global/networks/magento" --subnetwork "projects/magento2-gke/regions/asia-northeast1/subnetworks/magento" --default-max-pods-per-node "110" --enable-network-policy --addons HorizontalPodAutoscaling,HttpLoadBalancing --enable-autoupgrade --enable-autorepair --maintenance-window "21:00" --enable-vertical-pod-autoscaling --identity-namespace "magento2-gke.svc.id.goog" --enable-shielded-nodes --shielded-secure-boot && gcloud beta container --project "magento2-gke" node-pools create "small-pool" --cluster "magento-cluster" --region "asia-northeast1" --machine-type "g1-small" --image-type "COS" --disk-type "pd-ssd" --disk-size "50" --metadata disable-legacy-endpoints=true --service-account "magento-gke@magento2-gke.iam.gserviceaccount.com" --num-nodes "1" --enable-autoupgrade --enable-autorepair --shielded-secure-boot

gcloud container clusters get-credentials magento-cluster --region=asia-northeast1

# Pod 用 KSA<=>GSA 作成
- GSA for Pod: magento-pod-gke
- KSA: magento-ksa
- NameSpace: magento-ns

$ gcloud iam service-accounts create magento-pod-gke \
    --display-name=magento-pod-gke
$ kubectl create namespace magento-ns
$ kubectl create serviceaccount --namespace magento-ns magento-ksa
$ gcloud iam service-accounts add-iam-policy-binding \
    --role roles/iam.workloadIdentityUser \
    --member "serviceAccount:magento2-gke.svc.id.goog[magento-ns/magento-ksa]" \
    magento-pod-gke@magento2-gke.iam.gserviceaccount.com
$ kubectl annotate serviceaccount \
    --namespace magento-ns \
    magento-ksa \
    iam.gke.io/gcp-service-account=magento-pod-gke@magento2-gke.iam.gserviceaccount.com
$ kubectl config set-context $(kubectl config current-context) --namespace=magento-ns

> WId を設定してある場合これで namespace と serviceAccount を指定する限りは GSA の権限を共有する

# Helm 準備
kubectl create serviceaccount -n kube-system tiller
kubectl create clusterrolebinding tiller-cluster-rule --clusterrole=cluster-admin --serviceaccount=kube-system:tiller
helm init --service-account tiller
helm init --upgrade --service-account tiller

TODO: SA, RoleBinding コマンド化

kubectl apply -f storage-class-faster.yaml
helm install --name redis -f helm-redis-values.yaml stable/redis
kubectl get secret --namespace magento-ns redis -o jsonpath="{.data.redis-password}"
得られた Base64 パスワードを app-secret.yaml に追加
helm install stable/nfs-server-provisioner --name nfs -f helm-nfs-values.yaml

# GCR Image 格納
$ gcloud auth configure-docker
$ docker tag [IMAGE] asia.gcr.io/magento2-gke/magento:1
$ docker push asia.gcr.io/magento2-gke/magento:1

# config, secret
$ kubectl apply -f app-config.yaml
$ kubectl apply -f app-secret.yaml

# NetworkPolicy 適用
kubectl apply -f network-policy.yaml

# NFS 準備
kubectl apply -f nfs-pvc.yaml

# Init 実行
$ kubectl apply -f app-init.yaml
$ kubectl delete -f app-init.yaml

# 予約 IP と証明書を準備
gcloud compute addresses create magento-admin-static-ip --global
gcloud compute addresses create magento-static-ip --global
ssl-certificate.yaml

# Admin 準備
kubectl apply -f app-admin-deploy.yaml
kubectl apply -f app-admin-ing.yaml
Ingress を導入後に暖気が必要
CloudDNS に Static IP 設定する

# App 準備
kubectl apply -f app-front-deploy.yaml
kubectl apply -f app-front-ing.yaml
Ingress を導入後に暖気が必要
CloudDNS に Static IP 設定する
Change Admin URL で 管理画面 > 店舗 > 設定 > 高度な設定 > 管理者 > ベースURL に [ADMIN_DOMAIN_NAME] 設定

# SSL Certificate の Provisioning が結構かかる
$ gcloud beta compute ssl-certificates list
# SSL Certificate の初期化が結構かかる( ERR_SSL_VERSION_OR_CIPHER_MISMATCH ) 15分ほど暖気が必要
# PROVISIONING FAIL も発生する

# Cron 起動
kubectl apply -f app-cron.yaml

# => FIN

## NOTE
> node と pv が同じ zone に無いと起動できない
> GoogleDomain だろうと NS の変更は必要
> CDN は専用ルールが必要
> インスタンスの公開IP出てる
> 一部 Node 止める gcloud compute instances stop https://cloud.google.com/compute/docs/instances/stop-start-instance#stopping_an_instance
> NOTE redis と nfs 落ちるとどうしようもない => taints
> MEMO: tiller アカウントのセキュリティ設定
    Please note: by default, Tiller is deployed with an insecure 'allow unauthenticated users' policy.
    To prevent this, run `helm init` with the --tiller-tls-verify flag.
    For more information on securing your installation see: https://docs.helm.sh/using_helm/#securing-your-helm-installation
> PVC にも namespace 必要
> PV に namespace 必要
> mount.nfs: Connection timed out 権限か火壁か => Namespace 書き忘れだった...
> Error during sync: error running backend syncing routine: googleapi: Error 403: QUOTA_EXCEEDED - Quota 'BACKEND_SERVICES' exceeded. Limit: 5.0 globally.
    > BACKEND_SERVICES 作成の割当数が少ないとでる
> helm value 更新
    helm upgrade で対応できるが、書き換え禁止情報もある
    https://medium.com/@kcatstack/understand-helm-upgrade-flags-reset-values-reuse-values-6e58ac8f127e
