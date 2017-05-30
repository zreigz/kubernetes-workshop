# Quickstart

First install virtualbox on your machine. 

```
$ sudo apt-add-repository "deb http://download.virtualbox.org/virtualbox/debian $(lsb_release -sc) contrib"
```
Add secure key:

```
$ wget -q https://www.virtualbox.org/download/oracle_vbox.asc -O- | sudo apt-key add -
```

Install VirtualBox:

```
$ sudo apt-get update
$ sudo apt-get install virtualbox
```

When your VirtualBox is ready you can install minikube:

```
$ git clone https://github.com/zreigz/kubernetes-workshop.git
$ cd kubernetes-workshop/installation
$ ./install.sh
```

When script is finished then you can start minikube:

```
$ minikube start
Starting local Kubernetes v1.6.0 cluster...
Starting VM...
SSH-ing files into VM...
Setting up certs...
Starting cluster components...
Connecting to cluster...
Setting up kubeconfig...
Kubectl is now configured to use the cluster.
```

