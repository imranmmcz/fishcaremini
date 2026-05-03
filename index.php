<?php
/**
 * Fish Care System - Home Page
 * Role Selection
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// If logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    redirect(getDashboardUrl($user['role']));
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME_BN; ?> - মাছ চাষের সম্পূর্ণ ব্যবস্থাপনা</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary-color: #00BCD4;
            --secondary-color: #4CAF50;
            --accent-color: #FF9800;
            --danger-color: #f44336;
            --dark-bg: #0f172a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Hind Siliguri', 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            color: white;
        }

        .hero-section {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(0, 188, 212, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(76, 175, 80, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-content {
            text-align: center;
            max-width: 900px;
            position: relative;
            z-index: 1;
        }

        .logo-large {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 60px;
            box-shadow: 0 20px 60px rgba(0, 188, 212, 0.4);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .hero-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 20px;
            color: #94a3b8;
            margin-bottom: 50px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 50px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary-color);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }

        .feature-icon.admin { background: linear-gradient(135deg, #00BCD4, #0097a7); }
        .feature-icon.farmer { background: linear-gradient(135deg, #4CAF50, #388E3C); }
        .feature-icon.seller { background: linear-gradient(135deg, #FF9800, #F57C00); }
        .feature-icon.customer { background: linear-gradient(135deg, #9C27B0, #7B1FA2); }

        .feature-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .feature-desc {
            font-size: 14px;
            color: #94a3b8;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: 14px;
            font-weight: 600;
            font-family: 'Hind Siliguri', sans-serif;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #00acc1);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 188, 212, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary-color);
        }

        .footer {
            position: absolute;
            bottom: 20px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 32px;
            }

            .hero-subtitle {
                font-size: 16px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="hero-content">
            <div class="logo-large">
                <i class="bi bi-water"></i>
            </div>

            <h1 class="hero-title"><?php echo SITE_NAME_BN; ?></h1>
            <p class="hero-subtitle">মাছ চাষ থেকে বিক্রয় পর্যন্ত সম্পূর্ণ ব্যবস্থাপনা</p>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon admin">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3 class="feature-title">অ্যাডমিন</h3>
                    <p class="feature-desc">সিস্টেম পরিচালনা ও নিয়ন্ত্রণ</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon farmer">
                        <i class="bi bi-person-farmer"></i>
                    </div>
                    <h3 class="feature-title">চাষী</h3>
                    <p class="feature-desc">পুকুর ও মাছ চাষ ব্যবস্থাপনা</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon seller">
                        <i class="bi bi-shop"></i>
                    </div>
                    <h3 class="feature-title">বিক্রেতা</h3>
                    <p class="feature-desc">পণ্য ও মাছ বিক্রয় ব্যবস্থাপনা</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon customer">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="feature-title">গ্রাহক</h3>
                    <p class="feature-desc">মাছ ক্রয় ও পরিশোধ</p>
                </div>
            </div>

            <div class="cta-buttons">
                <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right"></i> লগইন করুন
                </a>
                <a href="<?php echo SITE_URL; ?>/pages/auth/register.php" class="btn btn-secondary">
                    <i class="bi bi-person-plus"></i> রেজিস্টার করুন
                </a>
            </div>
        </div>

        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME_BN; ?>। সর্বস্বত্ব সংরক্ষিত।
        </div>
    </div>
</body>
</html>
