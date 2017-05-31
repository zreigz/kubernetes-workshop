# Pod, ReplicationController, Deployment and Service

This tutorial demonstrates how to build a simple WEB application using Kubernetes.
The tutorial application is a simple WEB server that displays current IP address.

The tutorial shows how to set up the web server on an internal IP with a load balancer.

The example highlights a number of important Kubernetes concepts:

* Declarative configuration using YAML manifest files
* Deployments, which is a Kubernetes concept maintains a set of replicas Pods
* Services to create internal load balancers for a set of Pods.

## Pod Deployments

Use the manifest file named `http-pod.yaml` to deploy the container. This manifest file specifies a Pod controller that runs a single container instance from image `zreigz/simple-http-pod`:

```
apiVersion: v1
kind: Pod
metadata:
  name: simple-http
  labels:
    name: simple-http
spec:
  containers:
  - name: simple-http-pod
    image: zreigz/simple-http-pod
    ports:
- containerPort: 8080
```

Run the following command to deploy the pod:

```
$ kubectl create -f http-pod.yaml
pod "simple-http" created
```

Verify that the pod is running `kubectl get pods`:

```
$ kubectl get pods
NAME          READY     STATUS    RESTARTS   AGE
simple-http   1/1       Running   0          21s
```

Copy the pod name from the output of the previous command and run the following command to take a look at the logs from the pod:

```
$ kubectl logs simple-http
Serving HTTP on 0.0.0.0 port 8080 ...
```

To get more information about pod execute the following command:

```
$ kubectl describe pod simple-http
```
### Create Service

 You need to create a Service to proxy the traffic to the simple-http pod.

 Service is a Kubernetes abstraction which defines a logical set of pods and a policy by which to access them. It is effectively a named load balancer that proxies traffic to one or more pods. When you set up a service, you tell it the pods to proxy based on pod labels.

Take a look at the `http-service.yaml` manifest file describing a Service resource for the simple-http pod:

 ```
 apiVersion: v1
 kind: Service
 metadata:
   name: simple-http-service
   labels:
     name: simple-http-service
 spec:
   type: "NodePort"
   ports:
     # the port that this service should serve on
   - port: 8080
   selector:
     name: simple-http
 ```
 This manifest file creates a Service named simple-http-service with a label selector: `simple-http`.
 These label match the set of labels that are deployed in the previous step. Therefore, this service routes the network traffic to the simple-http pod created in previous step.

 The ports section of the manifest declares a single port mapping. In this case, the Service will route the traffic on port: 8080 to the random node port of the containers that match the specified selector labels.

 Start up the service by running:
```
$ kubectl create -f http-service.yaml
```

Verify that the service is created:

```
$ kubectl get services
NAME                  CLUSTER-IP   EXTERNAL-IP   PORT(S)          AGE
kubernetes            10.0.0.1     <none>        443/TCP          1h
simple-http-service   10.0.0.109   <nodes>       8080:32499/TCP   3s
```

In this case the `simple-http-service` listening on port `32499` on minikube machine.
Try to call this WEB server:

```
$ curl 192.168.99.100:32499
172.17.0.6
```

As you can see it shows container IP address.

Now delete the pod and the service:

```
$ kubectl delete -f http-service.yaml
$ kubectl delete -f http-pod.yaml
```
For some period of time it will be in `Terminating` state

```
$ kubectl get pods
NAME          READY     STATUS        RESTARTS   AGE
simple-http   1/1       Terminating   0          27m
```

## Create ReplicationController

Now we are going replace Pod with ReplicationController and Deployment.
Deployments are a newer and higher level concept than Replication Controllers. They manage the deployment of Replica Sets (also a newer concept, but pretty much equivalent to Replication Controllers), and allow for easy updating of a Replica Set as well as the ability to roll back to a previous deployment.

Use the manifest file named `http-rc.yaml` to deploy the container. This manifest file specifies a ReplicationController that runs a multiple instances of image `zreigz/simple-http-pod`:

