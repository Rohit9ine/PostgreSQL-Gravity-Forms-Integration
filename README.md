# PostgreSQL Gravity Forms Integration

## Description
The **PostgreSQL Gravity Forms Integration** plugin checks promocodes or any fields against a PostgreSQL database when a Gravity Form is submitted and populates results in a specific field.

## Features
- Integrates with Gravity Forms.
- Queries a PostgreSQL database for a reservation code.
- Displays custom messages based on the query results.
- Provides an admin settings page to configure form and field IDs, custom messages, and display options.

## Installation
1. Download the plugin files.
2. Upload the entire plugin folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Configure the plugin settings under the 'PosgresqlWP' menu in the WordPress admin dashboard.

## Usage
1. Create a Gravity Form that includes a field for inputting the reservation code.
2. Set the form ID, reservation code field ID, result field ID, and custom messages in the plugin settings.
3. Submit the Gravity Form to check the reservation code against the PostgreSQL database.

## Admin Settings
- **Gravity Form ID:** The ID of the Gravity Form to check.
- **Reservation Code Field ID:** The field ID where users input their reservation code.
- **Result Field ID:** The field ID where the result message will be populated.
- **Found Message:** Custom message to display if the reservation code is found.
- **Not Found Message:** Custom message to display if the reservation code is not found.
- **Display Result:** Checkbox to display the reservation code result above the confirmation message.


## Files
- `postgresql-gravity-forms-integration.php`: Main plugin file that contains the logic for checking reservation codes and rendering the settings page.

## Author
**Rohit Kumar**

## Changelog
### Version 1.0
- Initial release.
