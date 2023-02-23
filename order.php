<?php

const TOKEN = '1749f0c2d9d3df79561a3bc90489e17d'; // Replace it with your own from your personal account
const ENDPOINT_1 = 'https://api.webvork.com/v1/new-lead';
const ENDPOINT_2 = 'https://api2.webvork.com/v1/new-lead';

if (!empty($_POST)) {
    $data = array(
        'token' => TOKEN,
        'offer_id' => !empty($_POST['offer_id']) ? trim($_POST['offer_id']) : '',
        'name' => !empty($_POST['name']) ? trim($_POST['name']) : '',
        'phone' => !empty($_POST['phone']) ? trim($_POST['phone']) : '',
        'country' => !empty($_POST['country']) ? trim($_POST['country']) : '',
        'utm_source' => !empty($_POST['utm_source']) ? trim($_POST['utm_source']) : '',
        'utm_medium' => !empty($_POST['utm_medium']) ? trim($_POST['utm_medium']) : '',
        'utm_campaign' => !empty($_POST['utm_campaign']) ? trim($_POST['utm_campaign']) : '',
        'utm_content' => !empty($_POST['utm_content']) ? trim($_POST['utm_content']) : '',
        'utm_term' => !empty($_POST['utm_term']) ? trim($_POST['utm_term']) : '',
        'ip' => getIp(),
    );

    $count = 3;
    while ($count--) {
        if (apiWebvorkV1NewLead($data)) {
            break;
        } else {
            sleep(2);
        }
    }

    header("Location: success.html");
    die();
}

healthCheck();

// Classes and functions

function healthCheck()
{
    checkUserAgent();

    echo '<h3>Health check</h3>';

    if ('3532ccb861ab10ef86a6d073c27c1246' == TOKEN) {
        echo 'Error: TOKEN is registered by default, you need to replace it with a token from your personal account. <br>';
    }

    try {
        $data = array(
            'token' => TOKEN,
            'offer_id' => 1,
            'ip' => getIp(),
            'name' => 'Test',
            'phone' => '+12345',
            'country' => 'IT',
        );

        $client = new SuperClient();
        $client->addEndpoint(ENDPOINT_2)
            ->addEndpoint(ENDPOINT_2)
            ->setWaitHttpStatus200(true);

        $response = $client->send($data);

        if ($response) {
            echo 'API test sent, result: ' . $response . '<br>';
        } else {
            echo 'Error: Failed to send request <br>';
        }
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage() . '<br>';
    }
}

function checkUserAgent()
{
    if (
        empty($_SERVER['HTTP_USER_AGENT'])
        || !preg_match('#Windows|Mac|Linux|Android|iPad|iPhone#i', $_SERVER['HTTP_USER_AGENT'])
    ) {
        die();
    }
}

function apiWebvorkV1NewLead($data)
{
    checkData($data);

    $client = new SuperClient();
    $client->addEndpoint(ENDPOINT_1)
        ->addEndpoint(ENDPOINT_2)
        ->setWaitHttpStatus200(true);

    $response = $client->send($data);

    $json = json_decode($response, true);

    if (isset($json['status'])) {

        return true;
    }

    return false;
}

function checkData($data)
{
    // the token is missing or registered by default
    if (empty($data['token']) || '3532ccb861ab10ef86a6d073c27c1246' == $data['token']) {
        header("Location: /?error=token");
        die();
    }

    // the offer is missing or registered by default
    if (empty($data['offer_id'])) {
        header("Location: /?error=offer");
        die();
    }

    // the phone is missing
    if (empty($data['phone'])) {
        header("Location: /?error=phone");
        die();
    }

    // the country is missing
    if (empty($data['country'])) {
        header("Location: /?error=country");
        die();
    }
}

function getIp()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arIp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = $arIp[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = '';
    }
    return $ip;
}

class SuperClient
{
    /** @var array */
    private $endpoints;

    /** @var array */
    private $requests;

    /** @var int */
    private $socketTimeout;

    /** @var array */
    private $response;

    /** @var false */
    private $waitHttpStatus200;

    public function __construct()
    {
        $this->requests = array();
        $this->socketTimeout = 20;
        $this->response = array();
        $this->endpoints = array();
        $this->waitHttpStatus200 = false;
    }

