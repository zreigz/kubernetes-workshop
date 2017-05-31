![demo](./demo.png)

This is simple RESTful application to consume and display data. Redis is used as a data structure store. The application is based on DropWizard framework.

Now Let’s connect three applications:
* redis backend storage
* REST WEB service
* UI for WEB service

Kubernetes supports 2 primary modes of finding a Service - environment variables and DNS.

## Create pods and services

```
$ kubectl create -f redis-service.yaml
$ kubectl create -f redis.yaml
$ kubectl create -f web-list-deployment.yaml
$ kubectl create -f web-list-service.yaml
```

Verify pods:

```
$ kubectl get pods
NAME                        READY     STATUS    RESTARTS   AGE
redis                       1/1       Running   0          59s
web-list-1500300148-2bmpv   1/1       Running   0          52s
```

### Environment Variables

When a Pod is run on a Node, the kubelet adds a set of environment variables for each active Service. This introduces an ordering problem. To see why, inspect the environment of your running redis pod and web-list pod:

```
$ kubectl exec web-list-1500300148-2bmpv -- printenv | grep SERVICE
KUBERNETES_SERVICE_PORT=443
REDIS_SERVICE_HOST=10.0.0.133
REDIS_SERVICE_PORT=6379
WEB_LIST_SERVICE_PORT_8080_TCP_PORT=8080
WEB_LIST_SERVICE_PORT=tcp://10.0.0.215:8080
WEB_LIST_SERVICE_SERVICE_PORT=8080
WEB_LIST_SERVICE_SERVICE_HOST=10.0.0.215
```
The `web-list` application can use environment variables to reach redis service.

### DNS

Kubernetes offers a DNS cluster addon Service that uses skydns to automatically assign dns names to other Services. You can check if it’s running on your cluster:

```
$ kubectl get services kube-dns --namespace=kube-system
NAME       CLUSTER-IP   EXTERNAL-IP   PORT(S)         AGE
kube-dns   10.0.0.10    <none>        53/UDP,53/TCP   6h
```

## Create UI

Now create last component:

```
$ kubectl create -f web-list-ui-service.yaml
$ kubectl create -f web-list-ui-deployment.yaml
```

There are two ways to call UI endpoint

* 1 Node PORT

Find exposed port:
```
$ kubectl get service web-list-ui-service
NAME                  CLUSTER-IP   EXTERNAL-IP   PORT(S)        AGE
web-list-ui-service   10.0.0.89    <nodes>       80:32486/TCP   9m
```
And you can enter url to your WEB browser: `http://192.168.99.100:32486/`

* 2 Service proxy
You can use the API server and get access to your service by service name:
`http://API_IP:API_PORT/api/v1/proxy/namespaces/default/services/web-list-ui-service/`

Minikube uses `https` and this way is unauthorized. There is workaround and `kubectl` can create secure connection (tunnel) between your host and kubernetes API server.

```
kubectl proxy --address="0.0.0.0" --port=9090
```
Use the following url to get access to service:
`http://MINIKUBE_IP:9090/api/v1/proxy/namespaces/default/services/web-list-ui-service/`


