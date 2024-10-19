# freepbx-ucp-openid
OpenID-Connect support for the FreePBX UCP with keycloak example based on [jumbojett/openid-connect-php](https://github.com/jumbojett/OpenID-Connect-PHP)\
\
You can use this script to add oidc support to your freepbx user control panel.
The UCP-User to be used must be defined in your openid-provider as an attribute which holds the uid (id not login-name) of the freepbx-user. The name of this attribute can be configured as 'ucpUserAttr'.

## Installation
- Get on your FreePBX via SSH
- su asterisk
- cd /var/www/html/ucp
- git clone https://github.com/helmut1337/freepbx-ucp-openid.git
- Open up open-index.php and edit open-id configuration
- chmod +x install_openid.sh
- ./install_openid.sh

### What does install_open.sh do?
- Downloads composer
- installs php dependency for openid ([jumbojett/openid-connect-php](https://github.com/jumbojett/OpenID-Connect-PHP))
- patches the .htaccess file to use openid-index.php instead of index.php as document-root
- removes composer

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

Dont forget to add an user-attribute named "pbx-user" in the keycloak realm-settings and add it via mappers to the openid scope.