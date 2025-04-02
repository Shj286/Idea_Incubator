# Idea Incubator

A web application that allows users to share, discuss, and vote on innovative ideas. Built with PHP, MySQL, and Bootstrap 5.

## Features

- User Authentication (Sign Up, Sign In, Sign Out)
- Post and Manage Ideas
- Categorize Ideas
- Like/Upvote Ideas
- Comment on Ideas
- Search and Filter Ideas
- User Profile Management
- Responsive Design

## Setup Instructions

1. **Database Setup**
   - Create a MySQL database
   - Import the database schema (if provided)

2. **Server Requirements**
   - PHP 7.0 or higher
   - MySQL 5.6 or higher
   - Web server (Apache/Nginx)

3. **Installation**
   - Clone the repository
   - Configure your database connection in `model/db.php`
   - Place the files in your web server directory

4. **Directory Structure**
   ```
   IdeaIncubator/
   ├── controller/
   │   └── controller.php
   ├── model/
   │   ├── db.php
   │   └── model.php
   └── view/
       ├── mainpage.html
       └── startpage.html
   ```

## Usage

1. Start at `startpage.html` to sign in or create an account
2. After authentication, you'll be redirected to `mainpage.html`
3. You can then:
   - Post new ideas
   - Edit/delete your own ideas
   - Like ideas
   - Comment on ideas
   - Search and filter ideas by category
   - Manage your profile

## Technologies Used

- Frontend:
  - HTML5
  - CSS3
  - JavaScript/jQuery
  - AJAX for asynchronous data handling
  - Bootstrap 5 for responsive design
  - Responsive UI with mobile-first approach
- Backend:
  - PHP
  - MySQL

## Security Features

- Session-based authentication
- Password hashing
- Input validation


## Contributing

Feel free to submit issues and enhancement requests!