<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Business Permit System</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .logo {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        p {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .dashboard-grid {
            display: grid;
            gap: 20px;
            margin-top: 30px;
        }

        .dashboard-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            display: block;
        }

        .dashboard-card:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .dashboard-card h3 {
            margin: 0 0 8px 0;
            font-size: 1.3rem;
        }

        .dashboard-card p {
            margin: 0;
            font-size: 0.95rem;
            opacity: 0.8;
        }

        .dashboard-card:hover p {
            opacity: 1;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🏢</div>
        <h1>Online Business Permit System</h1>
        <p>Select your dashboard to get started</p>

        <div class="dashboard-grid">
            <a href="/Applicant-dashboard/index.php" class="dashboard-card">
                <h3>👤 Applicant Portal</h3>
                <p>Apply for business permits, track applications, and manage your submissions</p>
            </a>

            <a href="/Staff-dashboard/index.php" class="dashboard-card">
                <h3>👨‍💼 Staff Dashboard</h3>
                <p>Review applications, process permits, and manage workflow</p>
            </a>

            <a href="/Admin-dashboard/index.php" class="dashboard-card">
                <h3>⚡ Admin Panel</h3>
                <p>System administration, user management, and analytics</p>
            </a>
        </div>

        <div class="footer">
            <p>Powered by Firebase & Vercel</p>
        </div>
    </div>
</body>
</html>
