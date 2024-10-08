# Volunteer Portal Create Account (vp_create_account)

This extension creates a Volunteer Portal User role WordPress account after registration is accepted & sends an email containing the login credentials to the volunteer.

## Flow

1. Volunteer submits a Volunteer Registration Form

2. Admin reviews the volunteer

3. Admin accepts or rejects the registration

4. Assuming the registration is accepted, the volunteer becomes the organisation's volunteer and is able to start volunteering

5. A WordPress account is created for the volunteer with the Volunteer Portal User role

6. After the account is created, an email containing the login credentials is sent to the volunteer

7. The volunteer can log into the Volunteer Portal

## Configuration Page

1. Under Volunteer Portal >> Create Account Configuration

2. Enter organisation name to be used in the email

3. Select the Volunteer Registration Activity Type (E.g. Volunteer Registration Form)

4. Select Acceptance Status Custom Field (E.g. Acceptance Status)

5. Select the Acceptance Status value when a volunteer has been accepted to volunteer with the organisation (E.g. Accepted)

## Note

In order to send emails, there must be an SMTP server set up, where most sites uses WP Mail SMTP plugin. As such, any sites without SMTP or localhost would likely fail when sending emails.

To change the email content such as organisation name, you would need to edit the code in CMR/VPCreateAccount/Utils.php :: createVPUser.
You can also use this extension for other activity types, custom fields through the configuration page & editing Utils.php :: getAcceptanceStatus for custom field values.
Search for 'edit' in the files to see which parts should be edited if you want the extension for other entities.
Of course if editing this extension for your other use case is not the best method, feel free to use this as a boilerplate for your own extension.

This is an [extension for CiviCRM](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/), licensed under [AGPL-3.0](LICENSE.txt).
