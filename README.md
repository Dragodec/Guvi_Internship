# User Authentication & Profile Management System

A full-stack web application built as part of the **GUVI Internship assignment**. The application implements user registration, login authentication, session management, profile viewing, and profile updating.

## 📋 Problem Statement

Create a signup page where a user can register and a login page where the user can authenticate using their registered credentials.

After a successful login, the user is redirected to a profile page containing additional details such as age, date of birth, and contact information. The user can also update their profile details.

**Application Flow:**

`Register → Login → Profile → Update Profile`

The project follows the required internship guidelines:

* HTML, CSS, JavaScript, and PHP are maintained in separate files.
* jQuery AJAX is used for frontend-backend communication.
* Traditional form submission is not used.
* Bootstrap is used for responsive form design.
* MySQL uses prepared statements.
* Browser `localStorage` is used for client-side session token storage.
* Redis stores and validates backend session information.
* MongoDB stores user profile data.

---

## 🛠️ Tech Stack

| Layer                              | Technology             |
| ---------------------------------- | ---------------------- |
| Frontend Structure                 | HTML5                  |
| Styling                            | CSS3 + Bootstrap       |
| Client-Side Logic                  | JavaScript             |
| AJAX                               | jQuery AJAX            |
| Backend                            | PHP                    |
| Registration & Authentication Data | MySQL                  |
| Profile Data                       | MongoDB                |
| Session Storage                    | Redis                  |
| Client Session Storage             | Browser `localStorage` |

---

## 📁 Project File Structure

```text
GUVI_Internship/
└── Code/
    │
    ├── Frontend/
    │   │
    │   ├── Css/
    │   │   └── Index.css
    │   │
    │   ├── Html/
    │   │   ├── Index.html
    │   │   ├── LoginPage.html
    │   │   ├── ProfilePage.html
    │   │   ├── RegisterPage.html
    │   │   └── UpdateProfilePage.html
    │   │
    │   └── Js/
    │       ├── LoginScript.js
    │       ├── ProfileScript.js
    │       ├── RegisterScript.js
    │       └── UpdateProfileScript.js
    │
    └── Backend/
        │
        ├── Config/
        │   ├── CorsConfig.php
        │   └── DevConfig.php
        │   └── RateLimiter.php
        │
        ├── Controllers/
        │   ├── Login.php
        │   ├── Logout.php
        │   ├── Profile.php
        │   ├── Register.php
        │   └── UpdateProfile.php
        │
        ├── Database/
        │   ├── Mongo.php
        │   ├── MySQL.php
        │   └── Redis.php
        │
        └── Tests/
            ├── DevConfigTest.php
            ├── MongoSetup.php
            └── MongoTest.php
```

---

## ✨ Features

### 🔐 User Registration

* Register a new user through the registration page.
* User registration data is stored in **MySQL**.
* MySQL prepared statements are used for database operations.
* Passwords are securely handled before storage.

### 🔑 User Login

* Authenticate users using their registered credentials.
* Login credentials are validated against data stored in **MySQL**.
* Successful authentication generates a session token.
* Session information is stored in **Redis**.

### 🧠 Session Management

* Browser `localStorage` stores the client-side session token.
* Redis maintains session information on the backend.
* PHP native sessions are not used.
* Protected requests validate the user's session through Redis.
* Users can log out and terminate their active session.

### 👤 Profile Management

* View user profile details after successful authentication.
* Extended profile information is stored in **MongoDB**.
* Users can view details such as:

  * Name
  * Contact number
  * Date of birth
  * Age

### ✏️ Profile Update

Users can update selected profile information:

* Name
* Contact number
* Date of birth

The updated profile data is persisted in **MongoDB**.

### 🎂 Dynamic Age Calculation

* Age is calculated dynamically using the user's date of birth.
* Age does not need to be independently maintained as profile data.

### 🔄 AJAX-Based Communication

* Frontend-backend communication uses **jQuery AJAX**.
* No traditional form submission is used for backend interaction.
* Backend responses are handled dynamically on the client side.

### 🧩 Separated Project Architecture

The project maintains clear separation between:

* HTML page structure
* CSS styling
* JavaScript client-side logic
* PHP backend controllers
* Database connection logic
* Configuration files
* Test files

---

## 👤 Author

**Ahil**

Final Year Computer Science Student

**GUVI Internship Submission**
