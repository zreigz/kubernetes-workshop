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
The `minikube start` command can be used to start your cluster. This command creates and configures a virtual machine that runs a single-node Kubernetes cluster. This command also configures your `kubectl` installation to communicate with this cluster.

## Managing your Cluster

```
$ minikube --help
Minikube is a CLI tool that provisions and manages single-node Kubernetes clusters optimized for development workflows.

Usage:
  minikube [command]

Available Commands:
  addons           Modify minikube's kubernetes addons
  completion       Outputs minikube shell completion for the given shell (bash)
  config           Modify minikube config
  dashboard        Opens/displays the kubernetes dashboard URL for your local cluster
  delete           Deletes a local kubernetes cluster.
  docker-env       sets up docker env variables; similar to '$(docker-machine env)'
  get-k8s-versions Gets the list of available kubernetes versions available for minikube.
  ip               Retrieve the IP address of the running cluster.
  logs             Gets the logs of the running localkube instance, used for debugging minikube, not user code.
  service          Gets the kubernetes URL(s) for the specified service in your local cluster
  ssh              Log into or run a command on a machine with SSH; similar to 'docker-machine ssh'
  start            Starts a local kubernetes cluster.
  status           Gets the status of a local kubernetes cluster.
  stop             Stops a running local kubernetes cluster.
  version          Print the version of minikube.

```
First check avaiable kubernetes addons

```
$ minikube addons list
- ingress: disabled
- registry-creds: disabled
- addon-manager: enabled
- dashboard: enabled
- kube-dns: enabled
- heapster: disabled
```
Now enable `heapster` to get access to kubernetes metrics.

```
$ minikube addons enable heapster
heapster was successfully enabled
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


