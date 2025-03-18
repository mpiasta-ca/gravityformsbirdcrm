<?php

class GF_BirdCRM_EmailSender extends GF_BirdCRM_BaseProvider
{
    private const USE_CASE_DEFAULT = 'transactional';

    private const EMAIL_SEND_STATUS_SUCCESS = 'accepted';

    protected $className = 'EmailSender';
    protected $logLevel = GF_BirdCRM_LogFactory::LEVEL_DELTA;

    protected $state;

    /** @var GF_BirdCRM_TemplateProvider */
    protected $templateProvider;
    
	private static $_instance = null;

    public static function get_instance($apiKey, $workspaceId, $emailChannelId) {

		if ( null === self::$_instance ) {
			self::$_instance = new self($apiKey, $workspaceId, $emailChannelId);
		}

		return self::$_instance;
	}

    protected function __construct($apiKey, $workspaceId, $emailChannelId)
    {
        parent::__construct($apiKey, $workspaceId, $emailChannelId);

        $this->templateProvider = GF_BirdCRM_TemplateProvider::get_instance($apiKey, $workspaceId);

        $this->setDefaultState();
    }

    public function setDefaultState()
    {
        $this->state = [
            'headers' => [],
            'template' => [],
            'recipientEmailList' => [],
            'vars' => [],
            'attachments' => []
        ];
    }

    public function Clear()
    {
        $this->setDefaultState();
    }

    public function setHeaders($headers)
    {
        $this->state['headers'] = $headers;
        return $this;
    }

    public function setTemplate($type, $id, $versionId = null, $useCase = null)
    {
        $this->state['template'] = [
            'type' => $type,
            'id' => $id,
            'versionId' => $versionId,
            'useCase' => $useCase ?? self::USE_CASE_DEFAULT
        ];

        return $this;
    }

    public function addRecipientEmail($email, $firstName = '', $lastName = '')
    {
        if ( !is_email($email) )
        {
            $this->log->error(__FUNCTION__, "Invalid email: $email");
            return false;
        }

        $this->state['recipientEmailList'][] = [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName
        ];
        return $this;
    }

    public function addVars($vars)
    {
        if ( empty($vars) ) {
            return $this;
        }

        if ( !is_array($vars) ) {
            $this->log->error(__FUNCTION__, "Invalid vars: ". json_encode($vars));
            return false;
        }

        $this->state['vars'] = array_merge($this->state['vars'], $vars);

        return $this;
    }

    public function addAttachments($attachments)
    {
        if ( empty($attachments) ) {
            return $this;
        }

        if ( !is_array($attachments) ) {
            $this->log->error(__FUNCTION__, "Invalid attachments array: ". json_encode($attachments));
            return false;
        }

        $this->state['attachments'] = array_merge($this->state['attachments'], $attachments);

        return $this;

    }
    
    private function getEmailHeaders()
    {
        $headers = [
            'from' => []
        ];

        $from_name = $this->state['headers']['from']['name'];
        $email_handle = $this->state['headers']['from']['email_handle'];

        if ( !empty($from_name) ) {
            $headers['from']['displayName'] = $from_name;
        }

        if ( !empty($email_handle) ) {
            $headers['from']['username'] = $email_handle;
        }

        return $headers;
    }

    private function getTemplateType()
    {
        return $this->state['template']['type'] ?? null;
    }

    private function getTemplateId()
    {
        return $this->state['template']['id'] ?? null;
    }

    private function getTemplateVersionId()
    {
        return $this->state['template']['versionId'] ?? null;
    }

    private function getTemplateUseCase()
    {
        return $this->state['template']['useCase'] ?? null;
    }

    private function getRecipientEmailList()
    {
        return $this->state['recipientEmailList'] ?? [];
    }  

    private function getTemplateVars()
    {
        //unused vars added by Gravity Forms
        $gf_vars_key = [
            'id', 'form_id', 'post_id', 'date_created', 'date_updated', 'is_starred', 'is_read',
            'currency', 'payment_status', 'payment_date', 'payment_amount', 'payment_method',
            'transaction_id', 'is_fulfilled', 'created_by', 'transaction_type', 'user_agent',
            'status', 'source_id', 
        ];

        //remove Gravity Forms vars
        foreach ( $gf_vars_key as $key )
        {
            if ( isset($this->state['vars'][$key]) )
            {
                unset( $this->state['vars'][$key] );
            }
        }

        //normalize
        foreach ($this->state['vars'] as $key => $value)
        {
            //remove empty and numeric keys
            if ( empty($key) || is_numeric($key) )
            {
                unset( $this->state['vars'][$key] );
                continue;
            }

            //if empty value, replace with ''
            if ( empty( trim($value) ) )
            {
                $this->state['vars'][$key] = '';
            }

            //replace numeric with string
            if ( is_int($value) || is_float($value) )
            {
                $this->state['vars'][$key] = (string) $value;
            }

            //replace boolean with '1' or '0'
            if ( is_bool($value) )
            {
                $this->state['vars'][$key] = $value ? '1' : '0';
            }
        }
        
        return $this->state['vars'] ?? [];
    }

