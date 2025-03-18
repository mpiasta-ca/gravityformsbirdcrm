<?php

class GF_BirdCRM_HttpRequestClient
{    
    private const REQUEST_TIMEOUT = 90000;

    private $requestStartTime = 0;
    private $requestEndTime = 0;

    private $timeout = 0;

    private $url = '';
    private $headers = [];
    private $data = null;

    private $responseBody = null;
    private $statusCode = 0;
    private $connectionTimeout = false;
    private $errorNumber = null;
    private $errorMessage = null;
    
    /** @var GF_BirdCRM_LogFactory */
    private $log;

    public function __construct($url = null, $headers = null, $data = null, $debugLevel = GF_BirdCRM_LogFactory::LEVEL_DEBUG)
    {
        $this->log = new GF_BirdCRM_LogFactory('HttpRequestClient', $debugLevel);

        $this->timeout = self::REQUEST_TIMEOUT;

        $this->url = $url;
        $this->headers = $headers;
        $this->data = $data;
    }

    public function setTimeout($timeout) {
        $this->timeout = $timeout;
    }

    public function setUrl($url = '') {
        $this->url = $url;
    }

    public function setHeaders($headers = []) {
        $this->headers = $headers;
    }

    public function addHeader($header = '') {
        $this->headers[] = $header;
    }

    public function setData($data = []) {
        $this->data = $data;
    }

    public function addData($key = '', $value = '') {
        $this->data[$key] = $value;
    }

    public function get()
    {
        $this->execute('GET');
    }

    public function post()
    {
        $this->execute('POST');
    }

    public function patch()
    {
        $this->execute('PATCH');
    }

    public function delete()
    {
        $this->execute('DELETE');
    }

    private function execute($requestType = 'GET')
    {
        $this->log->debug(__FUNCTION__, "$requestType: {$this->url}");

        $this->requestStartTime = microtime(true);

        try {
            $ch = curl_init();

            if ($ch === false) {
                throw new Exception('curl_disabled');
            }

            switch ( $requestType )
            {
                case 'GET':
                    $params = !empty($this->data) ? http_build_query( $this->data ) : null;
                    $queryString = !empty($params) ? '?' . $params : '';

                    curl_setopt($ch, CURLOPT_HTTPGET, true);
                    curl_setopt($ch, CURLOPT_URL, $this->url . $queryString);
                    break;

                case 'POST':
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_URL, $this->url);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
                    break;

                case 'PATCH':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    curl_setopt($ch, CURLOPT_URL, $this->url);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
                    break;

                case 'DELETE':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    curl_setopt($ch, CURLOPT_URL, $this->url);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
                    break;
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->timeout); // Timeout in milliseconds
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

            $this->log->debug(__FUNCTION__, 'Request starting...');

            $this->responseBody = curl_exec($ch);

            $this->statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $this->errorNumber = curl_errno($ch);
            $this->errorMessage = curl_error($ch);
            $this->connectionTimeout = (curl_errno($ch) === CURLE_OPERATION_TIMEDOUT);

            curl_close($ch);

            $this->log->debug(__FUNCTION__, 'Request finished.');
        }
        catch (Exception $e) {
            $this->log->error(__FUNCTION__, 'CURL Failed: '. $e->getMessage());
            return;
        }
        
        $this->requestEndTime = microtime(true);

        $this->log->debug(__FUNCTION__, 'Duration: '. $this->getDurationString() );

        if ( $this->connectionTimeout() ) {
            $this->log->error( __FUNCTION__, $this->dumpMessage('Timed-out') );
            return;
        }

        if ( $this->failed() ) {
            $this->log->error( __FUNCTION__, $this->dumpMessage('Failed') );
            return;
        }

        if ( $this->successful() ) {
            $this->log->debug(__FUNCTION__, 'Successful');
        }
    }
    
    private function dumpMessage($message)
    {
        $dump = [
            'code' => $this->getStatusCode(),
            'errorNo' => $this->getErrorNumber(),
            'error' => $this->getErrorMessage(),
            'timedout' => $this->connectionTimeout(),
            'response' => $this->getResponseBody(),
        ];

        return $message .': '. json_encode($dump);
    }

    public function getDuration()
    {
        $durationInSecs = $this->requestEndTime - $this->requestStartTime;
        return number_format($durationInSecs, 3);
    }

    public function getDurationString()
    {
        return $this->getDuration() . ' seconds';
    }
    
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getResponseData()
    {
        return json_decode( $this->getResponseBody(), true );
    }

    public function getErrorNumber()
    {
        return $this->errorNumber;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function connectionTimeout()
    {
        return $this->connectionTimeout;
    }

    public function invalidResponse()
    {
        return $this->getResponseBody() === false;
    }

    public function successful()
    {
        $statusCode = (int) $this->statusCode;
        return ($statusCode >= 200 && $statusCode < 300);
    }

    public function failed()
    {
        return $this->getErrorNumber() || !$this->successful() || $this->invalidResponse();
    }
}