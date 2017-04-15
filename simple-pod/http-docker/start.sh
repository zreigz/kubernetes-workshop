#!/bin/bash

DEFAULT_IP_ADDRESS=$(ip -o -4 addr list $(ip -o -4 route show to default | awk '{print $5}' | head -1) | awk '{print $4}' | cut -d/ -f1 | head -1)

echo $DEFAULT_IP_ADDRESS >> index.html

python -m SimpleHTTPServer 8080

