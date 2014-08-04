#!/bin/bash
DOMAIN=$1
echo "domain: DOMAIN"
/usr/local/psa/admin/bin/httpdmng --reconfigure-domain $1 
#/usr/local/psa/admin/bin/httpdmng --reconfigure-domain gentchannel.com
