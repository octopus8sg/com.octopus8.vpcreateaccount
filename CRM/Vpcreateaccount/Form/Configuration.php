<?php
use CRM_Vpcreateaccount_ExtensionUtil as E;
use CRM_Vpcreateaccount_Utils as U;

class CRM_Vpcreateaccount_Form_Configuration extends CRM_Core_Form
{
    public function buildQuickForm()
    {
        // Fetch the activity types
        $activityTypes = civicrm_api3('OptionValue', 'get', [
            'option_group_id' => 'activity_type',
            'options' => ['limit' => 0],
        ]);

        $activityOptions = [];
        $activityOptionsName = []; // Store name as the identifier
        foreach ($activityTypes['values'] as $type) {
            $activityOptions[$type['value']] = $type['label'];
            $activityOptionsName[$type['name']] = $type['label'];
        }

        $this->add(
            'text',
            'email_org_name',
            E::ts('For Email: Your Organisation Name')
        );

        // Add the multi-select field for activity types
        $this->add(
            'select',
            'volunteer_registration_activity_type',
            E::ts('Volunteer Registration Activity Types'),
            $activityOptions,
            FALSE,
            [
                'class' => 'huge crm-select2',

                'multiple' => FALSE,
                'placeholder' => ts('- Select Type -'),
                'select' => ['minimumInputLength' => 0]
            ]
        );

        $this->addEntityRef('acceptance_status_field', ts('Select Custom Field for Acceptance Status'), [
            'entity' => 'CustomField',
            'api' => [
                'params' => [
                    'is_active' => 1,
                    'custom_group_id.extends' => 'Activity', // Only fetch custom fields under the Volunteer_Logging_Attendance custom group
                    'data_type',
                    '=',
                    'String',
                    'return' => [
                        'label',
                        'column_name',
                        'help_pre',
                        'custom_group_id',
                        'custom_group_id.title',
                    ], // Ensure the custom group title is returned
                ],
                'search_fields' => [
                    'custom_group_id.label',
                    'custom_group_id.name',
                    'label',
                    'column_name',
                ], // Include custom group name and other relevant fields in search
                'label_field' => 'label', // Label to display in the dropdown
                'description_field' => [
                    'column_name',
                    'help_pre',
                    'custom_group_id.title', // Add custom group title to the description
                ],
            ],
            'select' => ['minimumInputLength' => 1], // Set minimum characters before search starts
            'class' => 'huge', // CSS class to style the input field
            'placeholder' => ts('- Select Custom Fields -'), // Placeholder text
        ]);

        $this->add(
            'select',
            'acceptance_status_value',
            ts('Select Acceptance Status Value'),
            [],
            FALSE,
            [
                'class' => 'crm-select2',
                'multiple' => FALSE,
            ]
        );

        // Pass the saved acceptance status value to JavaScript
        // $savedStatusValue = U::getSettings('acceptance_status_value');
        // CRM_Core_Resources::singleton()->addScript("
        // var savedAcceptanceStatusValue = '" . ($savedStatusValue ? $savedStatusValue : '') . "';
        // ");

        $this->addButtons([
            [
                'type' => 'submit',
                'name' => ts('Save'),
                'isDefault' => TRUE,
            ],
            [
                'type' => 'cancel',
                'name' => ts('Cancel'),
            ],
        ]);
        CRM_Core_Resources::singleton()->addScriptFile('com.octopus8.vpcreateaccount', 'js/configuration.js');

        // export form elements
        $renderableElementNames = $this->getRenderableElementNames();

        $this->assign('elementNames', $renderableElementNames);
        $this->assign('suppressForm', FALSE);
        parent::buildQuickForm();
    }

    public function postProcess()
    {
        $values = $this->exportValues();
        $emailOrgName = $values['email_org_name'];
        $selectedVRActivityType = $values['volunteer_registration_activity_type'];
        $selectedAcceptanceStatusField = $values['acceptance_status_field'];
        $selectedAcceptanceStatusValue = $values['acceptance_status_value'];

        U::setSettings('email_org_name', $emailOrgName);
        U::setSettings('volunteer_registration_activity_type', $selectedVRActivityType);
        U::setSettings('acceptance_status_field', $selectedAcceptanceStatusField);
        U::setSettings('acceptance_status_value', $selectedAcceptanceStatusValue);

        $setEmailOrgName = U::getSettings('email_org_name');
        $setVRActivityType = U::getSettings('volunteer_registration_activity_type');
        $setAcceptanceStatusField = U::getSettings('acceptance_status_field');
        $setAcceptanceStatusValue = U::getSettings('acceptance_status_value');


        $status_str = ts('Email Organisation Name: ') . $setEmailOrgName
            . ", " . ts('Selected Volunteer Registration Activity Type: ') . $setVRActivityType
            . ", " . ts('Selected Acceptance Status Field: ') . $setAcceptanceStatusField
            . ", " . ts('Selected Acceptance Status Value: ') . $setAcceptanceStatusValue
        ;

        $js =
            "(function($) {
                $(document).ready(function() {
                    $('form').hide();
                    window.location.reload();
                });
            })(CRM.$);";
        CRM_Core_Resources::singleton()->addScript($js, 'inline');
        CRM_Core_Session::setStatus($status_str);
    }

    public function setDefaultValues()
    {
        $defaults = [];
        $settings = CRM_Core_BAO_Setting::getItem(U::SETTINGS_NAME, U::SETTINGS_SLUG);
        //        U::writeLog($settings, "starting values");
        if (!empty($settings)) {
            $defaults = $settings;

            // Set the saved value of acceptance_status_value to be passed to the form
            if (!empty($settings['acceptance_status_value'])) {
                Civi::log()->debug($settings['acceptance_status_value']);
                $defaults['acceptance_status_value'] = $settings['acceptance_status_value'];
            }
        }

        // Update the value to be passed to JS, even after form submit
        $savedStatusValue = $settings['acceptance_status_value'];
        CRM_Core_Resources::singleton()->addScript("
        var savedAcceptanceStatusValue = '" . ($savedStatusValue ? $savedStatusValue : '') . "';
        ");

        return $defaults;
    }

    /**
     * Get the fields/elements defined in this form.
     *
     * @return array (string)
     */
    public function getRenderableElementNames()
    {
        // The _elements list includes some items which should not be
        // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
        // items don't have labels.  We'll identify renderable by filtering on
        // the 'label'.
        $elementNames = array();
        foreach ($this->_elements as $element) {
            /** @var HTML_QuickForm_Element $element */
            $label = $element->getLabel();
            if (!empty($label)) {
                $elementNames[] = $element->getName();
            }
        }
        return $elementNames;
    }

}