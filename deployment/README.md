# Deployment

## Hands-On
Let’s create a Deployment with the following deployment yaml file `http-deployment.yaml` and service `http-service.yaml`.

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

```
$ kubectl create -f http-service.yaml
service "simple-http-service" created
```
and 
```
$ kubectl apply -f http-deployment.yaml --record

```
Use kubectl to retrieve the current status of deployment.

```
$kubectl get deployment
NAME          DESIRED   CURRENT   UP-TO-DATE   AVAILABLE   AGE
simple-http   3         3         3            3           2m

```
Deployment manages Replica Sets and Replica Set manages Pods.

```
$ kubectl get rs
NAME                     DESIRED   CURRENT   READY     AGE
simple-http-6c8dbd6548   3         3         3         3m

```
And the Replica Set will create pods after its been created.

```
$ kubectl get pods
NAME                           READY     STATUS    RESTARTS   AGE
simple-http-6c8dbd6548-256k9   1/1       Running   0          3m
simple-http-6c8dbd6548-f8slz   1/1       Running   0          3m
simple-http-6c8dbd6548-vrr9n   1/1       Running   0          3m

```

## Rolling Update
In order to support rolling update, we need to configure the update strategy first.

So we add following part into spec
```
minReadySeconds: 5
strategy:
  # indicate which strategy we want for rolling update
  type: RollingUpdate
  rollingUpdate:
    maxSurge: 1
    maxUnavailable: 1
```
This part was introduced to `http-rolling-update.yaml` file. Additionaly the image version was changed for `development`. Currently service return the image IP address.
Let's check the service IP address:
```
$ kubectl get svc
NAME                  CLUSTER-IP       EXTERNAL-IP   PORT(S)          AGE
kubernetes            10.96.0.1        <none>        443/TCP          1h
simple-http-service   10.100.203.159   <nodes>       8080:31401/TCP   8m
```

Now check the result:

```
$ curl 10.100.203.159:8080
10.244.0.43

```

Lets apply the new http-rolling-update.yaml

```
$ kubectl apply -f http-rolling-update.yaml --record
```
Check the status:

```
$ kubectl rollout status deployment simple-http
Waiting for rollout to finish: 2 out of 3 new replicas have been updated...
Waiting for rollout to finish: 2 out of 3 new replicas have been updated...
Waiting for rollout to finish: 2 out of 3 new replicas have been updated...
Waiting for rollout to finish: 2 out of 3 new replicas have been updated...
Waiting for rollout to finish: 1 old replicas are pending termination...
Waiting for rollout to finish: 1 old replicas are pending termination...
Waiting for rollout to finish: 1 old replicas are pending termination...
deployment "simple-http" successfully rolled out
```

Check if pods use new version of container `zreigz/simple-http-pod:development`:

```
$ curl 10.100.203.159:8080
10.244.0.51
I'm in development stage

```
Yes it works, additional message `I'm in development stage` was displayed.

## Rollback
After the image update the service become unstable you may want to go back to the previous version.
At previous part, the parameter --record comes with command let the Kubernetes record the command you typed, so that you can distinguish between the revisions.

```
kubectl rollout history deployment simple-http
deployments "simple-http"
REVISION	CHANGE-CAUSE
1		kubectl apply --filename=http-deployment.yaml --record=true
2		kubectl apply --filename=http-deployment.yaml --record=true

```
Now you can easly undo the changes:

```
$ kubectl rollout undo deployment simple-http
deployment "simple-http" rolled back

```

Check the status:

```
$ kubectl rollout status deployment simple-http
Waiting for rollout to finish: 2 out of 3 new replicas have been updated...
Waiting for rollout to finish: 2 out of 3 new replicas have been updated...
Waiting for rollout to finish: 2 out of 3 new replicas have been updated...
Waiting for rollout to finish: 2 out of 3 new replicas have been updated...
Waiting for rollout to finish: 2 out of 3 new replicas have been updated...
Waiting for rollout to finish: 2 out of 3 new replicas have been updated...
Waiting for rollout to finish: 1 old replicas are pending termination...
Waiting for rollout to finish: 1 old replicas are pending termination...
Waiting for rollout to finish: 1 old replicas are pending termination...
deployment "simple-http" successfully rolled out

```

Now you should see the old result for command:

```
$ curl 10.100.203.159:8080
10.244.0.75

```
You can also go to specific revision:

```
# to specific revision
$ kubectl rollout undo deployment <deployment> --to-revision=<revision>
```
