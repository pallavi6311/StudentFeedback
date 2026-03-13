<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduFeedback Pro - Advanced Student Feedback System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --dark: #1e293b;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            overflow-x: hidden;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            color: white;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }
        
        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease;
        }
        
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            animation: fadeInUp 1s ease 0.2s both;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease 0.4s both;
        }
        
        .btn {
            padding: 1rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
        }
        
        .btn-primary:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid white;
        }
        
        .btn-secondary:hover {
            background: transparent;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        /* Features Section */
        .features {
            padding: 5rem 0;
            background: #f8fafc;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 4rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }
        
        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }
        
        .feature-card h3 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .feature-card p {
            color: #64748b;
            line-height: 1.7;
        }
        
        /* Stats Section */
        .stats {
            padding: 4rem 0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }
        
        .stat-item h3 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h4 {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .footer-section p, .footer-section a {
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid #374151;
            padding-top: 2rem;
            color: #94a3b8;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .hero p { font-size: 1.1rem; }
            .cta-buttons { flex-direction: column; align-items: center; }
            .section-title { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>EduFeedback Pro</h1>
                <p>Revolutionary AI-powered student feedback system with real-time analytics, sentiment analysis, and smart recommendations for educational excellence.</p>
                
                <div class="cta-buttons">
                    <a href="login_student.php" class="btn btn-primary">
                        <i class="fas fa-user-graduate"></i>
                        Student Login
                    </a>
                    <a href="login_admin.php" class="btn btn-secondary">
                        <i class="fas fa-shield-alt"></i>
                        Admin Login
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Advanced Features</h2>
            <p class="section-subtitle">Experience the next generation of educational feedback with cutting-edge AI technology</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>AI Sentiment Analysis</h3>
                    <p>Advanced natural language processing to analyze student feedback sentiment and provide actionable insights for course improvement.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Real-time Analytics</h3>
                    <p>Live dashboard with real-time feedback monitoring, trend analysis, and predictive insights for proactive decision making.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-fire"></i>
                    </div>
                    <h3>Interactive Heatmaps</h3>
                    <p>Visual representation of course performance across all evaluation criteria with interactive tooltips and detailed breakdowns.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Smart Recommendations</h3>
                    <p>AI-powered recommendation engine that provides personalized suggestions for course improvements and student engagement.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3>Gamification System</h3>
                    <p>Engaging student experience with levels, badges, streaks, and achievements to encourage active participation in feedback.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Responsive Design</h3>
                    <p>Modern, mobile-first design that works seamlessly across all devices with intuitive user interface and smooth animations.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>98%</h3>
                    <p>Student Satisfaction</p>
                </div>
                <div class="stat-item">
                    <h3>50+</h3>
                    <p>Universities Using</p>
                </div>
                <div class="stat-item">
                    <h3>10K+</h3>
                    <p>Active Students</p>
                </div>
                <div class="stat-item">
                    <h3>24/7</h3>
                    <p>System Availability</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>EduFeedback Pro</h4>
                    <p>Revolutionizing educational feedback with AI-powered insights and real-time analytics.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <a href="login_student.php">Student Portal</a>
                    <a href="login_admin.php">Admin Dashboard</a>
                    <a href="#features">Features</a>
                </div>
                
                <div class="footer-section">
                    <h4>Technology</h4>
                    <p>Built with PHP, MySQL, Chart.js, and modern web technologies for optimal performance.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>Email: support@edufeedbackpro.com</p>
                    <p>Phone: +1 (555) 123-4567</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 EduFeedback Pro. All rights reserved. | Designed for educational excellence.</p>
            </div>
        </div>
    </footer>
</body>
</html>