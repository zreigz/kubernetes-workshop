# Installing kubeadm

## Installing kubectl

On each of your machines, install kubectl. You only need kubectl on the master, but it can be useful to have on the other nodes as well.

```
$ curl -LO https://storage.googleapis.com/kubernetes-release/release/$(curl -s https://storage.googleapis.com/kubernetes-release/release/stable.txt)/bin/linux/amd64/kubectl

$ chmod +x ./kubectl
$ sudo mv ./kubectl /usr/local/bin/kubectl
```

## Installing kubelet and kubeadm

You will install these packages on all of your machines:

* kubelet: the most core component of Kubernetes. It runs on all of the machines in your cluster and does things like starting pods and containers.

* kubeadm: the command to bootstrap the cluster.

```
$ apt-get update && apt-get install -y apt-transport-https
$ curl -s https://packages.cloud.google.com/apt/doc/apt-key.gpg | apt-key add -
$ cat <<EOF >/etc/apt/sources.list.d/kubernetes.list
deb http://apt.kubernetes.io/ kubernetes-xenial main
EOF
$ apt-get update

$ apt-get install -y kubelet kubeadm kubectl kubernetes-cni

```
## Initializing your master

The master is the machine where the “control plane” components run, including etcd (the cluster database) and the API server (which the kubectl CLI communicates with).

To initialize the master, pick one of the machines you previously installed kubeadm on, and run:

```
$ kubeadm init --apiserver-advertise-address=<ip-address> --pod-network-cidr=10.244.0.0/16
```

When installation is finished:

```
$ sudo cp /etc/kubernetes/admin.conf $HOME/
$ sudo chown $(id -u):$(id -g) $HOME/admin.conf
$ export KUBECONFIG=$HOME/admin.conf
```
## Master Isolation

By default, your cluster will not schedule pods on the master for security reasons. If you want to be able to schedule pods on the master, e.g. a single-machine Kubernetes cluster for development, run:

```
kubectl taint nodes --all node-role.kubernetes.io/master-
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

