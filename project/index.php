<?php
session_start();
// If already logged in, send to the correct dashboard
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'parent') {
        header("Location: parent.php");
        exit();
    } elseif ($_SESSION['user_role'] === 'admin') {
        header("Location: admin.php");
        exit();
    } elseif ($_SESSION['user_role'] === 'health worker') {
        header("Location: health worker.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CareVax - Welcome</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Segoe UI", sans-serif;
    }

    body {
      color: #fff;
      line-height: 1.6;
    }

    /* Hero Section */
    .hero-section {
      position: relative;
      height: 100vh;
      background: url("vaccines.jpg") no-repeat center center/cover;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
    }

    .overlay {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.6);
    }

    .hero-content {
      position: relative;
      z-index: 1;
    }

    .logo {
      font-size: 3rem;
      color: #85b5e5ff;
      font-weight: bold;
      margin-bottom: 1rem;
    }

    .hero-content h1 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }

    .hero-content p {
      font-size: 1.02rem;
      margin-bottom: 2rem;
    }

    .hero-buttons .index-btn {
      padding: 12px 25px;
      margin: 0 10px;
      background: #2196f3;
      border-radius: 30px;
      text-decoration: none;
      color: #fff;
      font-weight: bold;
      transition: 0.3s;
    }

    .hero-buttons .signup {
      background: #4caf50;
    }

    .hero-buttons .index-btn:hover {
      background: #1976d2;
    }

    /* Features */
    .features {
      background: #f5f5f5;
      color: #333;
      padding: 50px 20px;
      text-align: center;
    }

    .features h2 {
      font-size: 2.2rem;
      margin-bottom: 40px;
    }

    .feature-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
    }

    .feature-card {
      background: #fff;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      transition: 0.3s;
    }

    .feature-card img {
      max-width: 70px;
      margin-bottom: 15px;
    }

    .feature-card:hover {
      transform: translateY(-8px);
    }

    /* Footer */
    footer {
      background: #222;
      text-align: center;
      padding: 15px;
      font-size: 0.9rem;
      color: #ddd;
    }
  </style>
</head>
<body>
  <!-- Hero Section -->
  <div class="hero-section">
    <div class="overlay"></div>
    <div class="hero-content">
      <div class="logo">💉 CAREVAX</div>
      <h1>Welcome to CareVax</h1>
      <p>Your Digital Immunization Management System</p>
      <div class="hero-buttons">
       <a href="login.php" class="index-btn">Login</a>
        <a href="signup.php" class="index-btn signup">Sign Up</a>
      </div>
    </div>
  </div>

  <!-- Features Section -->
  <section class="features">
    <h2>Why Choose CareVax?</h2>
    <div class="feature-grid">
      <div class="feature-card">
        <img src="security.png" alt="Security">
        <h3>Secure Authentication</h3>
        <p>Keep your health data safe with modern security measures.</p>
      </div>
      <div class="feature-card">
        <img src="family.jpg" alt="Family">
        <h3>Admin & Health Worker Dashboards</h3>
        <p>Custom dashboards for  admins, and health workers.</p>
      </div>
      <div class="feature-card">
        <img src="calendar.jpg" alt="Calendar">
        <h3>Vaccination Schedules</h3>
        <p>Stay updated with upcoming immunization schedules.</p>
      </div>
      <div class="feature-card">
        <img src="report.jpg" alt="Reports">
        <h3>Reports & Analytics</h3>
        <p>Generate meaningful insights and health statistics.</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <p>&copy; <?php echo date("Y"); ?> CareVax. All Rights Reserved.</p>
  </footer>

  <script>
    // Simple hero animation
    document.addEventListener("DOMContentLoaded", () => {
      const heroText = document.querySelector(".hero-content h1");
      heroText.style.opacity = "0";
      heroText.style.transform = "translateY(30px)";
      setTimeout(() => {
        heroText.style.transition = "all 1s ease";
        heroText.style.opacity = "1";
        heroText.style.transform = "translateY(0)";
      }, 500);
    });
  </script>
</body>
</html>
