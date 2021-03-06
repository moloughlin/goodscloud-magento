<?php

class Goodscloud
{
    const ERROR_CREDENTIALS_INCORRECT_SERVER_UNREACHABLE = 1;

    private $uri;
    private $session;

    /**
     * @param string $uri      base url for goodscloud api
     * @param string $email    email to login with
     * @param string $password password to use
     *
     * @throws Exception
     */
    public function __construct($uri, $email, $password)
    {
        $this->uri = $uri;
        $this->login($email, $password);
    }

    /**
     * @param string $email    email to login with
     * @param string $password password to login with
     *
     * @throws Exception
     */
    private function login($email, $password)
    {
        static $ch;

        if($ch === null) {
            $ch = curl_init();
        }
        $url = $this->uri . '/session';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array(
                'GC-Email: ' . $email,
                'GC-Password: ' . $password,
                'Content-Length: 0'
            )
        );
        // Set so curl_exec returns the result instead of outputting it.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $this->session = json_decode($response);
        if (!isset($this->session) || $this->session->email != $email) {
            throw new Exception(
                "API credentials incorrect or server unreachable",
                self::ERROR_CREDENTIALS_INCORRECT_SERVER_UNREACHABLE
            );
        }
    }

    /**
     * @param array $params
     * @param bool  $encode
     *
     * @return string
     */
    private static function serialize_params($params, $encode = false)
    {
        ksort($params);
        $str_params = array();
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            if ($encode) {
                $value = urlencode($value);
            }
            $str_params[] = ($key . '=' . $value);
        }
        return join('&', $str_params);
    }

    /**
     * @param string       $method HTTP method
     * @param string       $uri    url to request
     * @param string       $path   path to request
     * @param array        $params http parameters
     * @param array|string $data   data to send with (post, put, delete data)
     *
     * @return string|bool request body or true on success/false on failure
     *
     * @throws Exception
     */
    private static function http($method, $uri, $path, $params, $data)
    {
        static $ch;
        if($ch === null) {
            $ch = curl_init();
        }
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json; version=20141029'
            )
        );

        $param_str = Goodscloud::serialize_params($params, true);
        if ($param_str) {
            curl_setopt($ch, CURLOPT_URL, $uri . $path . "?" . $param_str);
        } else {
            curl_setopt($ch, CURLOPT_URL, $uri . $path);
        }

        // reset before request (due to keepalive)
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_setopt($ch, CURLOPT_POST, 0);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif ($method == 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        // Set so curl_exec returns the result instead of outputting it.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Get the response
        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $decodedResult = json_decode($result, true);
        if ($status_code >= 200 and $status_code < 300) {
            return $decodedResult;
        } else {
            if (isset($decodedResult['message'])) {
                $message = $decodedResult['message'];
                throw new Exception("API request failed (status code $status_code): $message");
            }
            throw new Exception("API request failed (status code $status_code): Unknown error\n$result");
        }
    }

    /**
     * @param string       $method HTTP method to use (get, post, put, delete)
     * @param string       $path   path to request
     * @param string       $params get parameter to submit
     * @param array|string $data   data to send with (post, put, delete data)
     *
     * @return bool|string
     * @throws Exception
     */
    private function signed_request($method, $path, $params, $data = "")
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $expires = date("Y-m-d\TH:i:s\Z", time() + 60); //current time + 60 seconds
        $auth_params = array(
            'key'     => $this->session->auth->app_key,
            'token'   => $this->session->auth->app_token,
            'expires' => $expires
        );
        $params = array_merge($params, $auth_params);
        $param_str = $this::serialize_params($params);
        $sign_str = implode(
            array(
                $method, $path, md5($param_str), md5($data),
                $this->session->auth->app_token, $expires,
            ), "\n"
        );
        $sign = trim(
            base64_encode(
                hash_hmac(
                    'sha1', utf8_encode($sign_str), $this->session->auth->app_secret, true
                )
            ), '='
        );
        $params = array_merge($params, array('sign' => $sign));
        return $this::http($method, $this->uri, $path, $params, $data);
    }

    /**
     * @param string $uri
     * @param array  $params
     *
     * @return bool|string
     */
    public function get($uri, $params = array())
    {
        return $this->signed_request('GET', $uri, $params);
    }

    /**
     * @param string       $uri
     * @param array        $params
     * @param array|string $data data to send with (post, put, delete data)
     *
     * @return bool|string
     */
    public function post($uri, $params, $data)
    {
        return $this->signed_request('POST', $uri, $params, $data);
    }

    /**
     * @param string       $uri
     * @param array        $params
     * @param array|string $data data to send with (post, put, delete data)
     *
     * @return bool|string
     */
    public function put($uri, $params, $data)
    {
        return $this->signed_request('PUT', $uri, $params, $data);
    }

    /**
     * @param string       $uri
     * @param array        $params
     * @param array|string $data data to send with (post, put, delete data)
     *
     * @return bool|string
     */
    public function patch($uri, $params, $data)
    {
        return $this->signed_request('PATCH', $uri, $params, $data);
    }

    /**
     * @param string $uri
     *
     * @return bool|string
     */
    public function delete($uri)
    {
        return $this->signed_request('DELETE', $uri, array());
    }
}
