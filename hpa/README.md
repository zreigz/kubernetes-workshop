# Scaling an Application

Scaling is accomplished by changing the number of replicas in Deployment, ReplicaSet, Replication Controller, or StatefulSet.

## Inspecting an application
Before scaling your application, you should inspect the application and ensure that it is healthy.

To see all applications deployed to your cluster, run `kubectl get [CONTROLLER]`. Substitute `[CONTROLLER]` for deployments, statefulsets, or another controller object type.

The `kubectl scale` method is the fastest way to scale. However, you may prefer another method in some situations, like when updating configuration files or when performing in-place modifications.

Examples:
  ### Scale a replicaset named 'foo' to 3.
  ```
  kubectl scale --replicas=3 rs/foo
  ```

  ### Scale a resource identified by type and name specified in "foo.yaml" to 3.
  ```
  kubectl scale --replicas=3 -f foo.yaml
  ```
  ### If the deployment named mysql's current size is 2, scale mysql to 3.
  ```
  kubectl scale --current-replicas=2 --replicas=3 deployment/mysql
  ``` 
  ### Scale multiple replication controllers.
  ```
  kubectl scale --replicas=5 rc/foo rc/bar rc/baz
  ```
  ### Scale statefulset named 'web' to 3.
  ```
  kubectl scale --replicas=3 statefulset/web
  ```
  
# Horizontal Pod Autoscaling

Horizontal Pod Autoscaling automatically scales the number of pods in a replication controller, deployment or replica set based on observed CPU utilization

## Prerequisites

* version 1.2 or later
* Heapster monitoring

To install Heapster execute the following command:

```
$ kubectl create -f heapster.yaml
```
Additionally you can install Dashboard

```
$ kubectl create -f https://raw.githubusercontent.com/kubernetes/dashboard/master/src/deploy/recommended/kubernetes-dashboard.yaml
```

The easiest way to access Dashboard is to use kubectl. Run the following command in your desktop environment:
```
$ kubectl proxy
```
kubectl will handle authentication with apiserver and make Dashboard available at http://localhost:8001/ui

The UI can only be accessed from the machine where the command is executed. See kubectl proxy --help for more options.

### Creating sample user
In this guide, we will find out how to create a new user using Service Account mechanism of Kubernetes, grant this user admin permissions and log in to Dashboard using bearer token tied to this user.

```
apiVersion: v1
kind: ServiceAccount
metadata:
  name: admin-user
  namespace: kube-system
```
and execute

```
$ kubectl create -f sa.yaml
```
Create `ClusterRoleBinding`
In most cases after provisioning our cluster using kops or kubeadm or any other popular tool admin Role already exists in the cluster. We can use it and create only RoleBinding for our ServiceAccount.
```
apiVersion: rbac.authorization.k8s.io/v1beta1
kind: ClusterRoleBinding
metadata:
  name: admin-user
roleRef:
  apiGroup: rbac.authorization.k8s.io
  kind: ClusterRole
  name: cluster-admin
subjects:
- kind: ServiceAccount
  name: admin-user
  namespace: kube-system

```
execute:
```
$ kubectl create -f clustercolebinding.yaml
```
Now we need to find token we can use to log in. Execute following command:
```
kubectl -n kube-system describe secret $(kubectl -n kube-system get secret | grep admin-user | awk '{print $1}')
```

