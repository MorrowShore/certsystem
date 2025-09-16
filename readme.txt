=== Certificate System: Independent Certification ===
Contributors: Morrow Shore
Tags: certificate, verification, independent, credentials, certification, validation, digital certificates
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0
License: AGPLv3 or later
License URI: https://www.gnu.org/licenses/agpl-3.0.en.html

A comprehensive independent certification system for creating, managing, and verifying digital certificates with 10-digit unique IDs and QR code validation.

== Description ==
Certificate System: Independent Certification is a powerful WordPress plugin designed for organizations, educational institutions, and certification bodies that need to issue and verify independent digital certificates. The system generates secure 10-digit certificate IDs, creates scannable QR codes for instant verification, and provides a complete management system for certificate lifecycle.

== Features ==
* **Secure 10-Digit Certificate IDs** - Auto-generated unique identifiers for each certificate
* **QR Code Verification** - Instant mobile scanning for certificate validation
* **Complete Certificate Management** - Add, edit, delete, and bulk manage certificates
* **Frontend Verification Portal** - Public verification form with [certsystem] shortcode
* **Bulk CSV Import/Export** - Efficient management of large certificate databases
* **Professional Templates** - Customizable certificate design templates
* **Mobile Responsive** - Works perfectly on all devices and screen sizes
* **Advanced Security** - Nonce validation, input sanitization, and secure database operations
* **Real-time Validation** - Instant verification with visual confirmation indicators

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/certification/` directory, or install the plugin through the WordPress plugins screen directly
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to "Certificate System" in your WordPress admin menu
4. Start creating certificates or use bulk import for multiple entries
5. Add the verification form to any page using the `[certsystem]` shortcode

== Usage ==

= Creating Certificates =
1. Go to Certificate System → Manage Certificates
2. Click "Add New Certificate" 
3. Fill in student details, course/project information, and completion date
4. Leave Certificate ID blank to auto-generate a secure 10-digit code
5. Save to create the certificate with QR code

= Bulk Operations =
* Use the CSV import feature to upload multiple certificates
* Export existing certificates for backup or external processing
* Manage certificates in bulk with select-all and batch operations

= Verification =
* Add `[certsystem]` to any page for public verification
* Users enter their 10-digit certificate ID to validate
* QR codes can be scanned for instant mobile verification

== Frequently Asked Questions ==

= How are certificate IDs generated? =
The system automatically generates secure 10-character IDs (4 random characters + 6 date digits) when left blank during creation.

= Can I use my own certificate IDs? =
Yes, you can enter custom 10-character IDs, but we recommend using auto-generation for security.

= How does QR code verification work? =
Each certificate generates a unique QR code that links to its verification page. Scanning the code instantly validates the certificate.

= Is the system secure for sensitive data? =
Yes, the plugin follows WordPress security standards with input validation, nonce protection, and secure database operations.

= Can I customize the certificate design? =
Yes, use the Template Builder to create custom certificate designs with your branding.

= Can I export my certificate database? =
Yes, you can export all certificates as CSV for backup or external processing.


== Changelog ==

= 1.0 =
* Initial release of Certificate System: Independent Certification
* Complete certificate management system with secure 10-digit ID generation
* QR code integration for mobile verification
* Frontend verification portal with [certsystem] shortcode
* Bulk CSV import/export functionality
* Professional certificate templates with customization options
* Advanced security features and input validation
* Mobile responsive design
* Admin dashboard for certificate lifecycle management

== License ==
This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this plugin. If not, see <https://www.gnu.org/licenses/>.

== Support ==
For support, feature requests, or bug reports, contact us at https://morrowshore.com

== Contributing ==

We welcome contributions! Please follow WordPress coding standards and security best practices when submitting patches or feature additions through the WordPress plugin repository.
