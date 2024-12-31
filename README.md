# Volunteer Portal Create Account Extension (vpcreateaccount)

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

2. Select the Volunteer Registration Activity Type (E.g. Volunteer Registration Form)

3. Select Acceptance Status Custom Field (E.g. Acceptance Status)

4. Select the Acceptance Status value when a volunteer has been accepted to volunteer with the organisation (E.g. Accepted)

## Note

In order to send emails, there must be an SMTP server set up, where most sites uses WP Mail SMTP plugin. As such, any sites without SMTP or localhost would likely fail when sending emails.

This extension sends emails through API3's send email call and uses a Message Template (VP Account Credentials). Refer to [Message Template](https://demo.socialservicesconnect.com/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Fadmin%2FmessageTemplates%2Fadd&action=update&id=87&reset=1) if you would like to create new or edit the existing template.

This is an [extension for CiviCRM](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/), licensed under [AGPL-3.0](LICENSE.txt).
