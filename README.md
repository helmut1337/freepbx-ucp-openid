# freepbx-ucp-openid
OpenID-Connect support for the FreePBX UCP with keycloak example based on [jumbojett/openid-connect-php](https://github.com/jumbojett/OpenID-Connect-PHP)\
\
You can use this script to add oidc support to your freepbx user control panel.
The UCP-User to be used must be defined in your openid-provider as an attribute which holds the uid (id not login-name) of the freepbx-user. The name of this attribute can be configured as 'ucpUserAttr'.

Tested with Keycloak but should also work for Google, Apple, Microsoft or Github OIDC.
## Installation
- Get on your FreePBX via SSH and run:
```
su asterisk
cd /var/www/html
git clone https://github.com/helmut1337/freepbx-ucp-openid.git
```
- Open up open-index.php and edit open-id configuration:
```
cd freepbx-ucp-openid
nano freepbx-ucp-openid/openid-index.php
```
- For final install, run:
```
chmod +x install_openid.sh
./install_openid.sh
```
- done

### What does install_open.sh do?
- downloads composer.phar
- installs php dependency for openid ([jumbojett/openid-connect-php](https://github.com/jumbojett/OpenID-Connect-PHP))
- removed /var/www/html/ucp symlink
- moves itself to /var/www/html/ucp
- removes composer.phar

### Example Config for keycloak

```
$oidcConfig = [
    'mainUrl' => 'http://freepbx.local/ucp',
    'issuer' => 'https://<KEYCLOAK-HOST>/realms/master',
    'cid' => '<OPENID-CLIENT-ID>',
    'secret' => '<OPENID-CLIENT-SECRET>',
    'ucpUserAttr' => 'pbx-user',
    'customRedirectUrl' => 'http://freepbx.local/ucp'
];
```

Dont forget to add an user-attribute named "pbx-user" in the keycloak realm-settings and add it via mappers to the openid scope.\
The PHP-Script clones your /etc/freepbx.conf into /var/www/html/ucp/freepbx-cfg-clone.php because the freepbx.conf cannot be included without unwanted output.

## Uninstall
To remove just run:
```
su asterisk
cd /var/www/html
rm -rf ucp
ln -s admin/modules/ucp/htdocs ucp
```
