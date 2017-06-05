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
cat <<EOF >/etc/apt/sources.list.d/kubernetes.list
deb http://apt.kubernetes.io/ kubernetes-xenial main
EOF
$ apt-get update

$ apt-get install -y kubelet kubeadm kubectl kubernetes-cni

```
