# Bits Catholic Portal Community

Bits Catholic Portal Community is a PHP-based social networking platform designed for members to connect, share posts, comment, message, and build a faith-based community. The project features user authentication, profile management, posts, comments, likes, messaging, notifications, and more.

---

## Features

- **User Registration & Login**
  - Secure registration with validation and password hashing
  - Login with CSRF protection and session management

- **User Profiles**
  - View and edit profile (username, email, bio, profile picture)
  - Public profile pages accessible by username or ID

- **Posts**
  - Create, edit, and delete posts (with image/video upload support)
  - Like and share posts
  - View posts by user or in the main feed

- **Comments & Replies**
  - Add, edit, delete comments on posts
  - Like comments and reply to comments (threaded)

- **Messaging**
  - Private conversations between users
  - View message history and send new messages

- **Notifications**
  - Receive notifications for likes, comments, and other activities
  - Mark notifications as read

- **Bookmarks**
  - Bookmark posts for quick access

- **Activity Feed**
  - View recent activities and suggestions for new connections

- **Responsive UI**
  - Modern, mobile-friendly interface using Bootstrap and custom CSS

---

## Project Structure

```
community/
├── ajax/                # AJAX endpoints for comments, likes, etc.
├── assets/
│   ├── css/             # Stylesheets (style.css)
│   └── js/              # JavaScript (script.js)
├── config/
│   ├── .env             # Environment variables (DB config)
│   └── db.php           # Database connection and CSRF setup
├── includes/            # Shared UI components (header, footer, sidebar)
├── messages/            # Messaging system (send/view)
├── uploads/             # Uploaded images and videos
├── get_post.php         # View a single post
├── index.php            # Main feed (all posts)
├── login.php            # User login
├── logout.php           # User logout
├── mains.php            # User's own posts, comments, bookmarks
├── message.php          # List of conversations
├── notification.php     # User notifications
├── post.php             # Posts feed (AJAX loaded)
├── post_functions.php   # JS for post actions (edit, like, comment, share)
├── privacy.php          # Privacy policy
├── profile.php          # User profile page
├── register.php         # User registration
├── settings.php         # Account settings
├── terms.php            # Terms of service
├── user.php             # Public user profile (by username or ID)
└── README.md            # Project documentation
```

---

## Setup Instructions

1. **Clone the Repository**
   ```sh
   git clone <repo-url>
   cd community
   ```

2. **Database Setup**
   - Create a MySQL database (default: `forum_db`).
   - Import the required tables (users, posts, comments, likes, follows, messages, notifications, bookmarks, etc.).
   - Update `config/.env` with your DB credentials if needed.

3. **Configure Environment**
   - Ensure `config/db.php` matches your `.env` settings.
   - Set file permissions for the `uploads/` directory.

4. **Run Locally**
   - Use XAMPP, MAMP, or any PHP server.
   - Access via `http://localhost/community/`.

---

## Security

- CSRF protection on all forms and AJAX requests
- Passwords hashed with bcrypt
- Input validation and sanitization throughout
- Session management and secure cookies

---

## Customization

- Update branding and logo in `logo.jpg`
- Modify styles in `assets/css/style.css`
- Adjust navigation and layout in `includes/`

---

## License

This project is for educational and community-building purposes. Please contact the maintainer for reuse or contributions.

---

## Credits

- Built with PHP, MySQL, Bootstrap, and jQuery
- Inspired by modern social platforms and tailored for faith-based communities

---

*For questions or support, contact [info@bitscatholicportal.co.ke](mailto:info@bitscatholicportal.co.ke)
