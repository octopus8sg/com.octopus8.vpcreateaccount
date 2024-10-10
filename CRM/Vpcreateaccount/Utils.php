<?php
class CRM_Vpcreateaccount_Utils
{
    public const SETTINGS_NAME = "Vpcreateaccount Settings";
    public const SETTINGS_SLUG = 'vpcreateaccount_settings';

    public static function setSettings($setting_name, $setting_value)
    {
        $settings = CRM_Core_BAO_Setting::getItem(self::SETTINGS_NAME, self::SETTINGS_SLUG);
        if (!is_array($settings)) {
            $settings = [];
        }
        $settings[$setting_name] = $setting_value;
        $s = CRM_Core_BAO_Setting::setItem($settings, self::SETTINGS_NAME, self::SETTINGS_SLUG);

    }

    public static function getSettings($setting = null)
    {
        $settings = CRM_Core_BAO_Setting::getItem(self::SETTINGS_NAME, self::SETTINGS_SLUG);
        if ($setting === null) {
            if (is_array($settings)) {
                return $settings;
            }
            $settings = [];
            return $settings;
        }
        if ($setting) {
            $return_setting = CRM_utils_array::value($setting, $settings);
            if (!$return_setting) {
                return false;
            }
            return $return_setting;
        }
    }

    public static function getCustomGroupAndField($customFieldId)
    {
        $customGroupAndField = civicrm_api4('CustomField', 'get', [
            'select' => [
                'custom_group_id:name',
                'name',
            ],
            'where' => [
                ['id', '=', $customFieldId],
            ],
            'checkPermissions' => FALSE,
        ]);

        return $customGroupAndField[0]['custom_group_id:name'] . '.' . $customGroupAndField[0]['name'];
    }

    public static function getActivityType($activityTypeId)
    {
        Civi::log()->debug("TypeId: {$activityTypeId}");
        $activityType = civicrm_api4('OptionValue', 'get', [
            'select' => [
                'label',
            ],
            'where' => [
                ['value', '=', $activityTypeId],
            ],
            'checkPermissions' => FALSE,
        ]);

        return $activityType[0]['label'];
    }

    public static function getAcceptanceStatus($activityId)
    {
        $cfIdAcceptanceStatus = self::getSettings('acceptance_status_field');
        $cgcfnAcceptanceStatus = self::getCustomGroupAndField($cfIdAcceptanceStatus);

        $acceptanceStatus = civicrm_api4('Activity', 'get', [
            'select' => [
                $cgcfnAcceptanceStatus,
            ],
            'where' => [
                ['id', '=', $activityId],
            ],
            'checkPermissions' => FALSE,
        ]);

        return $acceptanceStatus[0][$cgcfnAcceptanceStatus];
    }

    public static function getContactIdByActivityId($activityId)
    {
        $contactId = civicrm_api4('ActivityContact', 'get', [
            'select' => [
                'contact_id'
            ],
            'where' => [
                ['activity_id', '=', $activityId]
            ],
            'checkPermissions' => FALSE,
        ]);

        return $contactId[0]['contact_id'];
    }

    public static function createVPUser($contactId)
    {
        // Fetch Approved Contact
        Civi::log()->debug("Fetch Contact");
        $contact = civicrm_api3('Contact', 'getsingle', [
            'id' => $contactId,
        ]);

        // Generate Account Credentials
        $email = $contact['email'];
        $username = sanitize_user($contact['first_name'] . ' ' . $contact['last_name']);
        $birthDate = $contact['birth_date'];
        $phone = $contact['phone'];

        // Extract birthdate in YYYYMMDD format
        $formattedBirthDate = date('Ymd', strtotime($birthDate));

        // Extract last 4 digits of the phone number
        $lastFourDigits = substr($phone, -4);

        // Concatenate the birthdate and last 4 digits of the phone number
        $password = $formattedBirthDate . $lastFourDigits;

        Civi::log()->debug("Email: {$email}, Username: {$username}, Password: {$password}");

        // Check if contact is already linked to a WordPress user
        $ufMatch = civicrm_api3('UFMatch', 'get', [
            'contact_id' => $contactId,
        ]);

        if (!empty($ufMatch['values'])) {
            // A WordPress user is already linked to this CiviCRM contact
            Civi::log()->debug("WordPress user already exists for this contact.");
            CRM_Core_Session::setStatus("WordPress user already exists for this contact.");
            return;
        }

        // Check if WordPress user already exists
        if (!username_exists($username) && !email_exists($email)) {
            Civi::log()->debug("Creating User Account");

            // Prepare user data
            $user_data = array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass' => $password,
                'first_name' => $contact['first_name'],
                'last_name' => $contact['last_name'],
                'role' => 'volunteer_portal_user', // Assign the role at the time of user creation
            );

            // Create the WordPress user
            $userId = wp_insert_user($user_data);

            if (is_wp_error($userId)) {
                Civi::log()->debug("Error creating user: " . $userId->get_error_message());
                CRM_Core_Session::setStatus("Failed to Create User.");
                return; // Exit if there is an error creating the user
            }

            Civi::log()->debug("User Account Created Successfully with Role Assigned: userId {$userId}");

            // Check if a new CiviCRM contact has been automatically created for this user
            $newUfMatch = civicrm_api3('UFMatch', 'get', [
                'uf_id' => $userId,
            ]);

            if (!empty($newUfMatch['values'])) {
                $newUfMatchRecord = reset($newUfMatch['values']);
                $newContactId = $newUfMatchRecord['contact_id'];

                if ($newContactId != $contactId) {
                    // Duplicate contact detected, we need to fix the `UFMatch`
                    Civi::log()->debug("Duplicate contact created (ID: $newContactId), fixing UFMatch.");

                    // Update UFMatch to link the WordPress user to the correct contact
                    civicrm_api3('UFMatch', 'create', [
                        'id' => $newUfMatchRecord['id'],
                        'contact_id' => $contactId,
                    ]);

                    // Optionally delete the duplicate contact
                    civicrm_api3('Contact', 'delete', [
                        'id' => $newContactId,
                    ]);

                    Civi::log()->debug("Duplicate contact deleted, UFMatch updated to correct contact.");
                }
            }

            // Fetch the email template for account credentials
            $messageTemplate = civicrm_api4('MessageTemplate', 'get', [
                'where' => [
                    ['msg_title', '=', 'VP Account Credentials'],
                ],
                'checkPermissions' => FALSE,
            ]);

            // Check if template was found
            if (empty($messageTemplate) || !isset($messageTemplate[0]['id'])) {
                Civi::log()->error("Message Template 'VP Account Credentials' Not Found, Email Not Sent");
                return; // Exit if no template is found
            }

            $templateId = $messageTemplate[0]['id'];

            // Attempt to send the email
            try {
                $result = civicrm_api3('Email', 'send', [
                    'contact_id' => $contact['id'],
                    'template_id' => $templateId,
                    'from_email_option' => 2, // Set email address in From Email Addresses Options page
                ]);

                Civi::log()->debug("User Account Created & Email Sent Successfully");
                CRM_Core_Session::setStatus("User Account Created & Email Sent Successfully");
            } catch (CiviCRM_API3_Exception $e) {
                $error = [
                    'error' => true,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ];

                Civi::log()->debug("Failed to send email: " . json_encode($error));
                CRM_Core_Session::setStatus("User Account Created, Failed to Send Email.");
                return;
            }
        } else {
            Civi::log()->debug("WordPress user already exists");
            CRM_Core_Session::setStatus("WordPress username & email already exists");
        }
    }

}