<?php

session_start();
require_once('vendor/autoload.php');

$oidcConfig = [
    'mainUrl' => '<URL-TO-UCP>',
    'issuer' => '<OPENID-ISSUER>',
    'cid' => '<OPENID-CLIENT-ID>',
    'secret' => '<OPENID-CLIENT-SECRET>',
    'ucpUserAttr' => '<OPENID-UCP-USER-ATTR>',
    // Optional settings
    //'additionalScopes' => [],
    //'customRedirectUrl' => '<OPENID-CUSTOM-REDIRECT-URL>>'
];

class UcpOpenIdWrapper
{
    private bool|mysqli $conn;
    private ?Jumbojett\OpenIDConnectClient $oidc;

    public function __construct(private readonly array $config)
    {
        $configFile = __DIR__ . '/freepbx-cfg-clone.php';
        if (!file_exists($configFile)) {
            $orgConfigFile = file_get_contents('/etc/freepbx.conf');
            $lines = explode("\n", $orgConfigFile);
            $lines[count($lines) - 2] = 'return $amp_conf;';
            $newString = implode("\n", $lines);
            $newString = str_replace('$amp_conf', '$db_conf', $newString);
            file_put_contents($configFile, $newString);
        }
    }

    public function auth(): bool
    {
        if (isset($_SESSION['QUERY_STRING']) && $_SESSION['QUERY_STRING']) {
            return true;
        }

        if (!array_key_exists('UCP_token', $_SESSION)) {
            $this->initOidc();

            $this->oidc->authenticate();
            $_SESSION['oidc_id_token'] = $this->oidc->getIdToken();
            $ucpUid = $this->oidc->requestUserInfo($this->config['ucpUserAttr']);

            if (!$ucpUid) {
                echo "No user found for uid";
                die();
            }

            $this->connectDb();
            $testToken = $this->generateToken();
            $this->saveSessionToken($testToken, $ucpUid);
            //$this->saveSessionToken($testToken, "8");
            $this->setSessionToken($testToken);
            $this->conn->close();
            header("Location: {$this->getMainUrl()}");

            return false;
        }

        // if UCP_token is set, but empty, show logout success page an remove token to make reauth possible
        if (!isset($_SESSION['UCP_token']) && array_key_exists('UCP_token', $_SESSION)) {
            unset($_SESSION['UCP_token']);
            try {
                $this->initOidc();
                $this->oidc->signOut($_SESSION['oidc_id_token'], null);
            } catch (Exception $e) {
                echo "ok fehler";
            }

            return false;
        }

        return true;
    }

    public function getMainUrl(): string
    {
        return $this->config['mainUrl'];
    }

    private function initOidc(): void
    {
        if (!isset($this->config['issuer']) || !isset($this->config['cid']) || !isset($this->config['secret'])) {
            throw new Exception('invalid config');
        }
        $this->oidc = new Jumbojett\OpenIDConnectClient($this->config['issuer'], $this->config['cid'], $this->config['secret']);
        if (isset($this->config['additionalScopes']) && is_array($this->config['additionalScopes'])) {
            $this->oidc->addScope($this->config['additionalScopes']);
        }
        if (isset($this->config['customRedirectUrl'])) {
            $this->oidc->setRedirectURL($this->config['customRedirectUrl']);
        }
    }

    private function connectDb(): void
    {
        $dbConfig = require('freepbx-cfg-clone.php');

        $dbHost = $dbConfig['AMPDBHOST'];
        $dbUsername = $dbConfig['AMPDBUSER'];
        $dbPassword = $dbConfig['AMPDBPASS'];
        $dbName = $dbConfig['AMPDBNAME'];

        $this->conn = new mysqli($dbHost, $dbUsername, $dbPassword);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        if (!$this->conn->select_db($dbName)) {
            die("DB select failed");
        }
    }

    private function generateToken(): string
    {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    private function saveSessionToken($token, $uid): void
    {
        $sql = "INSERT INTO ucp_sessions (session, uid, address, time) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE time = VALUES(time)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array($token, $uid, $_SERVER['REMOTE_ADDR'], time()));
    }

    private function setSessionToken($token): void
    {
        $_SESSION['UCP_token'] = $token;
    }
}

$openIdWrapper = new UcpOpenIdWrapper($oidcConfig);
if ($openIdWrapper->auth()) {
    include('index.php');
    $currentUser = $GLOBALS['user'];
    if (!$currentUser) {
        unset($_SESSION['UCP_token']);
        echo "Session expired, refreshing from OIDC session...<meta http-equiv='refresh' content='2; URL={$openIdWrapper->getMainUrl()}'>";
        return;
    }
}