```
apiVersion: v1
kind: ReplicationController
metadata:
  name: simple-http
spec:
  replicas: 3
  selector:
    name: simple-http
  template:
    metadata:
      name: simple-http
      labels:
        name: simple-http
    spec:
      containers:
      - name: simple-http-pod
        image: zreigz/simple-http-pod
        ports:
        - containerPort: 8080
```

Run the following command to deploy the ReplicationController:

```
$ kubectl create -f http-rc.yaml
replicationcontroller "simple-http" created
```
Verify that the pods are running `kubectl get pods`:

```
$ kubectl get pods
NAME                READY     STATUS    RESTARTS   AGE
simple-http-351rz   1/1       Running   0          1m
simple-http-dnr2v   1/1       Running   0          1m
simple-http-sb4dn   1/1       Running   0          1m
```

### Create Service
You need to create a Service to proxy the traffic to the simple-http pod instances.
Start up the service by running:

```
$ kubectl create -f http-service.yaml
service "simple-http-service" created
```
Verify that the service is created:

```
$ kubectl get services
NAME                  CLUSTER-IP   EXTERNAL-IP   PORT(S)          AGE
kubernetes            10.0.0.1     <none>        443/TCP          2h
simple-http-service   10.0.0.24    <nodes>       8080:32052/TCP   46s
```
In this case the simple-http-service listening on port 32052 on minikube machine and redirect randomly traffic to existing pods.

Execute the following commands to see how load balancer works.

```
$ curl 192.168.99.100:32052
172.17.0.6
$ curl 192.168.99.100:32052
172.17.0.8
$ curl 192.168.99.100:32052
172.17.0.7
$ curl 192.168.99.100:32052
172.17.0.7
$ curl 192.168.99.100:32052
172.17.0.8
```
Now delete the rc and the service:

```
$ kubectl delete -f http-service.yaml
$ kubectl delete -f http-rc.yaml
```

## Create Deployment

Use the manifest file named `http-deployment.yaml` to deploy the container. This manifest file specifies a Deployment that runs a multiple instances of image `zreigz/simple-http-pod`:

```
apiVersion: extensions/v1beta1
kind: Deployment
metadata:
  name: simple-http
spec:
  replicas: 3
  template:
    metadata:
      name: simple-http
      labels:
        name: simple-http
    spec:
      containers:
      - name: simple-http-pod
        image: zreigz/simple-http-pod
        ports:
        - containerPort: 8080
```

Run the following command to deploy the pods:

```
$ kubectl create -f http-deployment.yaml
deployment "simple-http" created
```
Verify that the pods are running `kubectl get pods`:

```
$ kubectl get pods
NAME                           READY     STATUS    RESTARTS   AGE
simple-http-1250675604-13mff   1/1       Running   0          39s
simple-http-1250675604-l64tj   1/1       Running   0          39s
simple-http-1250675604-rl127   1/1       Running   0          39s
```

### Create Service
You need to create a Service to proxy the traffic to the simple-http pod instances.
Start up the service by running:

```
$ kubectl create -f http-service.yaml
service "simple-http-service" created
```
Verify that the service is created:

```
$ kubectl get services
NAME                  CLUSTER-IP   EXTERNAL-IP   PORT(S)          AGE
kubernetes            10.0.0.1     <none>        443/TCP          2h
simple-http-service   10.0.0.105   <nodes>       8080:31781/TCP   15s

```
In this case the simple-http-service listening on port 31781 on minikube machine and redirect randomly traffic to existing pods.

Execute the following commands to see how load balancer works.

```
$ curl 192.168.99.100:31781
172.17.0.9
$ curl 192.168.99.100:31781
172.17.0.10
$ curl 192.168.99.100:31781
172.17.0.10
$ curl 192.168.99.100:31781
172.17.0.11
$ curl 192.168.99.100:31781
172.17.0.9
```

Now delete the deployment and the service:

```
$ kubectl delete -f http-service.yaml
$ kubectl delete -f http-deployment.yaml
```
