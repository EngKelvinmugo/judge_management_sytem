# Judge Management System

A PHP web application for managing judges and scoring participants.

---

## Features

- **Admin Panel:** Manage judges, add/edit/delete users.
- **Judge Portal:** Assign scores, add comments to participant evaluations.
- **Public Scoreboard:** View total points, leaderboards, with auto-refresh.
- **Dark/Light Mode:** Toggle theme with user preference remembered.
- **Authentication:** Session-based login for admins and judges.
- **Responsive Design:** Compatible with desktops and mobile devices.
- **Themed UI:** Consistent blue color scheme with accessible contrast.

---

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)

### Database Setup

1. **Create Database:**
   ```sql
   CREATE DATABASE judge_management_system;
   ```

2. **Import Schema & Sample Data:**
   ```bash
   mysql -u username -p judge_management_system < database.sql
   ```
   *(Replace `username` with your MySQL username)*

3. **Configure Database Connection:**

   Edit `config/db.php` to match your database credentials.

---

## Application Deployment

1. **Copy Files:**
   - Clone or download the repository.
   - Place files in your web server's document root or subdirectory e.g xampp/htdocs.

2. **Set Permissions:**
   - Directories: `755`
   - Files: `644`

3. **Access the Application:**
   - Via your browser at `http://your-server/path/`
   - Log in using default credentials:
     - **Admin:** `admin` / `admin123`
     - **Judges:** `judge1`, `judge2`, `judge3` / `judge123`

---

## Usage & Testing

### Login

- Navigate to the login page.
- Enter credentials based on role.
- Verify redirection and permissions.

### Admin Panel

- Log in as admin.
- Add/edit/delete judges.
- Manage users and roles.

### Judge Portal

- Log in as a judge.
- Select participants and assign scores.
- Add comments for detailed scoring.
- Update existing scores.

### Public Scoreboard

- View real-time leaderboard.
- See participant rankings based on total points.
- Auto-refresh every 10 seconds.
- Highlight top contenders.

### Theme Toggle

- Click toggle button in the header.
- Switch between dark and light modes.
- Preference persists across sessions.

---

## Design & Security

- **Architecture:** No framework, lightweight PHP with PDO.
- **UI:** Blue-themed, responsive, accessible.
- **Security:** Password hashing, prepared statements, input validation, session security.

---

## Future Enhancement Suggestions

- **Add User Management:** Admin can add/edit/delete users.
- **Password Reset:** Enable users to reset passwords via email.
- **Detailed Scoring:** Judges can add comments with scores.
- **Statistics Page:** Show insights about judges and scoring trends.
- **Export Data:** Download scoreboards as CSV or PDF for offline review.


---
### Visual References
Below are some snapshots demonstrating the system's interface and features:

![Screenshot 1](judge-management-system/assets/images/Screenshot%20from%202025-05-24%2018-49-44.png)  
Admin 1, e.g., Dashboard overview.

![Screenshot 2](judge-management-system/assets/images/Screenshot%20from%202025-05-24%2018-49-53.png)  
Admin 2, e.g., Add Judge page.

![Screenshot 3](judge-management-system/assets/images/Screenshot%20from%202025-05-24%2018-50-19.png)  
Admin & Judge  3, e.g.,  scoring interface.

![Screenshot 4](judge-management-system/assets/images/Screenshot%20from%202025-05-24%2018-50-51.png)  
Judge 4, e.g., Dashboard overview.

![Screenshot 5](judge-management-system/assets/images/Screenshot%20from%202025-05-24%2018-51-05.png)  
judge  5, e. Assign Score



