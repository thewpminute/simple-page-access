# Simple Page Access

Simple Page Access lets you lock individual posts and pages so only logged-in users, or logged-in users with selected roles, can view them.

## What it does

- Adds a **Simple Page Access** panel in the Block Editor sidebar
- Lets you require login for a single post or page
- Lets you optionally allow only selected roles
- Returns a standard WordPress `404` for unauthorized visitors
- Keeps administrators/ site managers able to access restricted content

## Quick start

1. Upload the `simple-page-access` folder to `/wp-content/plugins/`.
2. Activate **Simple Page Access** in **Plugins**.
3. Edit a post or page in the Block Editor.
4. Open the right sidebar and find **Simple Page Access**.
5. Enable **Restrict to logged in users only**.
6. Optional: check one or more roles in **Allowed User Roles**.
7. Update or publish the post/page.

## How access rules work

| Setting | Who can access |
|---|---|
| Restriction **OFF** | Everyone (public) |
| Restriction **ON**, no roles selected | Any logged-in user |
| Restriction **ON**, roles selected | Logged-in users with at least one selected role |
| Restriction **ON** | Administrators/site managers still have access |

Unauthorized users receive a `404` response instead of the content.

## Where protection applies

Protection is enforced for restricted posts/pages on:

- Direct post/page views
- WordPress REST API responses for posts/pages
- Post/page listings that use normal WordPress queries (for example archives, search, and feeds)

## Requirements

- WordPress 5.0 or higher (Block Editor / Gutenberg)
- PHP 7.0 or higher

## Troubleshooting

- **You cannot see the settings panel**: Confirm you are editing a post or page in the Block Editor (not Classic Editor).
- **Restriction seems inactive**: Make sure the post/page is updated after toggling settings.
- **A role cannot access content**: Verify the user account actually has that role on this site.
- **You are testing while logged in as admin**: Admin/site manager accounts bypass restrictions by design.

## Support

For issues or feature requests, visit [thewpminute.com](https://thewpminute.com/).

## License

GPL v2 or later
