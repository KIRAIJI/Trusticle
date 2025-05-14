<?php
// Include the header file
include '../includes/header.php';
?>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <h1>About Us - Trusticle</h1>
    </div>

    <div class="content-container">
        <div class="about-section-main">
            <h2 class="about-main-title">WHO WE ARE</h2>
            
            <div class="about-main-description">
                <p>
                    Trusticle is a platform developed to help users and administrators identify and manage fake news articles.
                    Our system combines user submissions, admin moderation, and keyword-based detection to promote
                    accurate and trustworthy information online.
                </p>
            </div>
        </div>

        <div class="about-boxes-container">
            <div class="about-box">
                <h3>Our Mission</h3>
                <p>
                    To combat the spread of misinformation by providing an accessible, secure, and efficient tool
                    for detecting fake news articles.
                </p>
            </div>
            
            <div class="about-box">
                <h3>What We Do</h3>
                <p>
                    With Trusticle, users can easily submit and manage their articles, while admins handle reviews and approvals.
                    The system auto-flags fake news using keyword checks and shows simple trends and reports to keep
                    everything transparent and organized.
                </p>
            </div>
            
            <div class="about-box">
                <h3>Why Trusticle?</h3>
                <p>
                    We believe in the importance of credible information. In the age of digital media, it's vital to have systems
                    that filter out fake content while allowing users and admins to work together toward truth and clarity.
                </p>
            </div>
        </div>

        <div class="about-footer-container">
            <div class="about-logo">
                <i class="fas fa-feather-alt fa-2x"></i>
                <div class="logo-text">Trusticle</div>
            </div>
            
            <div class="about-tagline">
                <h3>Empowering Truth in News</h3>
                <p>Helping users and admins detect fake news through smart analysis, clear reporting, and responsible content management.</p>
            </div>
            
            <div class="about-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
        </div>
        
        <div class="about-copyright">
            <p>&copy; <?php echo date('Y'); ?> Trusticle. All rights reserved.</p>
        </div>
    </div>
</div>

<style>
    .content-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .about-section-main {
        text-align: center;
        margin-bottom: 50px;
    }
    
    .about-main-title {
        color: #006a71;
        font-size: 32px;
        font-weight: 600;
        margin-bottom: 25px;
    }
    
    .about-main-description {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.6;
        color: #555;
        font-size: 16px;
    }
    
    .about-boxes-container {
        display: flex;
        justify-content: space-between;
        gap: 30px;
        margin-bottom: 60px;
    }
    
    .about-box {
        flex: 1;
        background-color: #fff;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .about-box h3 {
        color: #006a71;
        margin-bottom: 15px;
        font-size: 20px;
    }
    
    .about-box p {
        line-height: 1.5;
        color: #555;
        font-size: 15px;
    }
    
    .about-footer-container {
        background-color: #f5f5f5;
        padding: 30px;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .about-logo {
        display: flex;
        flex-direction: column;
        align-items: center;
        color: #006a71;
    }
    
    .about-tagline {
        text-align: right;
        max-width: 60%;
    }
    
    .about-tagline h3 {
        color: #333;
        margin-bottom: 10px;
        font-size: 20px;
    }
    
    .about-tagline p {
        color: #666;
        line-height: 1.4;
    }
    
    .about-social {
        display: flex;
        gap: 15px;
    }
    
    .about-social a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background-color: #006a71;
        color: white;
        transition: background-color 0.3s ease;
    }
    
    .about-social a:hover {
        background-color: #004a50;
    }
    
    .about-copyright {
        text-align: center;
        color: #999;
        font-size: 14px;
        padding: 15px 0;
    }
    
    @media (max-width: 768px) {
        .about-boxes-container {
            flex-direction: column;
        }
        
        .about-footer-container {
            flex-direction: column;
            text-align: center;
            gap: 20px;
        }
        
        .about-tagline {
            text-align: center;
            max-width: 100%;
        }
    }
</style>

<?php
// Include the footer file
include '../includes/footer.php';
?> 