# Installing kubeadm

Before your installation please execute the following command:

```
$ sudo iptables -P FORWARD ACCEPT
```

## Installing kubelet and kubeadm

You will install these packages on all of your machines:

* kubelet: the most core component of Kubernetes. It runs on all of the machines in your cluster and does things like starting pods and containers.

* kubeadm: the command to bootstrap the cluster.

```
$ sudo su
# apt-get update && apt-get install -y apt-transport-https
# curl -s https://packages.cloud.google.com/apt/doc/apt-key.gpg | apt-key add -
# cat <<EOF >/etc/apt/sources.list.d/kubernetes.list
deb http://apt.kubernetes.io/ kubernetes-xenial main
EOF
# apt-get update

# apt-get install -y kubelet kubeadm kubectl kubernetes-cni

```
## Initializing your master

The master is the machine where the “control plane” components run, including etcd (the cluster database) and the API server (which the kubectl CLI communicates with).

To initialize the master, pick one of the machines you previously installed kubeadm on, and run:

```
# kubeadm init --apiserver-advertise-address=<ip-address> --pod-network-cidr=10.244.0.0/16
# exit
```

When installation is finished:

```
  $ mkdir -p $HOME/.kube
  $ sudo cp -i /etc/kubernetes/admin.conf $HOME/.kube/config
  $ sudo chown $(id -u):$(id -g) $HOME/.kube/config
```
## Master Isolation

By default, your cluster will not schedule pods on the master for security reasons. If you want to be able to schedule pods on the master, e.g. a single-machine Kubernetes cluster for development, run:

```
$ kubectl taint nodes --all node-role.kubernetes.io/master-
```

## Installing a pod network

You must install a pod network add-on so that your pods can communicate with each other.

```
kubectl apply -f https://raw.githubusercontent.com/coreos/flannel/master/Documentation/kube-flannel-rbac.yml
kubectl apply -f https://raw.githubusercontent.com/coreos/flannel/master/Documentation/kube-flannel.yml
```

Restart kubelet service and docker

```
$ sudo systemctl restart kubelet
$ sudo systemctl restart docker
$ sudo systemctl restart kubelet
```
Get info about your cluster

```
$ kubectl get nodes
NAME       STATUS    AGE       VERSION
minikube   Ready     9m        v1.5.2
```
or

```
$ kubectl describe node minikube
```

### How do I test if it is working?

Create a simple Pod to use as a test environment
```
kubectl create -f https://raw.githubusercontent.com/zreigz/kubernetes-workshop/master/installation-kubeadm/busybox.yaml
```

Wait for this pod to go into the running state
You can get its status with:
```
kubectl get pods busybox
```
You should see:
```
NAME      READY     STATUS    RESTARTS   AGE
busybox   1/1       Running   0          <some-time>
```
Validate that DNS is working
Once that pod is running, you can exec nslookup in that environment:
```
kubectl exec -ti busybox -- nslookup kubernetes.default.svc.cluster.local
```
You should see something like:
```
Server:    10.0.0.10
Address 1: 10.0.0.10

Name:      kubernetes.default
Address 1: 10.0.0.1
```

If you see that, DNS is working correctly.

## Overview of kubectl
`kubectl` is a command line interface for running commands against Kubernetes clusters.

### Syntax

Use the following syntax to run `kubectl` commands from your terminal window:

```
kubectl [command] [TYPE] [NAME] [flags]
```
where command, TYPE, NAME, and flags are:
* command: Specifies the operation that you want to perform on one or more resources, for example *create*, *get, *describe*, *delete*.
* TYPE: Specifies the resource type. Resource types are case-sensitive and you can specify the singular, plural, or abbreviated forms.
* NAME: Specifies the name of the resource. Names are case-sensitive. 
* flags: Specifies optional flags

### Examples: Common operations

```
// Create a service using the definition in example-service.yaml.
$ kubectl create -f example-service.yaml

// List all pods in plain-text output format.
$ kubectl get pods

// Display the details of the node with name <node-name>.
$ kubectl describe nodes <node-name>

// Delete a pod using the type and name specified in the pod.yaml file.
$ kubectl delete -f pod.yaml

// Get an interactive TTY and run /bin/bash from pod <pod-name>. By default, output is from the first container.
$ kubectl exec -ti <pod-name> /bin/bash

// Return a snapshot of the logs from pod <pod-name>.
$ kubectl logs <pod-name>

```

