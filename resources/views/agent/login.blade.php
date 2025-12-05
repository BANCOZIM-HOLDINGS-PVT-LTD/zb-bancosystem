<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Portal Login - Microbiz Zimbabwe</title>
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
            background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 50%, #1b263b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo p {
            color: #64748b;
            font-size: 14px;
            margin-top: 8px;
        }

        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 16px 20px;
            padding-left: 50px;
            font-size: 16px;
            font-weight: 500;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            outline: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .input-wrapper input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .input-wrapper .icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 20px;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .help-text {
            text-align: center;
            margin-top: 24px;
            color: #64748b;
            font-size: 13px;
        }

        .help-text a {
            color: #10b981;
            text-decoration: none;
            font-weight: 500;
        }

        .features {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .features h3 {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
            text-align: center;
        }

        .feature-list {
            display: grid;
            gap: 12px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: #64748b;
        }

        .feature-item span {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <h1>Microbiz Zimbabwe</h1>
                <p>Agent Portal</p>
            </div>
            
            <div style="text-align: center;">
                <div class="portal-badge">
                    🏪 Agent Access
                </div>
            </div>

            @if ($errors->any())
                <div class="error-message">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('success'))
                <div class="success-message">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('agent.login.submit') }}">
                @csrf
                
                <div class="form-group">
                    <label for="agent_code">Agent Code</label>
                    <div class="input-wrapper">
                        <span class="icon">🔑</span>
                        <input 
                            type="text" 
                            id="agent_code" 
                            name="agent_code" 
                            placeholder="AG123456"
                            value="{{ old('agent_code') }}"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    Access Portal →
                </button>
            </form>

            <p class="help-text">
                Don't have an agent code? <a href="/">Apply to become an agent</a>
            </p>

            <div class="features">
                <h3>Agent Portal Features</h3>
                <div class="feature-list">
                    <div class="feature-item">
                        <span>📊</span>
                        View your referral statistics
                    </div>
                    <div class="feature-item">
                        <span>🔗</span>
                        Get your unique referral link
                    </div>
                    <div class="feature-item">
                        <span>💰</span>
                        Track your commissions
                    </div>
                    <div class="feature-item">
                        <span>📱</span>
                        Share with potential customers
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
