<?php 
include_once '../includes/header.php';
require_once "../../config/connection.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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
    $total_articles = (int)($row['total'] ?? 0);
    $pending_articles = (int)($row['pending'] ?? 0);
    $legit_articles = (int)($row['legit'] ?? 0);
    $fake_articles = (int)($row['fake'] ?? 0);
}
$stmt->close();

// Get top keywords from fake_keywords table with occurrence counts
$topKeywords = [];

// First, get all keywords from the fake_keywords table
$keywordsQuery = $conn->query("SELECT id, keyword FROM fake_keywords ORDER BY id");
$keywords = [];
while ($keywordRow = $keywordsQuery->fetch_assoc()) {
    $keywords[$keywordRow['id']] = $keywordRow['keyword'];
}

// If we have keywords, let's count their occurrences in fake articles
if (!empty($keywords)) {
    // Get content of fake articles
    $fakeArticlesQuery = $conn->prepare("SELECT content FROM articles WHERE user_id = ? AND status = 'fake' AND is_visible = 1");
    $fakeArticlesQuery->bind_param("i", $user_id);
    $fakeArticlesQuery->execute();
    $fakeArticlesResult = $fakeArticlesQuery->get_result();

    // Initialize count for each keyword
    $keywordCounts = array_fill_keys($keywords, 0);
    
    // Check each article for keyword occurrences
    while ($articleRow = $fakeArticlesResult->fetch_assoc()) {
        $content = $articleRow['content'];
        $content = strtolower($content); // Convert to lowercase for case-insensitive matching
        
        foreach ($keywords as $id => $keyword) {
            // Count occurrences of the keyword phrase in the content
            $keywordLower = strtolower($keyword);
            $count = substr_count($content, $keywordLower);
            if ($count > 0) {
                $keywordCounts[$keyword] += $count;
            }
        }
    }
    $fakeArticlesQuery->close();
    
    // Sort by count (highest first) and take top 8
    arsort($keywordCounts);
    $topKeywords = array_slice($keywordCounts, 0, 8, true);
}

// If we have less than 8 keywords with counts, add the remaining ones with 0 counts
if (count($topKeywords) < 8) {
    $remainingKeywords = array_diff($keywords, array_keys($topKeywords));
    $remainingKeywords = array_slice($remainingKeywords, 0, 8 - count($topKeywords), true);
    foreach ($remainingKeywords as $keyword) {
        $topKeywords[$keyword] = 0;
    }
}

// If still less than 8 (meaning we don't have enough keywords), add placeholders
if (count($topKeywords) < 8) {
    $placeholders = ['No keywords found', 'Add keywords', 'In database', 'To see', 'Trend analysis', 'For fake', 'News articles', 'Detection'];
    $i = count($topKeywords);
    while ($i < 8) {
        $topKeywords[$placeholders[$i]] = 0;
        $i++;
    }
}

// Debug output (will be hidden in the HTML source)
// echo "<!-- Debug: " . json_encode(['total' => $total_articles, 'pending' => $pending_articles, 'legit' => $legit_articles, 'fake' => $fake_articles]) . " -->";
?>
<link rel="stylesheet" href="../../assets/css/analytics.css">
<div class="dashboard-container">
    <!-- Main Content Area -->
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Analytics</h1>
        </div>
        
        <div class="analytics-content">
            <div class="welcome-message">
                <h1>Analytics Overview</h1>
                <p>Monitor article distribution and keyword trends.</p>
            </div>
            
            <!-- Stats Grid - 4 Cards using dashboard styling -->
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
            
            <!-- Charts Grid - Donut and Line Charts -->
            <div class="chart-grid">
                <!-- Donut Chart - Article Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Article Distribution</div>
                    </div>
                    <div class="donut-container">
                        <canvas id="donutChart"></canvas>
                    </div>
                </div>
                
                <!-- Line Chart - Top Fake Keywords -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Top Fake Keywords</div>
                    </div>
                    <div class="line-chart-container">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Dashboard layout to ensure content isn't hidden by sidebar */
    .dashboard-container {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex: 1;
        margin-left: 180px;
        padding: 20px;
        width: calc(100% - 180px);
        transition: margin-left 0.3s ease, width 0.3s ease;
        background: #fffaf5;
    }

    #sidebar.collapsed + .dashboard-container .main-content {
        margin-left: 60px;
        width: calc(100% - 60px);
    }

    /* Card grid layout */
    .stat-cards-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 35px;
        padding: 0 10px;
    }

    /* Chart grid layout */
    .chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
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
    
    /* Chart card styling */
    .chart-card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        padding: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }
    
    .chart-header {
        margin-bottom: 15px;
    }
    
    .chart-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
            width: 100%;
            padding: 15px;
        }
        
        .stat-cards-container {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .chart-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Make article count data available to JavaScript as a global variable
    window.articleData = {
        pending: <?php echo intval($pending_articles); ?>,
        legit: <?php echo intval($legit_articles); ?>,
        fake: <?php echo intval($fake_articles); ?>,
        total: <?php echo intval($total_articles); ?>
    };
    
    // Make keyword data available to JavaScript
    window.keywordData = {
        labels: <?php echo json_encode(array_keys($topKeywords)); ?>,
        values: <?php echo json_encode(array_values($topKeywords)); ?>
    };
    
    console.log("Article data:", window.articleData); // Debug data
    console.log("Keyword data:", window.keywordData); // Debug keyword data
</script>
<script src="../../assets/js/analytics.js"></script>
<script>
    // Improved dashboard card navigation - same as in dashboard.php
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
                
                // Create the target URL
                const targetUrl = 'my_articles.php?filter=' + filter;
                
                // Use direct window location navigation
                window.location.href = targetUrl;
                
                // Prevent default action if somehow an <a> tag is still involved
                e.preventDefault();
            });
        });
        
        // Check sidebar state and update main content accordingly
        const sidebar = document.getElementById('sidebar');
        if (sidebar.classList.contains('collapsed')) {
            document.querySelector('.main-content').classList.add('expanded');
        }
        
        // Listen for sidebar toggle
        document.addEventListener('sidebarToggled', function(e) {
            document.querySelector('.main-content').classList.toggle('expanded');
        });
    });
</script>
<?php include_once '../includes/footer.php'; ?>