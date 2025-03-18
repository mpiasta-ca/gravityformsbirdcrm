<?php

class GF_BirdCRM_TemplateProvider extends GF_BirdCRM_BaseProvider
{
    private const CHANNEL_TYPE_EMAIL = 'htmlEmail';

    private $selectOptionsCache = null;
    private $templateCache = [];
    
    protected $className = 'TemplateProvider';
    protected $logLevel = GF_BirdCRM_LogFactory::LEVEL_ERROR;
    
	private static $_instance = null;

    public static function get_instance($apiKey, $workspaceId) {

		if ( null === self::$_instance ) {
			self::$_instance = new self($apiKey, $workspaceId);
		}

		return self::$_instance;
	}

    protected function __construct($apiKey, $workspaceId) {
        parent::__construct($apiKey, $workspaceId);
    }

    /**
     * List all templates for the Bird account.
     * 
     * @return array|bool
     */
    public function listAll() {
        if ( $this->selectOptionsCache ) {
            return $this->selectOptionsCache;
        }

        $this->log->debug(__FUNCTION__, "Try list all");

        $path = "/workspaces/{$this->workspaceId}/projects";

        $params = [
            'type' => self::CHANNEL_TYPE_EMAIL,
            'limit' => 100,
            'sortBy' => 'createdAt',
        ];

        try {
            $response = $this->getRequest($path, $params);
        }
        catch (Exception $e) {
            $this->log->error(__FUNCTION__, "Failed: unexpected response");
            return [];
        }
        
        if ( empty($response) ) {
            $this->log->error(__FUNCTION__, "Failed: empty response");
            return [];
        }

        $results = $response['results'] ?? [];

        if ( empty($results) || !is_array($results) || !count($results) ) {
            $this->log->debug(__FUNCTION__, "No templates found");
            return [];
        }

        foreach ($results as $emailTemplate)
        {
            $id = $emailTemplate['id'] ?? null;
            $name = $emailTemplate['name'] ?? null;

            $this->addCached($id, $emailTemplate);

            $selectOptions[] = [
                'id' => $id,
                'name' => $name
            ];
        }

        $this->selectOptionsCache = $selectOptions;

        $this->log->debug(__FUNCTION__, "Found ". count($selectOptions) ." templates");
        return $selectOptions;
    }

    /**
     * Get information about one template by ID.
     * 
     * Object returns:
     * - id
     * - name
     * - activeResourceId
     * - createdAt
     * - updatedAt
     * 
     * @param string $templateId
     * @return array|null
     */
    public function getById($templateId) {
        if ( $template = $this->getCached($templateId) ) {
            return $template;
        }

        $this->log->debug(__FUNCTION__, "Try get by ID: $templateId");

        try {
            $path = "/workspaces/{$this->workspaceId}/projects/{$templateId}";
            $template = $this->getRequest($path);
        }
        catch (Exception $e) {
            $this->log->error(__FUNCTION__, "Failed: unexpected response");
            return null;
        }

        $id = $template['id'] ?? null;
        $versionId = $template['activeResourceId'] ?? null;

        if ( empty($id) || empty($versionId) ) {
            $this->log->error(__FUNCTION__, "Failed: missing expected data");
            return null;
        }

        $this->addCached($templateId, $template);

        $this->log->debug(__FUNCTION__, "Found: " . json_encode($template));
        return $template;
    }

    private function getCached($id) {
        $template = $this->templateCache[$id] ?? null;

        if ( $template ) {
            $this->log->debug(__FUNCTION__, "Found in cache: $id");
        }

        return $template;
    }

    private function addCached($id, $response) {
        $this->log->debug(__FUNCTION__, "Add to cache: $id");
        $this->templateCache[$id] = $response;
    }
}