# Simple Page Access

A WordPress plugin that allows you to control access to pages and posts for logged-in users with specific roles.

## Features

- Restrict access to individual posts and pages to logged-in users only
- Granular control: Select specific user roles that can access content
- Seamless integration with WordPress Block Editor (Gutenberg)
- Shows standard WordPress 404 page for unauthorized access
- Administrators always have access to all content

## Installation

1. Upload the `simple-page-access` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to any post or page in the Block Editor to configure access settings

## Usage

### Restricting a Page or Post

1. Edit a post or page in the WordPress Block Editor
2. In the right sidebar, scroll down to find the **"Simple Page Access"** panel
3. Check the box **"Restrict to logged in users only"** to enable access control
4. Optionally, select specific user roles that should have access:
   - Check one or more user roles (Subscriber, Contributor, Author, Editor, etc.)
   - If no roles are selected, any logged-in user can access the content
   - If specific roles are selected, only users with those roles can access the content

### Access Behavior

- **Non-logged-in users**: Will see a 404 page
- **Logged-in users without required role**: Will see a 404 page
- **Logged-in users with required role**: Can view the content normally
- **Administrators**: Always have full access regardless of settings

## Technical Details

- Compatible with WordPress Block Editor (Gutenberg)
- Uses WordPress post meta to store access settings
- Works with standard posts and pages
- Lightweight with no database tables required

## Requirements

- WordPress 5.0 or higher (Block Editor support)
- PHP 7.0 or higher

## Author

Created by [WP Minute](https://thewpminute.com/)

## Support

For issues or feature requests, please visit [thewpminute.com](https://thewpminute.com/)

## License

GPL v2 or later
