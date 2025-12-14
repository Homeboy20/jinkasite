<?php
/**
 * Setup Landing Page
 * Shows if database is not connected
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Required - JINKA Plotter</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .logo {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .setup-steps {
            text-align: left;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .step {
            display: flex;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .step:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .step-number {
            background: #667eea;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .step-content {
            flex: 1;
        }
        .step-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .step-desc {
            color: #666;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            margin: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }
        .btn.secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            box-shadow: none;
        }
        .btn.secondary:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        .quick-fix {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            text-align: left;
        }
        .quick-fix strong {
            color: #856404;
            display: block;
            margin-bottom: 8px;
        }
        .quick-fix code {
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 13px;
        }
        .divider {
            margin: 30px 0;
            text-align: center;
            position: relative;
        }
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            height: 1px;
            background: #ddd;
        }
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🛠️</div>
        <h1>Welcome to JINKA Plotter</h1>
        <p class="subtitle">Let's set up your database to get started</p>
        
        <div class="setup-steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <div class="step-title">Check MySQL Server</div>
                    <div class="step-desc">Make sure WAMP is running (green icon in system tray)</div>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <div class="step-title">Create Database</div>
                    <div class="step-desc">Use our automatic setup or manual SQL import</div>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <div class="step-title">Verify Connection</div>
                    <div class="step-desc">Check that everything is working correctly</div>
                </div>
            </div>
        </div>
        
        <div>
            <a href="setup-db.php" class="btn">🚀 Automatic Setup (Recommended)</a>
            <a href="check-db.php" class="btn secondary">🔍 Check Connection</a>
        </div>
        
        <div class="divider"><span>OR</span></div>
        
        <div class="quick-fix">
            <strong>⚡ Quick Manual Setup:</strong>
            1. Open <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a><br>
            2. Click "SQL" tab and run:<br>
            <code>CREATE DATABASE jinka_plotter;</code><br>
            3. Select database and import <code>database/schema.sql</code><br>
            4. <a href="check-db.php">Check connection</a> again
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <p style="color: #999; font-size: 14px;">
                Need help? Check the <a href="SETUP-WAMP.md" style="color: #667eea;">Setup Guide</a>
            </p>
        </div>
    </div>
</body>
</html>