    /**
     * @param array $data
     * @return string
     * @throws \Exception
     */
    public function send($data)
    {
        if (!count($this->endpoints)) {
            throw new \Exception('add endpoint first ->addEndpoint($url)');
        }

        $content = http_build_query($data);
        $data['requestId'] = sha1(time() . $content);

        foreach ($this->endpoints as $endpoint) {
            $this->sendHttpRequest($endpoint, $data);
        }

        $response = $this->catchResponse();
        $this->closeAllRequests();

        return $response ? $this->getResponseBody($response['rawResponse']) : '';
    }

    /**
     * @param string $url
     */
    public function addEndpoint($url)
    {
        $this->endpoints[] = $url;

        return $this;
    }

    /**
     * @param bool $val
     */
    public function setWaitHttpStatus200($val)
    {
        $this->waitHttpStatus200 = $val;

        return $this;
    }

    /**
     * @param string $url
     * @param array $data
     */
    private function sendHttpRequest($url, $data)
    {
        $urlParsed = parse_url($url);

        $scheme = !empty($urlParsed['scheme']) ? $urlParsed['scheme'] : 'http'; // https
        $host = !empty($urlParsed['host']) ? $urlParsed['host'] : null;  // www.google.com
        $port = !empty($urlParsed['port']) ? $urlParsed['port'] : null; // 8080
        $path = !empty($urlParsed['path']) ? $urlParsed['path'] : '/'; // /path
        $query = !empty($urlParsed['query']) ? '?' . $urlParsed['query'] : ''; // ?search=1


        if (!$port && 'https' == $scheme) {
            $port = 443;
        }

        if (!$port && 'http' == $scheme) {
            $port = 80;
        }

        $hostname = ('https' == $scheme)
            ? 'ssl://' . $host . ':' . $port
            : $host . ':' . $port;

        $context = stream_context_create(
            array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                )
            )
        );

        $fp = @stream_socket_client(
            $hostname,
            $error_code,
            $error_message,
            $this->socketTimeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($fp) {
            $content = http_build_query($data);
            stream_set_blocking($fp, false);
            $httpRequest = "POST " . $path . $query . " HTTP/1.0\r\n";
            $httpRequest .= "Host: " . $host . "\r\n";
            $httpRequest .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $httpRequest .= "Content-Length: " . strlen($content) . "\r\n";
            $httpRequest .= "Connection: Close\r\n\r\n";
            $httpRequest .= $content;

            fwrite($fp, $httpRequest);
            $this->addRequest($url, $fp);
        }

        return $this;
    }

    /**
     * @return array
     */
    private function catchResponse()
    {
        $this->response = array();

        $startReadingTime = time();
        while (true) {
            if (!count($this->requests)) {
                return array();
            }

            $someDataRecieved = false;

            foreach ($this->requests as $key => $requestData) {
                if (!feof($requestData['fp'])) {
                    $part = fgets($requestData['fp'], 1024);
                    if ($part) {
                        $someDataRecieved = true;
                        $this->requests[$key]['rawResponse'] .= $part;
                    }
                } else {
                    if ($this->waitHttpStatus200) {
                        if ($this->checkHttpStatus200($this->requests[$key]['rawResponse'])) {
                            $this->response = $this->requests[$key];

                            return $this->response;
                        } else {
                            $this->closeRequestByKey($key);
                        }
                    } else {
                        $this->response = $this->requests[$key];

                        return $this->response;
                    }
                }
            }

            if (time() - $startReadingTime >= $this->socketTimeout) {

                return $this->response;
            }

            if (!$someDataRecieved) {
                usleep(100000);
            }
        }
    }

    /**
     * @param string $url
     * @param resource $fp
     */
    private function addRequest($url, $fp)
    {
        $this->requests[] = array(
            'url' => $url,
            'fp' => $fp,
            'rawResponse' => '',
        );
    }

    /**
     * @param int $key
     */
    private function closeRequestByKey($key)
    {
        if (isset($this->requests[$key]['fp'])) {
            @fclose($this->requests[$key]['fp']);
        }
        unset($this->requests[$key]);
    }

    /**
     *
     */
    private function closeAllRequests()
    {
        foreach ($this->requests as $key => $treadData) {
            $this->closeRequestByKey($key);
        }
    }

    /**
     * @param string $string
     * @return bool
     */
    private function checkHttpStatus200($string)
    {
        $firstLine = strstr($string, "\r\n", true);
        if ($firstLine && strpos($firstLine, '200') !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param $string
     * @return false|string
     */
    private function getResponseBody($string)
    {
        $body = substr($string, strpos($string, "\r\n\r\n") + 4, strlen($string));

        return $body;
    }
}
