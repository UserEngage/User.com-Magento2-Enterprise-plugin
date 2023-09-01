# User.com Magento 2 plugin

Official Magento 2 EE module for User.com integration. This tag will implement User.com on your Website and synchronize your 

## Requirements
Magento 2.3.4 version or higher  
PHP 7.4 or higher

## Installation process
1) Create app/code/Usercom/Analytics/ folder
```code
mkdir -p app/code/Usercom/Analytics/
```
2) Move to the app/code/Usercom/Analytics folder
```code
cd app/code/Usercom/Analytics/
```
3) Download the plugin
```code
git clone https://github.com/UserEngage/User.com-Magento2-Enterprise-plugin.git .
```
4) Move to the main Magento folder
```code
cd ../../../../
```
5) Update Magento config
```code
 bin/magento s:up && bin/magento s:d:c && bin/magento s:sta:d -f && bin/magento c:c && bin/magento c:f
 ```
## Configuration

### Required configuration data
- **API Key**
	- You can find it in your `Application -> Settings -> Setup & Integrations`
- **Application Domain**
	- You can find it in your `Application -> Settings -> Setup & Integrations`
- **REST API Key**
	- You can find it in your `Application -> Settings -> App Settings -> Advanced -> Public REST API keys`
 
## Functionality
1. Installation of widget tracking code on every webpage of your Magento app.
2. Gather data from login/registration forms and send it to the User.com app.

## Developers' note
- usercom_user_id - user identifier created inside the plugin to identify users on user.com
- usercom_key - user identifier automatically created by User.com widget

## LICENSE

[Apache 2.0 License](https://github.com/UserEngage/User.com-Magento2-Enterprise-plugin/blob/master/LICENSE.md)
