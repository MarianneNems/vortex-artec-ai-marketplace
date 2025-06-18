# Collector Forum Documentation

The Collector Forum feature allows marketplace users to create and engage in discussions about projects, offers, and events. This functionality is designed to foster community interaction and collaboration within the marketplace.

## Features

- **Post Types**: Users can create three types of posts:
  - **Projects**: For commissioning artwork or creative collaborations
  - **Offers**: For offering services or products to the community
  - **Events**: For announcing upcoming events, exhibitions, or auctions

- **Public Visibility**: All posts are visible to all users (logged in or not)
- **Interactive Responses**: Logged-in users can respond to open posts
- **Post Management**: Post creators can close posts when they're complete

## Shortcodes

### Main Forum Shortcode

To display the complete forum with all posts, use:

```
[vortex_forum]
```

#### Optional Parameters:

- `post_type`: Filter posts by type ('all', 'project', 'offer', 'event')
- `limit`: Number of posts to display per page (default: 10)
- `status`: Filter posts by status ('all', 'open', 'closed')

Example:
```
[vortex_forum post_type="project" limit="5" status="open"]
```

### Create Post Shortcode

To display only the post creation form:

```
[vortex_create_post]
```

#### Optional Parameters:

- `type`: Pre-select a specific post type ('project', 'offer', 'event')

Example:
```
[vortex_create_post type="event"]
```

## Implementation

To implement the Collector Forum on your site:

1. Create a new page for the forum
2. Add the `[vortex_forum]` shortcode to the page
3. Publish the page and add it to your menu

## User Guide

### Viewing Posts

- All users can browse and view posts without logging in
- Use the tabs to filter by post type (All, Projects, Offers, Events)
- Use the search box to find specific posts
- Filter by status (Open/Closed) using the dropdown

### Creating a Post

1. Click the "Create Post" button
2. Select the post type
3. Fill in the required information:
   - **Title**: A clear, descriptive title
   - **Description**: Detailed information about your post
   - **Budget** (for Projects/Offers): Optional budget amount
   - **Deadline** (for Projects/Events): Optional deadline date
   - **Skills Required** (for Projects): Optional list of required skills
   - **Attachments**: Optional files to supplement your post

4. Click "Create Post" to publish

### Responding to Posts

1. Open a post by clicking on its title
2. Scroll down to the response form
3. Enter your message
4. Add attachments if needed
5. Click "Submit Response"

### Managing Your Posts

As the creator of a post, you can:

1. Close a post when it's complete or no longer relevant
2. Reopen a closed post if needed

## Technical Information

The Collector Forum functionality is implemented using:

- Custom database tables for posts and responses
- AJAX for seamless user experience
- Responsive design for mobile compatibility

## Troubleshooting

If you encounter issues with the forum:

1. Ensure you're using the latest version of the plugin
2. Check that all required database tables are properly created
3. Verify that JavaScript is enabled in your browser
4. Check the console for any JavaScript errors

For further assistance, contact the plugin support team. 