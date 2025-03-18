<?php

class GF_BirdCRM_ContactProvider extends GF_BirdCRM_BaseProvider
{    
    protected $className = 'ContactProvider';
    protected $logLevel = GF_BirdCRM_LogFactory::LEVEL_DELTA;

	private static $_instance = null;

    public static function get_instance($apiKey, $workspaceId) {

		if ( null === self::$_instance ) {
			self::$_instance = new self($apiKey, $workspaceId);
		}

		return self::$_instance;
	}

    protected function __construct($apiKey, $workspaceId)
    {
        parent::__construct($apiKey, $workspaceId);
    }

    /**
     * Find Bird contact by matching email or insert new contact.
     * 
     * @param string $email
     * @return null|array
     */
    public function upsertContactByEmail($email, $firstName = '', $lastName = '')
    {
        $this->log->debug(__FUNCTION__, "Get by email: $email");

        if ( empty($email) ) {
            $this->log->error(__FUNCTION__, "Email is missing! <Terminate>");
            return null;
        }

        $typeEmailIdentifier = 'emailaddress';

        $path = "/workspaces/{$this->workspaceId}/contacts/identifiers/$typeEmailIdentifier/$email";

        $contactAttributes = [
            'displayName' => "$firstName $lastName",
            'firstName' => $firstName,
            'lastName' => $lastName,
        ];

        try {
            $response = $this->patchRequest($path, $contactAttributes);
        }
        catch (Exception $e) {
            $this->log->error(__FUNCTION__, "Failed: $email threw an error! (Error) " . $e->getMessage());
            return null;
        }

        $contactId = $response['id'] ?? null;

        if ( empty($contactId) ) {
            $this->log->error(__FUNCTION__, 'Failed: unexpected response! (Response)' . json_encode($response));
            return null;
        }
    
        return $response;
    }
}