<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - Microbiz Zimbabwe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #f8fafc;
        }

        .header {
            background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header-logo h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
        }

        .header-logo span {
            color: #10b981;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .agent-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
        }

        .logout-btn {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .welcome-section {
            margin-bottom: 32px;
        }

        .welcome-section h2 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .welcome-section p {
            color: #64748b;
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .stat-icon.green { background: #d1fae5; }
        .stat-icon.blue { background: #dbeafe; }
        .stat-icon.yellow { background: #fef3c7; }
        .stat-icon.purple { background: #ede9fe; }

        .stat-card h3 {
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
        }

        .referral-section {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 32px;
        }

        .referral-section h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 24px;
        }

        .referral-link-box {
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .referral-link-box input {
            flex: 1;
            background: transparent;
            border: none;
            font-size: 14px;
            color: #334155;
            outline: none;
        }

        .copy-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            transform: scale(1.05);
        }

        .agent-code-display {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%);
            border-radius: 12px;
            color: white;
        }

        .agent-code-display .code {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 4px;
        }

        .agent-code-display .label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .info-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .info-card h4 {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item .label {
            color: #64748b;
            font-size: 14px;
        }

        .info-item .value {
            color: #1e293b;
            font-weight: 500;
            font-size: 14px;
        }

        .share-buttons {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }

        .share-btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .share-btn.whatsapp {
            background: #25D366;
            color: white;
        }

        .share-btn.facebook {
            background: #1877F2;
            color: white;
        }

        .share-btn:hover {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .header {
                padding: 16px 20px;
                flex-direction: column;
                gap: 16px;
            }
            
            .main-content {
                padding: 20px 16px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-logo">
            <h1>Microbiz <span>Zimbabwe</span></h1>
        </div>
        <div class="header-user">
            <div class="agent-badge">🏪 Agent Portal</div>
            <a href="{{ route('agent.logout') }}" class="logout-btn">Logout</a>
        </div>
    </header>

    <main class="main-content">
        <div class="welcome-section">
            <h2>Welcome back, {{ $agent->first_name }}! 👋</h2>
            <p>Here's an overview of your agent activity</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green">📊</div>
                <h3>Total Referrals</h3>
                <div class="stat-value">{{ $stats['total_referrals'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">✅</div>
                <h3>Successful Referrals</h3>
                <div class="stat-value">{{ $stats['successful_referrals'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <h3>Pending Commission</h3>
                <div class="stat-value">${{ number_format($stats['pending_commission'], 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">💰</div>
                <h3>Total Earned</h3>
                <div class="stat-value">${{ number_format($stats['total_earned'], 2) }}</div>
            </div>
        </div>

        <!-- Referral Section -->
        <div class="referral-section">
            <h3>🔗 Your Referral Link</h3>
            
            <div class="agent-code-display">
                <div>
                    <div class="label">YOUR AGENT CODE</div>
                    <div class="code">{{ $agent->agent_code }}</div>
                </div>
            </div>
            
            <div class="referral-link-box" style="margin-top: 20px;">
                <input type="text" id="referralLink" value="{{ $agent->referral_link }}" readonly>
                <button class="copy-btn" onclick="copyLink()">📋 Copy Link</button>
            </div>

            <div class="share-buttons">
                <button class="share-btn whatsapp" onclick="shareWhatsApp()">
                    💬 Share on WhatsApp
                </button>
                <button class="share-btn facebook" onclick="shareFacebook()">
                    📘 Share on Facebook
                </button>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-card">
                <h4>👤 Your Profile</h4>
                <div class="info-item">
                    <span class="label">Full Name</span>
                    <span class="value">{{ $agent->first_name }} {{ $agent->surname }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Province</span>
                    <span class="value">{{ $agent->province }}</span>
                </div>
                <div class="info-item">
                    <span class="label">WhatsApp</span>
                    <span class="value">{{ $agent->whatsapp_contact }}</span>
                </div>
                <div class="info-item">
                    <span class="label">EcoCash</span>
                    <span class="value">{{ $agent->ecocash_number }}</span>
                </div>
            </div>
            
            <div class="info-card">
                <h4>📅 Account Info</h4>
                <div class="info-item">
                    <span class="label">Status</span>
                    <span class="value" style="color: #10b981; font-weight: 600;">✓ Active</span>
                </div>
                <div class="info-item">
                    <span class="label">Member Since</span>
                    <span class="value">{{ $agent->created_at->format('M d, Y') }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Approved On</span>
                    <span class="value">{{ $agent->updated_at->format('M d, Y') }}</span>
                </div>
            </div>
        </div>
    </main>

    <script>
        function copyLink() {
            const input = document.getElementById('referralLink');
            input.select();
            document.execCommand('copy');
            
            const btn = document.querySelector('.copy-btn');
            const originalText = btn.textContent;
            btn.textContent = '✅ Copied!';
            setTimeout(() => {
                btn.textContent = originalText;
            }, 2000);
        }

        function shareWhatsApp() {
            const link = document.getElementById('referralLink').value;
            const text = `🌟 Start your own business with Microbiz Zimbabwe! Get gadgets, furniture, solar systems on credit. Apply now: ${link}`;
            window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
        }

        function shareFacebook() {
            const link = document.getElementById('referralLink').value;
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(link)}`, '_blank');
        }
    </script>
</body>
</html>
