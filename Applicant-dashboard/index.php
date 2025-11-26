<?php
// index.php - Modern Get Started Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>OnlineBizPermit - Get Started</title>
  <style>
    /* General Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #5d9eff, #2a73ff);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      color: #333;
    }

    .container {
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 12px 28px rgba(0,0,0,0.15);
      overflow: hidden;
      width: 90%;
      max-width: 1100px;
      animation: fadeIn 1s ease-in-out;
    }

    /* Left Section */
    .left {
      flex: 1;
      padding: 50px 40px;
      text-align: center;
      background: linear-gradient(to bottom right, #f0f7ff, #ffffff);
    }

    .left h1 {
      font-size: 24px;
      color: #2a2a2a;
      margin-bottom: 25px;
      font-weight: bold;
      letter-spacing: 1px;
    }

    .left img {
      width: 80%;
      max-width: 250px;
      margin: 0 auto 20px;
      display: block;
      transition: transform 0.3s ease;
    }
    .left img:hover {
      transform: scale(1.05);
    }

    .left p {
      font-size: 15px;
      color: #555;
      line-height: 1.7;
      margin: 15px 0 25px;
    }

    /* Button */
    .btn {
      background: linear-gradient(135deg, #4a73f3, #2e56d3);
      color: #fff;
      border: none;
      padding: 12px 30px;
      border-radius: 50px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      text-decoration: none;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
      display: inline-block;
    }
    .btn:hover {
      background: linear-gradient(135deg, #3357d6, #1c3fb3);
      transform: translateY(-3px);
      box-shadow: 0 6px 16px rgba(0,0,0,0.25);
    }

    /* Right Section */
    .right {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      background: #fff;
      padding: 40px;
    }

    .right img {
      width: 100%;
      max-width: 480px;
      border-radius: 14px;
      transition: transform 0.4s ease;
    }
    .right img:hover {
      transform: scale(1.03);
    }

    /* Animation */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive */
    @media (max-width: 900px) {
      .container {
        flex-direction: column;
        text-align: center;
      }
      .left, .right {
        flex: unset;
        width: 100%;
      }
      .right img {
        max-width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Left Section -->
    <div class="left">
      <h1>ONLINEBIZ PERMIT</h1>
      <img src="logo.png" alt="Business Illustration">
      <p>
        Welcome to our platform that revolutionizes business permit applications 
        and monitoring for municipalities. Experience a new level of efficiency 
        and transparency in licensing procedures.
      </p>
      <a href="login.php" class="btn">🚀 Get Started</a>
    </div>
    
    <!-- Right Section -->
    <div class="right">
      <img src="office.png.png" alt="Office Illustration">
    </div>
  </div>
</body>
</html>