It should print something like:
```
Name:         admin-user-token-6gl6l
Namespace:    kube-system
Labels:       <none>
Annotations:  kubernetes.io/service-account.name=admin-user
              kubernetes.io/service-account.uid=b16afba9-dfec-11e7-bbb9-901b0e532516

Type:  kubernetes.io/service-account-token

Data
====
ca.crt:     1025 bytes
namespace:  11 bytes
token:      eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJrdWJlcm5ldGVzL3NlcnZpY2VhY2NvdW50Iiwia3ViZXJuZXRlcy5pby9zZXJ2aWNlYWNjb3VudC9uYW1lc3BhY2UiOiJrdWJlLXN5c3RlbSIsImt1YmVybmV0ZXMuaW8vc2VydmljZWFjY291bnQvc2VjcmV0Lm5hbWUiOiJhZG1pbi11c2VyLXRva2VuLTZnbDZsIiwia3ViZXJuZXRlcy5pby9zZXJ2aWNlYWNjb3VudC9zZXJ2aWNlLWFjY291bnQubmFtZSI6ImFkbWluLXVzZXIiLCJrdWJlcm5ldGVzLmlvL3NlcnZpY2VhY2NvdW50L3NlcnZpY2UtYWNjb3VudC51aWQiOiJiMTZhZmJhOS1kZmVjLTExZTctYmJiOS05MDFiMGU1MzI1MTYiLCJzdWIiOiJzeXN0ZW06c2VydmljZWFjY291bnQ6a3ViZS1zeXN0ZW06YWRtaW4tdXNlciJ9.M70CU3lbu3PP4OjhFms8PVL5pQKj-jj4RNSLA4YmQfTXpPUuxqXjiTf094_Rzr0fgN_IVX6gC4fiNUL5ynx9KU-lkPfk0HnX8scxfJNzypL039mpGt0bbe1IXKSIRaq_9VW59Xz-yBUhycYcKPO9RM2Qa1Ax29nqNVko4vLn1_1wPqJ6XSq3GYI8anTzV8Fku4jasUwjrws6Cn6_sPEGmL54sq5R4Z5afUtv-mItTmqZZdxnkRqcJLlg2Y8WbCPogErbsaCDJoABQ7ppaqHetwfM_0yMun6ABOQbIwwl8pspJhpplKwyo700OSpvTT9zlBsu-b35lzXGBRHzv5g_RA
```
Take this token to login Dashboard.

## Start pod

To demonstrate Horizontal Pod Autoscaler we will use a custom docker image based on the php-apache image. The Dockerfile can be found here. It defines an index.php page which performs some CPU intensive computations.

First, we will start a deployment running the image and expose it as a service:

```
$ kubectl run php-apache --image=gcr.io/google_containers/hpa-example --requests=cpu=200m --expose --port=80
service "php-apache" created
deployment "php-apache" created

```

## Create Horizontal Pod Autoscaler

```
$ kubectl autoscale deployment php-apache --cpu-percent=50 --min=1 --max=10
deployment "php-apache" autoscaled

```
The following command will create a Horizontal Pod Autoscaler that maintains between 1 and 10 replicas of the Pods controlled by the php-apache deployment we created in the first step of these instructions.
Roughly speaking, HPA will increase and decrease the number of replicas (via the deployment) to maintain an average CPU utilization across all Pods of 50% (since each pod requests 200 milli-cores by kubectl run, this means average CPU usage of 100 milli-cores). 

We may check the current status of autoscaler by running:

```
$ kubectl get hpa
NAME         REFERENCE                     TARGET    CURRENT   MINPODS   MAXPODS   AGE
php-apache   Deployment/php-apache/scale   50%       0%        1         10        18s
```

## Increase load

Please run it in a different terminal:

```
$ kubectl run -i --tty load-generator --image=busybox /bin/sh

Hit enter for command prompt

$ while true; do wget -q -O- http://php-apache.default.svc.cluster.local; done
```

we should see the higher CPU load by executing:
```
$ kubectl get hpa
NAME         REFERENCE                     TARGET    CURRENT   MINPODS   MAXPODS   AGE
php-apache   Deployment/php-apache/scale   50%       305%      1         10        3m
```
Here, CPU consumption has increased to 305% of the request. As a result, the deployment was resized to 7 replicas:
```
$ kubectl get deployment php-apache
NAME         DESIRED   CURRENT   UP-TO-DATE   AVAILABLE   AGE
php-apache   7         7         7            7           19m
```
