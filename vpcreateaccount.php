<?php

require_once 'vpcreateaccount.civix.php';

use CRM_Vpcreateaccount_ExtensionUtil as E;
use CRM_Vpcreateaccount_Utils as U;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function vpcreateaccount_civicrm_config(&$config): void
{
  _vpcreateaccount_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function vpcreateaccount_civicrm_install(): void
{
  _vpcreateaccount_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function vpcreateaccount_civicrm_enable(): void
{
  _vpcreateaccount_civix_civicrm_enable();
}

function vpcreateaccount_civicrm_navigationMenu(&$menu)
{
  _vpcreateaccount_civix_insert_navigation_menu($menu, '', [
    'label' => E::ts('Volunteer Portal'),
    'icon' => 'crm-i fa-heart',
    'name' => 'vp',
    'permission' => 'administer CiviCRM',
  ]);

  _vpcreateaccount_civix_insert_navigation_menu($menu, 'vp', [
    'label' => E::ts('Create Account Configuration'),
    'name' => 'vpca_configuration',
    'url' => 'civicrm/vpcreateaccount/configuration',
    'permission' => 'administer CiviCRM',
  ]);

  _vpcreateaccount_civix_navigationMenu($menu);
}

function vpcreateaccount_civicrm_postCommit($op, $objectName, $objectId, &$objectRef)
{
  // Civi::log()->debug("Post Commit: op = {$op}, objectName = {$objectName}, objectId = {$objectId}, objectRef = " . json_encode($objectRef, JSON_PRETTY_PRINT));

  if ($objectName === "Activity" && $op === "edit") {
    // $activityType = U::getActivityType($objectRef->activity_type_id);
    $activityTypeId = $objectRef->activity_type_id;
    $volunteerRegistrationAT = (int) U::getSettings('volunteer_registration_activity_type');

    if ($activityTypeId === $volunteerRegistrationAT) {
      $acceptanceStatus = U::getAcceptanceStatus($objectId);
      Civi::log()->debug("Acceptance Status: {$acceptanceStatus}");
      $acceptanceStatusValue = U::getSettings('acceptance_status_value');
      if ($acceptanceStatus === $acceptanceStatusValue) {
        $contactId = U::getContactIdByActivityId($objectId);
        Civi::log()->debug("ContactId: {$contactId}");
        U::createVPUser($contactId);
      }
    } else {
      Civi::log()->debug("Not a Volunteer Registration Form");
    }
  }
}