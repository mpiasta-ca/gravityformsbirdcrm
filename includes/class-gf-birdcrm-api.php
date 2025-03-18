<?php

class GF_BirdCRM_API extends GF_BirdCRM_BaseProvider
{
    protected $className = 'GF_BirdCRM_API';

    private static $_instance = null;

    public static function get_instance($api_secret_key, $workspace_id, $email_channel_id)
    {
		if ( null === self::$_instance ) {
			self::$_instance = new self($api_secret_key, $workspace_id, $email_channel_id);
		}

		return self::$_instance;
	}

    protected function __construct($api_secret_key, $workspace_id, $email_channel_id)
	{
        parent::__construct($api_secret_key, $workspace_id, $email_channel_id);
    }

    /**
     * Test connection by getting a count of email templates.
     *
     * @since  1.0
     * @access public
     *
     * @return int
     */
    public function is_connected()
    {
        return count( $this->list_email_templates() ) > 0;
    }

    /**
     * Get list of email templates from Bird CRM.
     * 
     * @since  1.0
     * @access public
     * 
     * @return array
     */
    public function list_email_templates()
    {
        $email_templates = GF_BirdCRM_TemplateProvider::get_instance($this->apiKey, $this->workspaceId);
        return $email_templates->listAll();
    }

    /**
     * Get email template by ID from Bird CRM.
     *
     * @since  1.0
     * @access public
     *
     * @param int $template_id
     *
     * @return array
     */
    public function get_email_template_by_id($template_id)
    {
        $email_template = GF_BirdCRM_TemplateProvider::get_instance($this->apiKey, $this->workspaceId);
        return $email_template->getById($template_id);
    }

    /**
     * Send email using Bird CRM API.
     *
     * @since  1.0
     * @access public
     *
     * @param array $email_headers    Email headers.
     * @param array $email_data       Email data like email-to and template id.
     * @param array $email_variables  Email variables that are used in template.
     *
     * @return array|WP_Error
     */
    public function send_email($email_headers, $email_data, $email_variables)
	{
        $this->log->debug(__FUNCTION__, 'Email data: ' . json_encode($email_data));

        // Field values
        $firstName = $email_data['first_name'] ?? '';
        $lastName = $email_data['last_name'] ?? '';
        $email = $email_data['email'] ?? '';
        $templateType = $email_data['email_template'] ?? '';
        $templateId = $email_data['template_id'] ?? '';
        $publishedVersionId = $email_data['published_version_id'] ?? '';
        $attachments = $email_data['attachments'] ?? [];

        // Create contact

        /** @var GF_BirdCRM_ContactProvider */
        $contactProvider = GF_BirdCRM_ContactProvider::get_instance($this->apiKey, $this->workspaceId);
        $contact = $contactProvider->upsertContactByEmail($email, $firstName, $lastName);
        $contactId = $contact['id'] ?? null;

        $this->log->debug(__FUNCTION__, 'Contact ID: ' . $contactId);

        // Prepare email

        /** @var GF_BirdCRM_EmailSender */
        $emailSender = GF_BirdCRM_EmailSender::get_instance($this->apiKey, $this->workspaceId, $this->emailChannelId);
        $emailSender->setHeaders($email_headers);
        $emailSender->setTemplate($templateType, $templateId, $publishedVersionId);
        $emailSender->addRecipientEmail($email, $firstName, $lastName);
        $emailSender->addVars($email_variables);
        $emailSender->addAttachments($attachments);

        try {
            return $emailSender->send();
        } catch (Exception $e) {
            return $this->log->error(__FUNCTION__, "Failed: " . $e->getMessage());
        }
	}
}
