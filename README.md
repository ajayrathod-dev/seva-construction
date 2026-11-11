# 🏗️ Seva Construction - Multilingual Website & Admin CMS

A modern, responsive, and multilingual web application built for **Seva Construction**, specializing in professional civil construction, brick masonry, plastering, and custom house design. The platform includes an interactive customer-facing portal and a secure admin content management dashboard.

---

## 🌟 Key Features

- **🌐 Multilingual Support**: Seamless on-the-fly switching between **English**, **हिन्दी (Hindi)**, and **मराठी (Marathi)**.
- **🖼️ Dynamic Hero Slideshow**: Configurable promotional carousel with custom headers and subtitles.
- **🧱 Project Portfolio Showcase**: Filterable gallery categorized by Brickwork, Plastering, House Design, Full Construction, and Renovation.
- **📱 Fully Responsive UI**: Crafted with **Tailwind CSS**, optimized for mobile, tablet, and desktop viewing.
- **🔐 Admin Management Dashboard**:
  - Secure authentication with session management.
  - Project management (add, edit, delete, categorize).
  - Automated image resizing and aspect-ratio maintenance (JPEG, PNG, WebP).
  - Appearance customizer (custom background images and site branding).
  - Slideshow banner manager.
- **📞 Quick Contact & WhatsApp Integration**: Direct call and WhatsApp inquiry CTAs for instant client engagement.

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.x
- **Database**: MySQL / MariaDB (Prepared statements with `mysqli`)
- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript, FontAwesome 6
- **Typography**: Google Fonts (*Outfit* and *Inter*)
- **Image Processing**: PHP GD Library

---

## 📂 Project Structure

```text
Seva_construction/
├── admin/                     # Admin control panel
│   ├── appearance.php         # Appearance and background settings
│   ├── dashboard.php          # Main project management dashboard
│   ├── login.php              # Secure admin login
│   ├── logout.php             # Session termination
│   └── slideshow.php          # Hero carousel manager
├── lang/                      # Localization dictionary files
│   ├── en.php                 # English translations
│   ├── hi.php                 # Hindi translations
│   └── mr.php                 # Marathi translations
├── uploads/                   # Media upload storage directory
├── connection.php             # Centralized database configuration and helper functions
├── database.sql               # MySQL database schema and initial seed data
├── index.php                  # Main customer-facing homepage
├── lang.php                   # Language session controller
├── .gitignore                 # Git ignore rules
└── README.md                  # Project documentation
```

---

## 🚀 Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP 8.0+ + MySQL stack)
- PHP GD extension enabled (default in XAMPP)
- Git

### Installation Steps

1. **Clone the repository**:
   ```bash
   git clone <YOUR_GITHUB_REPO_URL> Seva_construction
   ```
   *(Place inside your web server root, e.g., `C:/xampp/htdocs/Seva_construction`)*

2. **Database Setup**:
   - Start **Apache** and **MySQL** from your XAMPP Control Panel.
   - Open [phpMyAdmin](http://localhost/phpmyadmin/).
   - Create a database named `seva_construction` or import `database.sql` directly:
     ```bash
     mysql -u root -p seva_construction < database.sql
     ```

3. **Configure Database Connection**:
   - Open `connection.php` and verify your credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'seva_construction');
     ```

4. **Run the Application**:
   - **Frontend**: Open `http://localhost/Seva_construction/`
   - **Admin Panel**: Open `http://localhost/Seva_construction/admin/login.php`

---

## 🔑 Default Admin Credentials

| Username | Default Password |
|:---|:---|
| `admin` | `password` *(or `seva@2026` depending on your seed configuration)* |

> ⚠️ *Important: Make sure to update the default password after your initial deployment.*

---

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).