    /**
     * Get attachments array formatted for Bird API.
     * 
     * @return array|null
     */
    private function getEmailAttachments()
    {
        $attachments = $this->state['attachments'] ?? [];

        if ( empty($attachments) || !is_array($attachments) ) {
            return null;
        }

        $template_attachments = [];

        foreach ($attachments as $attachment)
        {
            $url = trim( $attachment['file_url'] ?? '' );
            $filename = trim( $attachment['file_name'] ?? '' );

            if ( empty($url) || !filter_var($url, FILTER_VALIDATE_URL) ) {
                continue;
            }
            
            if ( empty($filename) ) {
                $filename = basename($url);
            }

            $template_attachments[] = [
                'mediaUrl' => $url,
                'filename' => $filename
            ];
        }

        return $template_attachments;
    }

    /**
     * Convert email address string or list to Bird Recipients array.
     * 
     * @return array
     */
    private function getValidRecipientsMapped()
    {
        $recipients = [];
        $emails = $this->getRecipientEmailList();

        if ( !empty($emails) )
        {
            foreach ($emails as $recipient)
            {
                if ( empty($recipient['email']) || !is_email($recipient['email']) ){
                    continue;
                }

                $recipients[] = [
                    'identifierKey' => 'emailaddress',
                    'identifierValue' => $recipient['email'],
                ];
            }
        }

        return $recipients;
    }

    /**
     * Send an email using a template through Bird.
     * 
     * @return bool
     */
    public function send()
    {
        $templateType = $this->getTemplateType();
        $templateId = $this->getTemplateId();
        $templateVersionId = $this->getTemplateVersionId();
        $templateUseCase = $this->getTemplateUseCase();
        $toEmails = $this->getRecipientEmailList();

        // Validators
        if ( empty($templateId) ) {
            $this->log->error(__FUNCTION__, "Template ID is not set");
            $this->Clear();
            throw new Exception('missing_template_id');
        }

        if ( empty($toEmails) ) {
            $this->log->error(__FUNCTION__, "Email address missing");
            $this->Clear();
            throw new Exception('missing_email_address');
        }

        if ( empty( $templateVersionId ) ) {
            $template = $this->templateProvider->getById($templateId);
            $templateVersionId = $template['activeResourceId'] ?? null;
        }

        if ( empty($templateVersionId) ) {
            $this->log->error(__FUNCTION__, "Invalid template: version ID is missing");
            $this->Clear();
            throw new Exception('missing_template_version_id');
        }

        // Send email
        $this->log->delta(__FUNCTION__, "Try send: (Template) $templateType (Email) ". json_encode($toEmails));

        $path = "/workspaces/{$this->workspaceId}/channels/{$this->emailChannelId}/messages";

        $data = [
            'receiver' => [
                'contacts' => $this->getValidRecipientsMapped()
            ],
            'meta' => [
                'email' => $this->getEmailHeaders(),
                'extraInformation' => []
            ],
            'template' => [
                'projectId' => $templateId,
                'version' => $templateVersionId,
                'locale' => 'en',
                'variables' => $this->getTemplateVars()
            ],
        ];

        //transactional or marketing
        if ( !empty($templateUseCase) )
        {
            $data['meta']['extraInformation']['useCase'] = $templateUseCase;
        }

        //include attachments
        $attachments = $this->getEmailAttachments();
        if ( !empty( $attachments ) )
        {
            $data['template']['attachments'] = $attachments;
        }

        $this->log->delta(__FUNCTION__, "Post data: ". json_encode($data));

        try {
            $response = $this->postRequest($path, $data);
        }
        catch (Exception $e) {
            $this->log->error(__FUNCTION__, 'Failed: unexpected response');
            $this->Clear();
            throw new Exception('send_email_failed');
        }

        $status = $response['status'] ?? null;

        if ( $status !== self::EMAIL_SEND_STATUS_SUCCESS )
        {
            $this->log->error(__FUNCTION__, 'Failed: status mismatch');
            $this->Clear();
            throw new Exception('send_email_failed');
        }

        $this->log->delta(__FUNCTION__, "Email sent: (Template) $templateType (Email) ". json_encode($toEmails));
        $this->Clear();
        return true;
    }
}