<?php

class GF_BirdCRM_BaseProvider
{
    private const API_HOSTNAME = 'https://api.bird.com';

    protected $className = 'BaseProvider';
    protected $logLevel = GF_BirdCRM_LogFactory::LEVEL_DELTA;

    protected $apiKey;
    protected $workspaceId;
    protected $emailChannelId;

    /** @var GF_BirdCRM_LogFactory */
    protected $log;
    
    protected function __construct($apiKey, $workspaceId, $emailChannelId = null)
    {
        $this->apiKey = $apiKey;
        $this->workspaceId = $workspaceId;

        if ( !empty($emailChannelId) ) {
            $this->emailChannelId = $emailChannelId;
        }
        
        $this->log = new GF_BirdCRM_LogFactory($this->className, $this->logLevel);
    }

    /**
     * Perform a GET request to the Bird API.
     * 
     * @param string $path
     * @param array $params
     * @return array|null
     */
    protected function getRequest($path, $params = null)
    {
        if ( !empty($params) ) {
            $this->log->debug(__FUNCTION__, 'Request: ' . json_encode($params));
        }

        return $this->request('GET', $path, $params);
    }

    /**
     * Perform a POST request to the Bird API.
     * 
     * @param string $path
     * @param array|null $data
     * @return array|null
     */
    protected function postRequest($path, $data = [])
    {
        $requestData = json_encode($data);
        return $this->request('POST', $path, $requestData);
    }

    /**
     * Perform a PATCH request to the Bird API.
     * 
     * @param string $path
     * @param array|null $data
     * @return array|null
     */
    protected function patchRequest($path, $data = [])
    {
        $requestData = json_encode($data);
        return $this->request('PATCH', $path, $requestData);
    }

    /**
     * Perform a DELETE request to the Bird API.
     * 
     * @param string $path
     * @param array|null $data
     * @return array|null
     */
    protected function deleteRequest($path, $data = [])
    {
        $requestData = json_encode($data);
        return $this->request('DELETE', $path, $requestData);
    }

    /**
     * Perform a request $type to the Bird API.
     * 
     * @param string $type
     * @param string $path
     * @param array|null $requestData
     * @return array|null
     */
    private function request($type, $path, $requestData)
    {
        $requestUrl = self::API_HOSTNAME . $path;

        $requestHeaders = [
            'Authorization: AccessKey ' . $this->apiKey,
            'Content-Type: application/json',
        ];
            
        $request = new GF_BirdCRM_HttpRequestClient($requestUrl, $requestHeaders, $requestData, $this->logLevel);

        switch ($type)
        {
            case 'GET':
                $request->get();
                break;

            case 'POST':
                $request->post();
                break;

            case 'PATCH':
                $request->patch();
                break;

            case 'DELETE':
                $request->delete();
                break;
        }

        if ( $request->failed() ) {
            throw new Exception( $request->getResponseBody() );
        }

        $this->log->debug(__FUNCTION__, 'Response: ' . $request->getResponseBody(), 1000);
        return $request->getResponseData();
    }
}