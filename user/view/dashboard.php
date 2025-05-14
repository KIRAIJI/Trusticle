<?php
session_start();
require_once "../../utils/user.php";
require_once "../../config/connection.php";

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page if not logged in
    header("Location: ../../auth/login.php");
    exit();
}

// Get user info
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Get user first name
$stmt = $conn->prepare("SELECT first_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$first_name = $username; // Default to username if first_name not found
if ($row = $result->fetch_assoc()) {
    $first_name = $row['first_name'];
}
$stmt->close();

// Get user article statistics
$total_articles = 0;
$pending_articles = 0;
$legit_articles = 0;
$fake_articles = 0;

// Get article counts
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'legit' THEN 1 ELSE 0 END) as legit,
    SUM(CASE WHEN status = 'fake' THEN 1 ELSE 0 END) as fake
FROM articles 
WHERE user_id = ? AND is_visible = 1");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $total_articles = $row['total'] ?? 0;
    $pending_articles = $row['pending'] ?? 0;
    $legit_articles = $row['legit'] ?? 0;
    $fake_articles = $row['fake'] ?? 0;
}
$stmt->close();

// Get recent articles
$stmt = $conn->prepare("SELECT 
    a.id, 
    a.title, 
    u.username as author, 
    c.name as category, 
    a.created_at as date_published, 
    a.status
FROM articles a
LEFT JOIN users u ON a.user_id = u.id
LEFT JOIN categories c ON a.category_id = c.id
WHERE a.user_id = ? AND a.is_visible = 1
ORDER BY a.created_at DESC
LIMIT 5");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_articles = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Trusticle</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Dashboard container and spacing */
        .dashboard-content {
            padding: 20px 0;
        }
        
        .welcome-message {
            margin-bottom: 30px;
            padding: 0 10px;
        }
        
        .welcome-message h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .welcome-message p {
            color: #666;
            font-size: 16px;
        }

        /* Stats grid with improved spacing */
        .stat-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
            padding: 0 10px;
        }

        /* Card styling */
        .stat-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            padding: 20px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            user-select: none;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.1);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .stat-card:hover:after {
            opacity: 1;
        }

        .stat-icon {
            font-size: 2rem;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 12px;
        }

        .stat-info {
            flex: 1;
        }

        .stat-title {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
        }

        /* Card Colors */
        .stat-card.blue {
            border-left: 4px solid #2196f3;
        }
        .stat-card.blue .stat-icon {
            color: #2196f3;
            background-color: rgba(33, 150, 243, 0.1);
        }

        .stat-card.yellow {
            border-left: 4px solid #ffc107;
        }
        .stat-card.yellow .stat-icon {
            color: #ffc107;
            background-color: rgba(255, 193, 7, 0.1);
        }

        .stat-card.green {
            border-left: 4px solid #4caf50;
        }
        .stat-card.green .stat-icon {
            color: #4caf50;
            background-color: rgba(76, 175, 80, 0.1);
        }

        .stat-card.red {
            border-left: 4px solid #f44336;
        }
        .stat-card.red .stat-icon {
            color: #f44336;
            background-color: rgba(244, 67, 54, 0.1);
        }

        /* Recent articles card with improved styling */
        .articles-card {
            background-color: #e4dac880;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
            margin: 0 10px 25px 10px;
            overflow: hidden;
        }

        .articles-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .articles-card-title {
            font-weight: 600;
            font-size: 18px;
            color: #006a71;
        }

        .articles-table {
            width: 100%;
            border-collapse: collapse;
        }

        .articles-table th {
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            color: #49463b;
            background-color: #D2CABA50;
            position: relative;
            cursor: pointer;
        }

        .articles-table th::after {
            content: "▼";
            font-size: 10px;
            margin-left: 5px;
            opacity: 0.5;
        }

        .articles-table td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            color: #333;
            font-size: 14px;
        }

        .articles-table tr:last-child td {
            border-bottom: none;
        }

        .articles-table tr:hover td {
            background-color: rgba(138, 126, 72, 0.1);
        }

        /* Status badges with improved styling */
        .result-real, .result-fake, .result-pending {
            display: inline-block;
            padding: 5px 12px;
            color: #fff;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            min-width: 80px;
        }

        .result-real {
            background-color: #4caf50;
        }

        .result-fake {
            background-color: #f44336;
        }

        .result-pending {
            background-color: #ffc107;
            color: #333;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stat-cards-container {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .articles-card {
                padding: 15px;
            }
            
            .articles-table th, 
            .articles-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/header.php';?>
        <!-- Main Content Area -->
        <div class="main-content">

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="welcome-message">
                    <h1>Welcome, <?php echo htmlspecialchars($first_name); ?>!</h1>
                    <p>Here's what's happening with your articles today.</p>
                </div>

                <div class="stat-cards-container">
                    <!-- Total Articles Card -->
                    <div class="stat-card blue" data-filter="all">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Total Articles Submitted</div>
                            <div class="stat-value"><?php echo $total_articles; ?></div>
                        </div>
                    </div>

                    <!-- Pending Articles Card -->
                    <div class="stat-card yellow" data-filter="pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Pending Articles</div>
                            <div class="stat-value"><?php echo $pending_articles; ?></div>
                        </div>
                    </div>

                    <!-- Legit Articles Card -->
                    <div class="stat-card green" data-filter="legit">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Legit Articles</div>
                            <div class="stat-value"><?php echo $legit_articles; ?></div>
                        </div>
                    </div>

                    <!-- Fake Articles Card -->
                    <div class="stat-card red" data-filter="fake">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-title">Fake Articles</div>
                            <div class="stat-value"><?php echo $fake_articles; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Articles Table -->
                <div class="articles-card">
                    <div class="articles-card-header">
                        <div class="articles-card-title">My Recent Articles</div>
                    </div>
                    <table class="articles-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Date Published</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_articles->num_rows > 0): ?>
                                <?php while ($article = $recent_articles->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($article['title']); ?></td>
                                        <td><?php echo htmlspecialchars($article['category']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($article['date_published'])); ?></td>
                                        <td>
                                            <?php if ($article['status'] == 'pending'): ?>
                                                <span class="result-pending">Pending</span>
                                            <?php elseif ($article['status'] == 'legit'): ?>
                                                <span class="result-real">Real</span>
                                            <?php elseif ($article['status'] == 'fake'): ?>
                                                <span class="result-fake">Fake</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No articles found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
<script>
    // Improved dashboard card navigation
    document.addEventListener('DOMContentLoaded', function() {
        // Get all stat cards
        const cards = document.querySelectorAll('.stat-card');
        
        // Process all cards
        cards.forEach(card => {
            // Make sure cards are visibly clickable
            card.style.cursor = 'pointer';
            
            // Add click event with direct navigation
            card.addEventListener('click', function(e) {
                // Get the filter value directly from the data attribute
                const filter = this.getAttribute('data-filter');
                
                // Log navigation info for debugging
                console.log('NAVIGATION: Dashboard card clicked for filter: ' + filter);
                console.log('NAVIGATION: Redirecting to my_articles.php?filter=' + filter);
                
                // Create the target URL
                const targetUrl = 'my_articles.php?filter=' + filter;
                
                // Use direct window location navigation
                window.location.href = targetUrl;
                
                // Prevent default action if somehow an <a> tag is still involved
                e.preventDefault();
            });
        });
    });
</script>
</html>
      