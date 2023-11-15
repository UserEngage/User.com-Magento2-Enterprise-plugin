#!/bin/bash

cd /var/www/ || exit
echo "Killing old consumers..."
pkill -f queue:consumers:start

echo "Starting consumers..."
echo "start usercom.customer.sync"
bin/magento queue:consumers:start usercom.customer.sync > /var/www/var/log/consumers.log 2>&1 &
echo "start usercom.customer.login"
bin/magento queue:consumers:start usercom.customer.login > /var/www/var/log/consumers.log 2>&1 &
echo "start usercom.customer.register"
bin/magento queue:consumers:start usercom.customer.register > /var/www/var/log/consumers.log 2>&1 &
echo "start usercom.customer.newsletter"
bin/magento queue:consumers:start usercom.customer.newsletter > /var/www/var/log/consumers.log 2>&1 &
echo "start usercom.customer.product.view"
bin/magento queue:consumers:start usercom.catalog.product.event > /var/www/var/log/consumers.log 2>&1 &
echo "start usercom.cart.product.add"
bin/magento queue:consumers:start usercom.cart.product.add > /var/www/var/log/consumers.log 2>&1 &
echo "start usercom.cart.product.remove"
bin/magento queue:consumers:start usercom.cart.product.remove > /var/www/var/log/consumers.log 2>&1 &
echo "start usercom.cart.checkout"
bin/magento queue:consumers:start usercom.cart.checkout > /var/www/var/log/consumers.log 2>&1 &
echo "start usercom.order.purchase"
bin/magento queue:consumers:start usercom.order.purchase > /var/www/var/log/consumers.log 2>&1 &

echo "Consumers started"
tail -f /var/www/var/log/consumers.log
