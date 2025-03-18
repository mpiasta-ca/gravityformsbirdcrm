# Gravity Forms Bird CRM (WordPress Plugin)

This is a WordPress 6.0+ plugin for Gravity Forms to add support for Email Sending through Bird CRM (https://bird.com).

This program is released as-is without support or warranty.

Feel free to make modifications to suit your needs. I welcome Bug Reports and Commits.

If you use this plugin, give it a star so I know it is being used.

## Behavior

When a Gravity Form is submitted:

* Contact will be created with basic fields: first name, last name, email.
* Email will be sent using the Bird CRM Email service, using a selected Email Template from your Bird Template Editor list.

## Features

* Set your API Credentials in: Gravity Forms > Settings > Bird CRM;
* Set multiple emails to be sent (ie. one to customer, one to your staff) in: Form > Settings > Bird CRM;
* Auto syncs template list from your Bird account (every 5 minutes) into a Pods Custom Post Type;
* Daily logs are created in: `/wp-content/uploads/gf_birdcrm_logs`.

## Limitations

* This plugin was developed for our needs so some form fields may not be handled properly.
* Current forms fields supported:
  * text
  * dropdown
  * number
  * checkbox
  * radio
  * multiple choice
  * consent
  * full name
  * address
  * email
  * phone
  * date
  * time
  * file uploads pro (Gravity Forms Perks)
* You may need to add code to support other fields, because the value passed to Bird CRM API may not be in your expected format.
* Form fields must set "Advanced Field Label" using ie. `customer_email` format, so you can reference the field in the Email Template within Bird CRM.

## Requirements

* Gravity Forms plugin (https://gravityforms.com)
* Pods plugin: (https://wordpress.org/plugins/pods/)

## Installation

1. Upload plugin to your WordPress website
2. Activate plugin
3. Install Pods plugin
4. Create a Custom Post Type named `email`
5. Bird CRM plugin settings
6. Set "Advanced Field Label" values on the form fields
7. Attach an email to your form
8. Map your form fields and attachments
9. Set conditional logic (optional)
10. Setup multiple emails (optional)
11. Setup a staff email (optional)
12. Edit your Email Template in Bird
13. Debugging

### 4. Create a Custom Post Type named `email`

This will be used to store/sync a list of available Templates from your Bird CRM account.

Go to: Pods > Add New.

Create a new Custom Post Type name `email` (type: `Meta`) with the following values:

![pods-fields](https://github.com/user-attachments/assets/037944fa-3439-479e-9cba-16c684fc496e)

![pods-labels](https://github.com/user-attachments/assets/b0df44ba-9237-4fd6-8f20-8c1d29ae5114)

![pods-admin-ui](https://github.com/user-attachments/assets/727024af-efe2-4de5-9edf-07cf5d2d7b58)

![pods-connections](https://github.com/user-attachments/assets/d8395079-4250-4be1-810a-6b908c0813ad)

![pods-advanced-options](https://github.com/user-attachments/assets/c9e86ad5-b622-4f3e-a1bd-422ea6bfdb2f)

![pods-rest-api](https://github.com/user-attachments/assets/de1947a4-9a82-4f62-9ac6-e5bfb2810f61)

### 5. Bird CRM plugin settings

Go to: Gravity Forms > Settings > Bird CRM

#### 5a. Setup your API credentials for Bird CRM

Connect your Bird CRM account by inputing your API credentials and workspace information.

#### 5a. Setup your API credentials for Bird CRM

Once connected to the API successfully, click the "Synchronize" button.

![image](https://github.com/user-attachments/assets/340b28b6-5fa3-424c-8d3f-b28205b83a25)

### 6. Set "Advanced Field Label" values on the form fields

#### Email (field example)

ie. `customer_email`.

In your Email Template, you use it like this: `{{customer_email}}`.

![email-field](https://github.com/user-attachments/assets/420ffa11-c0c2-43e4-b7f5-76b559551bfc)

#### Name (field example)

ie. `customer_name` (you don't need to name the other fields).

In your Email Template, you use it like this: `{{customer_name_first}}`, `{{customer_name_last}}`.

![name-field](https://github.com/user-attachments/assets/cece6dba-9c6c-4cfa-a91e-88f7b58004e3)

#### Address (field example)

ie. `customer_address` (you don't need to name the other fields).

In your Email Template, you use it like this: `{{customer_address_street_address}}`, `{{customer_address_city}}`, `{{customer_address_state}}`, `{{customer_address_zip_code}}`, `{{customer_address_country}}`, etc.

![address-field](https://github.com/user-attachments/assets/c8643b13-3a50-4886-955d-60fd8cd76389)

#### Template ID (field example)

If you want to use a Dynamic Template ID (ie. if your Email Template will change based on user input):

![template-id-field](https://github.com/user-attachments/assets/58647150-e803-4c0c-b190-4e1bea95b59c)

### 7. Attach an email to your form

Go to: Gravity Forms > Forms > (Pick a form) > Settings > Bird CRM

#### Click "Add new" button

![add-new](https://github.com/user-attachments/assets/2569b7f2-98cf-47d8-9f1d-e6658f3c028b)

#### Pick an action

Choose one of the two options:

![select-action](https://github.com/user-attachments/assets/6b7c7011-738b-448c-8aa7-a4fdb4c2a20d)

##### 1) Send Email (email template set by form field)

This options allows you to map a form field to your Email Template ID, which can be set as Hidden or Administrative so it isn't visible on your public form, to set the Email Template based on a field value (ie. dropdown value).

For our use-case, we use Gravity Forms Perks Populate Anything plugin (https://gravitywiz.com/documentation/gravity-forms-populate-anything/) to dynamically load the latest Template IDs from our `email` custom post type.

##### 2) Send Email (global email template)

This option allows you to select a specific Email Template to always use for this email.

The dropdown will display all the synced Email Templates from your Bird CRM account (if you do not see the latest templates, click the "Synchronize" button on the Gravity Forms > Settings page, or just wait 5 minutes for the scheduled cron task to run).

### 8. Map your form fields

Now you can map the form fields to determine where the email is sent and which Email Template is used.

#### 1) Send Email (email template set by form field)

Only **Email To** field is required (First Name and Last Name are optionally, they will appear in the Email To header).

For **Email Template**, select which field will have the Template ID value of your desired Email Template.

![email-template-form-field](https://github.com/user-attachments/assets/1fe0ef98-1faa-4496-8492-675b7ba24a06)

#### 2) Send Email (global email template)

For **Email Template**, select from the list of Email Templates synced from your Bird CRM account.

![email-template-global](https://github.com/user-attachments/assets/13629cfc-54e5-4fb0-abf9-641ef5ba5043)

#### Attachments

If your form has one or more File Upload Fields, you can checkbox each field to include for each email.

If you leave the file upload field unchecked, it will not be attached to the email.

### 9. Set conditional logic (optional)

You can set conditions so the email will ONLY send if ALL/ANY conditions are met.

This feature is good if you want to setup email variations, and use a field value to determine which should send.

![conditional-send](https://github.com/user-attachments/assets/b2f4b348-da9d-4ebf-a596-a24b4d30451a)

### 10. Setup multiple emails (optional)

You can setup multiple emails to send each time a form is submitted.

Or use Conditionals to determine which should send based on a specific field value.

![email-variations](https://github.com/user-attachments/assets/317f8948-50cb-4c92-86eb-e4f2e180875c)

### 11. Setup a staff email (optional)

For sending an internal copy of the form to your staff:

1. Add a text field to your form, ie. `staff_email`;
2. Set the "Default Value" with your internal email, ie. `support@mywebsite.com`;
3. Mark "Visibility" as Hidden or Administrative;
4. (Optional) Repeat these steps to map a `staff_first_name` and `staff_last_name` to the email header.

![staff-email-field](https://github.com/user-attachments/assets/ed83ca10-cc87-483a-ba63-9fd1e0728ee2)

### 12. Edit your Email Template in Bird

When you edit your Email Template in Bird CRM, you can insert custom values into your content by pasting the field keys, ie. `{{customer_name_first}}`, `{{customer_name_last}}`, etc.

### 13. Debugging

Try submitting your form to see if the email is sent.

If you are having issues with your form, download the daily log file to see the history of what happened, ie. `/wp-content/uploads/gf_birdcrm_logs/2025-03-18.log`. 

There you can see any error messages, and the contents of the API Request that was sent to Bird CRM, which will show you a list of all the field keys and field values that were sent in the request.



