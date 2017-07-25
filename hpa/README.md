# Horizontal Pod Autoscaling

Horizontal Pod Autoscaling automatically scales the number of pods in a replication controller, deployment or replica set based on observed CPU utilization

## Prerequisites

* version 1.2 or later
* Heapster monitoring

To install Heapster execute the following command:

```
$ kubectl create -f https://raw.githubusercontent.com/kubernetes/heapster/master/deploy/kube-config/standalone/heapster-controller.yaml
```
Additionally you can install Dashboard

```
$ kubectl create -f https://raw.githubusercontent.com/zreigz/kubernetes-workshop/master/hpa/dashboard.yaml
```

The easiest way to access Dashboard is to use kubectl. Run the following command in your desktop environment:

$ kubectl proxy

kubectl will handle authentication with apiserver and make Dashboard available at http://localhost:8001/ui

The UI can only be accessed from the machine where the command is executed. See kubectl proxy --help for more options.

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
