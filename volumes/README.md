# Volumes

A volume is a directory, possibly with some data in it, accessible to a container as part of its filesystem. Volumes are used used, for example, to store stateful app data.

For the purposes of this example, let's look at one of volumes types: `hostPath`.

A `hostPath` volume mounts a file or directory from the host node's filesystem into your pod. It can be used, for example, to provide HTML files for the nginx.

Use the manifest file named `nginx.yaml` to deploy the pod with volume.

```
apiVersion: v1
kind: Pod
metadata:
  name: nginx
  labels:
    name: nginx
spec:
  containers:
  - name: nginx
    image: nginx
    ports:
    - containerPort: 80
      name: http
      protocol: TCP
    volumeMounts:
    - mountPath: /usr/share/nginx/html
      name: nginx-vol
  volumes:
    - name: nginx-vol
      hostPath:
        path: /data
```
With the podr configured like this, the nginx container will serve HTML files from the host mounted volume.

Run the following command to deploy the pod:

```
$ kubectl create -f nginx.yaml
pod "nginx" created
```

Verify that the pod is running `kubectl get pods`:

```
$ kubectl get pods
NAME                           READY     STATUS        RESTARTS   AGE
nginx                          1/1       Running       0          1m

```

Start up the service by running:
```
$ kubectl create -f nginx-service.yaml
```

Verify that the service is created:

```
$ kubectl get services
NAME                  CLUSTER-IP   EXTERNAL-IP   PORT(S)          AGE
kubernetes            10.0.0.1     <none>        443/TCP          4h
nginx                 10.0.0.226   <nodes>       80:32326/TCP     7s
```
Try to call the nginx server:

```
$ curl <server-ip>:32326

<html>
<head><title>403 Forbidden</title></head>
<body bgcolor="white">
<center><h1>403 Forbidden</h1></center>
<hr><center>nginx/1.13.1</center>
</body>
</html>
```

Now let's create some `index.html` file in shared directory.

```
$ sudo su
$ echo "Hello world" > /data/index.html
$ exit
```

Now you can again call nginx server:

```
$ curl <server-ip>:32326
Hello world
```

Now you can delete pod and service:

```
$ kubectl delete -f nginx.yaml
$ kubectl delete -f nginx-service.yaml
```

# Persistent Volumes

## Creating a PersistentVolume

In this exercise, you create a hostPath PersistentVolume. Kubernetes supports hostPath for development and testing on a single-node cluster. A hostPath PersistentVolume uses a file or directory on the Node to emulate network-attached storage.

Here is the configuration file for the hostPath PersistentVolume:

```
apiVersion: v1
kind: PersistentVolume
metadata:
  name: pv0001
spec:
  accessModes:
    - ReadWriteOnce
  capacity:
    storage: 5Gi
  hostPath:
    path: /data
```
The configuration file specifies that the volume is at /data on the the cluster’s Node. The configuration also specifies a size of 5 gibibytes and an access mode of ReadWriteOnce, which means the volume can be mounted as read-write by a single Node.


Create the PersistentVolume:

```
$ kubectl create -f pv.yaml
persistentvolume "pv0001" created
```

View information about the PersistentVolume:
```
$ kubectl get pv
NAME      CAPACITY   ACCESSMODES   RECLAIMPOLICY   STATUS      CLAIM     REASON    AGE
pv0001    5Gi        RWO           Retain          Available                       21s
```
The output shows that the PersistentVolume has a STATUS of Available. This means it has not yet been bound to a PersistentVolumeClaim.

## Creating a PersistentVolumeClaim

The next step is to create a PersistentVolumeClaim. Pods use PersistentVolumeClaims to request physical storage. In this exercise, you create a PersistentVolumeClaim that requests a volume of at least 1 gibibyte that can provide read-write access for at least one Node.

```
kind: PersistentVolumeClaim
apiVersion: v1
metadata:
  name: nginx-claim
spec:
  accessModes:
    - ReadWriteOnce
  resources:
    requests:
      storage: 1Gi
```

Create the PersistentVolumeClaim:

```
$ kubectl create -f pvc.yaml
```

After you create the PersistentVolumeClaim, the Kubernetes control plane looks for a PersistentVolume that satisfies the claim’s requirements. If the control plane finds a suitable PersistentVolume, it binds the claim to the volume.

Look again at the PersistentVolume:

```
$ kubectl get pv
NAME      CAPACITY   ACCESSMODES   RECLAIMPOLICY   STATUS    CLAIM                 REASON    AGE
pv0001    5Gi        RWO           Retain          Bound     default/nginx-claim             8s

```
Now the output shows a STATUS of Bound.

Look at the PersistentVolumeClaim:

```
$ kubectl get pvc
NAME          STATUS    VOLUME    CAPACITY   ACCESSMODES   AGE
nginx-claim   Bound     pv0001    5Gi        RWO           5m

```
## Creating a Pod

The next step is to create a Pod that uses your PersistentVolumeClaim as a volume.

Here is the configuration file for the Pod:

```
apiVersion: v1
kind: Pod
metadata:
  name: nginx
  labels:
    name: nginx
spec:
  containers:
  - name: nginx
    image: nginx
    ports:
    - containerPort: 80
      name: http
      protocol: TCP
    volumeMounts:
    - mountPath: /usr/share/nginx/html
      name: nginx-vol
  volumes:
    - name: nginx-vol
      persistentVolumeClaim:
        claimName: nginx-claim

```

Create this pod with service:

```
$ kubectl create -f nginx-claim.yaml
$ kubectl create -f nginx-service.yaml
```

Find port for nginx service:
```
$ kubectl get services
NAME                  CLUSTER-IP   EXTERNAL-IP   PORT(S)          AGE
kubernetes            10.0.0.1     <none>        443/TCP          4h
nginx                 10.0.0.139   <nodes>       80:31007/TCP     54s
```

The port is 31007

Try call the server:
```
$ curl <server-ip>:31007
Hello world
```
## Dynamic Volume Provisioning
### Prerequisites
Make sure that the DefaultStorageClass admission controller is enabled on the API server. Add start parments to kube-controller-manager: `--enable-hostpath-provisioner`

Edit `/etc/kubernetes/manifests/kube-controller-manager.yaml` file and add new line in `.spec.containers.command`:
```
...
    - --cluster-cidr=10.244.0.0/16
    - --enable-hostpath-provisioner
    - --node-cidr-mask-size=24
...

```
