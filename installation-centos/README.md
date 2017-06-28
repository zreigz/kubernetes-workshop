# CentOS
## 1 Kubeadm (recommended)

```
cat <<EOF > /etc/yum.repos.d/kubernetes.repo
[kubernetes]
name=Kubernetes
baseurl=http://yum.kubernetes.io/repos/kubernetes-el7-x86_64
enabled=1
gpgcheck=1
repo_gpgcheck=1
gpgkey=https://packages.cloud.google.com/yum/doc/yum-key.gpg
        https://packages.cloud.google.com/yum/doc/rpm-package-key.gpg
EOF

setenforce 0

# 1.5.4 is the latest previous version in the repo.  Because of messed up
# versioning in the 1.5 release, kubeadm is no longer indexed in the repos
# so we have to refer to the RPM directly.
sudo yum -y install \
  yum-versionlock \
  docker \
  kubectl-1.5.4-0 \
  kubelet-1.5.4-0 \
  kubernetes-cni-0.3.0.1-0.07a8a2 \
  http://yum.kubernetes.io/pool/082436e6e6cad1852864438b8f98ee6fa3b86b597554720b631876db39b8ef04-kubeadm-1.6.0-0.alpha.0.2074.a092d8e0f95f52.x86_64.rpm

# Lock the version of these packages so that we don't upgrade them accidentally.
sudo yum versionlock add kubectl kubelet kubernetes-cni kubeadm

# Enable and start up docker and the kubelet
systemctl enable docker && systemctl start docker
systemctl enable kubelet && systemctl start kubelet

```
### Initializing your master

The master is the machine where the “control plane” components run, including etcd (the cluster database) and the API server (which the kubectl CLI communicates with).

To initialize the master, pick one of the machines you previously installed kubeadm on, and run:
```
$ kubeadm init --apiserver-advertise-address=<ip-address> --pod-network-cidr=10.244.0.0/16
```

Check to make sure the cluster can see the node (on centos-master)
```
$ kubectl get nodes
NAME                   STATUS     AGE     VERSION
127.0.0.1              Ready      3d      v1.6.0+fff5156
```

## 2 Manual installation
*Skip this when you installed kubernetes in step 1*
### Prepare the host
Create a `/etc/yum.repos.d/virt7-docker-common-release.repo` on host - centos with following information.
```
[virt7-docker-common-release]
name=virt7-docker-common-release
baseurl=http://cbs.centos.org/repos/virt7-docker-common-release/x86_64/os/
gpgcheck=0
```
Install Kubernetes, etcd and flannel on hosts - centos. This will also pull in docker and cadvisor.
```
yum -y install --enablerepo=virt7-docker-common-release kubernetes etcd flannel
```
Edit /etc/kubernetes/config which will be the same on all hosts to contain:
```
###
# kubernetes system config
#
# The following values are used to configure various aspects of all
# kubernetes services, including
#
#   kube-apiserver.service
#   kube-controller-manager.service
#   kube-scheduler.service
#   kubelet.service
#   kube-proxy.service
# logging to stderr means we get it in the systemd journal
KUBE_LOGTOSTDERR="--logtostderr=true"

# journal message level, 0 is debug
KUBE_LOG_LEVEL="--v=0"

# Should this cluster be allowed to run privileged docker containers
KUBE_ALLOW_PRIV="--allow-privileged=false"

# How the controller-manager, scheduler, and proxy find the apiserver
KUBE_MASTER="--master=http://127.0.0.1:8080"

```

Disable the firewall on the master and all the nodes, as docker does not play well with other firewall rule managers. CentOS won’t let you disable the firewall as long as SELinux is enforcing, so that needs to be disabled first.
```
setenforce 0
systemctl disable iptables-services firewalld
systemctl stop iptables-services firewalld
```

### Configure the Kubernetes services on the master.
Edit /etc/etcd/etcd.conf to appear as such:

```
# [member]
ETCD_NAME=default
ETCD_DATA_DIR="/var/lib/etcd/default.etcd"
ETCD_LISTEN_CLIENT_URLS="http://0.0.0.0:2379"

#[cluster]
ETCD_ADVERTISE_CLIENT_URLS="http://0.0.0.0:2379"

```


Edit /etc/kubernetes/apiserver to appear as such:
```
KUBE_API_ADDRESS="--address=0.0.0.0"
KUBE_API_PORT="--port=8080"
KUBELET_PORT="--kubelet_port=10250"
KUBE_ETCD_SERVERS="--etcd_servers=http://127.0.0.1:2379"
KUBE_SERVICE_ADDRESSES="--service-cluster-ip-range=10.254.0.0/16"
KUBE_ADMISSION_CONTROL="--admission_control=NamespaceLifecycle,NamespaceExists,LimitRanger,SecurityContextDeny,ResourceQuota"
KUBE_API_ARGS=""

```
Configure flannel to overlay Docker network in /etc/sysconfig/flanneld on the master (also in the nodes as we’ll see):

```
# Flanneld configuration options

# etcd url location.  Point this to the server where etcd runs
FLANNEL_ETCD_ENDPOINTS="http://127.0.0.1:2379"

# etcd config key.  This is the configuration key that flannel queries
# For address range assignment
FLANNEL_ETCD_PREFIX="/kube-centos/network"


# Any additional options that you want to pass
#FLANNEL_OPTIONS=""

```

Start ETCD and configure it to hold the network overlay configuration on master: Warning This network must be unused in your network infrastructure! 172.30.0.0/16 is free in our network.
```
systemctl start etcd
etcdctl mkdir /kube-centos/network
etcdctl mk /kube-centos/network/config "{ \"Network\": \"172.30.0.0/16\", \"SubnetLen\": 24, \"Backend\": { \"Type\": \"vxlan\" } }"
```

Start the appropriate services on master:
```
for SERVICES in etcd kube-apiserver kube-controller-manager kube-scheduler flanneld; do
    systemctl restart $SERVICES
    systemctl enable $SERVICES
    systemctl status $SERVICES
done
```

Edit /etc/kubernetes/kubelet to appear as such:

```
###
# kubernetes kubelet (minion) config

# The address for the info server to serve on (set to 0.0.0.0 or "" for all interfaces)
KUBELET_ADDRESS="--address=0.0.0.0"

# The port for the info server to serve on
# KUBELET_PORT="--port=10250"

# You may leave this blank to use the actual hostname
KUBELET_HOSTNAME="--hostname-override=127.0.0.1"

# location of the api-server
KUBELET_API_SERVER="--api-servers=http://127.0.0.1:8080"

# pod infrastructure container
KUBELET_POD_INFRA_CONTAINER="--pod-infra-container-image=registry.access.redhat.com/rhel7/pod-infrastructure:latest"

# Add your own!
KUBELET_ARGS="--cluster-dns=10.254.254.254 --cluster-domain=cluster.local"

```

Start the appropriate services on node.
```
for SERVICES in kube-proxy kubelet flanneld docker; do
    systemctl restart $SERVICES
    systemctl enable $SERVICES
    systemctl status $SERVICES
done
```

Configure kubectl

```
kubectl config set-cluster default-cluster --server=http://127.0.0.1:8080
kubectl config set-context default-context --cluster=default-cluster --user=default-admin
kubectl config use-context default-context
```
Check to make sure the cluster can see the node (on centos-master)
```
$ kubectl get nodes
NAME                   STATUS     AGE     VERSION
127.0.0.1              Ready      3d      v1.6.0+fff5156
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
