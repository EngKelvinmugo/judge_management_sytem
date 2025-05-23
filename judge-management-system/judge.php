<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Ensure user is logged in as judge
requireJudge();

$success_message = '';
$error_message = '';

// Process form submission to add/update score
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $points = $_POST['points'];
    
    if (empty($user_id) || empty($points)) {
        $error_message = "Please select a user and enter points.";
    } elseif ($points < 1 || $points > 100) {
        $error_message = "Points must be between 1 and 100.";
    } else {
        try {
            // Check if score already exists for this judge and user
            $stmt = $pdo->prepare("SELECT id FROM scores WHERE judge_id = ? AND user_id = ?");
            $stmt->execute([$_SESSION['user_id'], $user_id]);
            $existing_score = $stmt->fetch();
            
            if ($existing_score) {
                // Update existing score
                $stmt = $pdo->prepare("UPDATE scores SET points = ? WHERE judge_id = ? AND user_id = ?");
                $stmt->execute([$points, $_SESSION['user_id'], $user_id]);
                $success_message = "Score updated successfully!";
            } else {
                // Insert new score
                $stmt = $pdo->prepare("INSERT INTO scores (judge_id, user_id, points) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $user_id, $points]);
                $success_message = "Score added successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all users
try {
    $stmt = $pdo->query("SELECT id, name FROM users ORDER BY name");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching users: " . $e->getMessage();
    $users = [];
}

// Fetch current scores given by this judge
try {
    $stmt = $pdo->prepare("
        SELECT s.user_id, s.points, u.name 
        FROM scores s
        JOIN users u ON s.user_id = u.id
        WHERE s.judge_id = ?
        ORDER BY u.name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $current_scores = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching scores: " . $e->getMessage();
    $current_scores = [];
}

// Calculate dashboard statistics
$total_users = count($users);
$scored_users = count($current_scores);
$remaining_users = $total_users - $scored_users;
$completion_percentage = $total_users > 0 ? round(($scored_users / $total_users) * 100) : 0;

// Calculate average score
$total_points = 0;
foreach ($current_scores as $score) {
    $total_points += $score['points'];
}
$average_score = $scored_users > 0 ? round($total_points / $scored_users) : 0;

// Get highest and lowest scores
$highest_score = 0;
$lowest_score = 100;
$highest_user = '';
$lowest_user = '';

foreach ($current_scores as $score) {
    if ($score['points'] > $highest_score) {
        $highest_score = $score['points'];
        $highest_user = $score['name'];
    }
    if ($score['points'] < $lowest_score) {
        $lowest_score = $score['points'];
        $lowest_user = $score['name'];
    }
}

// If no scores yet, reset lowest
if ($scored_users === 0) {
    $lowest_score = 0;
}

// Get recently scored users
$stmt = $pdo->prepare("
    SELECT s.points, u.name, s.id
    FROM scores s
    JOIN users u ON s.user_id = u.id
    WHERE s.judge_id = ?
    ORDER BY s.id DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_scores = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<style>
/* Dashboard styles */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.dashboard-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.judge-welcome {
    font-size: 1.1rem;
    color: #64748b;
    margin-top: 0.5rem;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.stat-card.progress::before {
    background: linear-gradient(90deg, #3b82f6, #2dd4bf);
}

.stat-card.average::before {
    background: linear-gradient(90deg, #f59e0b, #f97316);
}

.stat-card.highest::before {
    background: linear-gradient(90deg, #10b981, #34d399);
}

.stat-card.lowest::before {
    background: linear-gradient(90deg, #ef4444, #f87171);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #1e293b;
}

.stat-label {
    font-size: 1rem;
    color: #64748b;
    font-weight: 500;
}

.stat-icon {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 1.5rem;
    opacity: 0.2;
    color: #0f172a;
}

.stat-footer {
    margin-top: 1rem;
    font-size: 0.875rem;
    color: #64748b;
}

.progress-bar-container {
    height: 8px;
    background-color: #e2e8f0;
    border-radius: 4px;
    margin-top: 1rem;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #2dd4bf);
    border-radius: 4px;
    width: 0;
    transition: width 1s ease-in-out;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.btn-primary {
    background: linear-gradient(90deg, #3b82f6, #2dd4bf);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 25px rgba(0, 0, 0, 0.2);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    background: linear-gradient(90deg, #3b82f6, #2dd4bf);
    color: white;
    padding: 1.5rem;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.close {
    color: white;
    font-size: 1.5rem;
    font-weight: bold;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.close:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.modal-body {
    padding: 2rem;
}

.scores-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.table-header {
    background: linear-gradient(90deg, #3b82f6, #2dd4bf);
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.score-bar-container {
    width: 100%;
    height: 8px;
    background-color: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.score-bar {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #2dd4bf);
    border-radius: 4px;
}

.recent-activity {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-text {
    font-size: 0.9rem;
    color: #64748b;
}

.activity-score {
    background: linear-gradient(90deg, #3b82f6, #2dd4bf);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
}

.score-pill {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.875rem;
    color: white;
}

.score-high {
    background: linear-gradient(90deg, #10b981, #34d399);
}

.score-medium {
    background: linear-gradient(90deg, #f59e0b, #f97316);
}

.score-low {
    background: linear-gradient(90deg, #ef4444, #f87171);
}

.range-slider {
    -webkit-appearance: none;
    width: 100%;
    height: 10px;
    border-radius: 5px;
    background: #e2e8f0;
    outline: none;
    margin: 1rem 0;
}

.range-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 25px;
    height: 25px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.range-slider::-moz-range-thumb {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    border: none;
}

.range-value {
    text-align: center;
    font-size: 2rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1rem;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #64748b;
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .modal-content {
        margin: 10% auto;
        width: 95%;
    }
    
    .modal-body {
        padding: 1rem;
    }
}
</style>

<!-- Alert Messages -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<div class="dashboard-header">
    <div>
        <h1 class="dashboard-title">Judge Portal</h1>
        <p class="judge-welcome">Welcome, <?php echo htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']); ?>!</p>
    </div>
    <button class="btn-primary" onclick="openModal()">
        <span>➕</span> Assign Score
    </button>
</div>

<!-- Dashboard Statistics -->
<div class="dashboard-stats">
    <div class="stat-card progress">
        <div class="stat-icon">📊</div>
        <div class="stat-number"><?php echo $completion_percentage; ?>%</div>
        <div class="stat-label">Completion</div>
        <div class="progress-bar-container">
            <div class="progress-bar" id="progressBar"></div>
        </div>
        <div class="stat-footer">
            <?php echo $scored_users; ?> of <?php echo $total_users; ?> users scored
        </div>
    </div>
    
    <div class="stat-card average">
        <div class="stat-icon">⭐</div>
        <div class="stat-number"><?php echo $average_score; ?></div>
        <div class="stat-label">Average Score</div>
        <div class="stat-footer">
            Based on <?php echo $scored_users; ?> evaluations
        </div>
    </div>
    
    <?php if ($scored_users > 0): ?>
    <div class="stat-card highest">
        <div class="stat-icon">🏆</div>
        <div class="stat-number"><?php echo $highest_score; ?></div>
        <div class="stat-label">Highest Score</div>
        <div class="stat-footer">
            Given to <?php echo htmlspecialchars($highest_user); ?>
        </div>
    </div>
    
    <div class="stat-card lowest">
        <div class="stat-icon">📉</div>
        <div class="stat-number"><?php echo $lowest_score; ?></div>
        <div class="stat-label">Lowest Score</div>
        <div class="stat-footer">
            Given to <?php echo htmlspecialchars($lowest_user); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Dashboard Grid -->
<div class="dashboard-grid">
    <!-- Scores Table -->
    <div class="scores-table">
        <div class="table-header">
            <h3 class="table-title">Your Scores</h3>
            <span><?php echo count($current_scores); ?> of <?php echo $total_users; ?> users</span>
        </div>
        
        <?php if (count($current_scores) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Participant</th>
                            <th>Score</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_scores as $score): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($score['name']); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div class="score-bar-container">
                                            <div class="score-bar" style="width: <?php echo $score['points']; ?>%;"></div>
                                        </div>
                                        <span style="font-weight: 600;"><?php echo htmlspecialchars($score['points']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $scoreClass = '';
                                    $scoreText = '';
                                    
                                    if ($score['points'] >= 80) {
                                        $scoreClass = 'score-high';
                                        $scoreText = 'Excellent';
                                    } elseif ($score['points'] >= 60) {
                                        $scoreClass = 'score-medium';
                                        $scoreText = 'Good';
                                    } else {
                                        $scoreClass = 'score-low';
                                        $scoreText = 'Needs Improvement';
                                    }
                                    ?>
                                    <span class="score-pill <?php echo $scoreClass; ?>"><?php echo $scoreText; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📝</div>
                <p>You haven't assigned any scores yet.</p>
                <p>Click the "Assign Score" button to get started!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h3 style="margin-bottom: 1rem; color: #1e293b;">Recent Activity</h3>
        <?php if (count($recent_scores) > 0): ?>
            <?php foreach ($recent_scores as $activity): ?>
                <div class="activity-item">
                    <div class="activity-text">
                        You scored <strong><?php echo htmlspecialchars($activity['name']); ?></strong>
                    </div>
                    <div class="activity-score"><?php echo htmlspecialchars($activity['points']); ?> pts</div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #64748b; text-align: center; padding: 1rem;">No recent activity</p>
        <?php endif; ?>
        
        <div style="margin-top: 2rem;">
            <h3 style="margin-bottom: 1rem; color: #1e293b;">Remaining Users</h3>
            <?php 
            $remaining_count = 0;
            foreach ($users as $user) {
                $found = false;
                foreach ($current_scores as $score) {
                    if ($score['user_id'] == $user['id']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    echo '<div class="activity-item">';
                    echo '<div class="activity-text">' . htmlspecialchars($user['name']) . '</div>';
                    echo '<button class="btn-sm" onclick="assignToUser(' . $user['id'] . ', \'' . htmlspecialchars($user['name']) . '\')">Score</button>';
                    echo '</div>';
                    $remaining_count++;
                    if ($remaining_count >= 5) {
                        if (count($users) - count($current_scores) > 5) {
                            echo '<div style="text-align: center; margin-top: 1rem;">';
                            echo '<em style="color: #64748b; font-size: 0.875rem;">' . (count($users) - count($current_scores) - 5) . ' more users remaining</em>';
                            echo '</div>';
                        }
                        break;
                    }
                }
            }
            if ($remaining_count === 0) {
                echo '<p style="color: #64748b; text-align: center; padding: 1rem;">All users have been scored!</p>';
            }
            ?>
        </div>
    </div>
</div>

<!-- Assign Score Modal -->
<div id="assignScoreModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Assign Score</h3>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="assignScoreForm">
                <div class="form-group">
                    <label for="user_id">Select Participant:</label>
                    <select id="user_id" name="user_id" required onchange="checkExistingScore(this.value)">
                        <option value="">-- Select Participant --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['name']); ?>">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="scoreInputContainer" style="margin-top: 2rem;">
                    <label for="points">Score (1-100):</label>
                    <div class="range-value" id="rangeValue">50</div>
                    <input type="range" id="points" name="points" min="1" max="100" value="50" class="range-slider" oninput="updateRangeValue(this.value)">
                    <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                        <span style="color: #ef4444; font-size: 0.875rem;">Poor</span>
                        <span style="color: #f59e0b; font-size: 0.875rem;">Average</span>
                        <span style="color: #10b981; font-size: 0.875rem;">Excellent</span>
                    </div>
                </div>
                
                <div id="existingScoreAlert" style="display: none; margin-top: 1rem; padding: 1rem; background-color: #fffbeb; border: 1px solid #fef3c7; color: #92400e; border-radius: 8px;">
                    You've already scored this participant. Your current score: <strong id="currentScoreValue">0</strong>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeModal()" style="background: #6b7280; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary" id="submitButton">
                        Submit Score
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Set progress bar width based on completion percentage
document.addEventListener('DOMContentLoaded', function() {
    const progressBar = document.getElementById('progressBar');
    if (progressBar) {
        setTimeout(function() {
            progressBar.style.width = '<?php echo $completion_percentage; ?>%';
        }, 300);
    }
    
    // Auto-close alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Animate stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(function(card, index) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(function() {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Modal functionality
function openModal() {
    document.getElementById('assignScoreModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('assignScoreModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    // Reset form
    document.getElementById('assignScoreForm').reset();
    document.getElementById('existingScoreAlert').style.display = 'none';
    document.getElementById('rangeValue').textContent = '50';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('assignScoreModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// Update range value display
function updateRangeValue(value) {
    document.getElementById('rangeValue').textContent = value;
    
    // Change color based on value
    const rangeValue = document.getElementById('rangeValue');
    if (value >= 80) {
        rangeValue.style.color = '#10b981';
    } else if (value >= 60) {
        rangeValue.style.color = '#f59e0b';
    } else {
        rangeValue.style.color = '#ef4444';
    }
}

// Check if user already has a score
function checkExistingScore(userId) {
    if (!userId) return;
    
    // Create an array of current scores for easy lookup
    const currentScores = <?php echo json_encode($current_scores); ?>;
    const existingScoreAlert = document.getElementById('existingScoreAlert');
    const currentScoreValue = document.getElementById('currentScoreValue');
    const pointsInput = document.getElementById('points');
    
    let existingScore = null;
    for (let i = 0; i < currentScores.length; i++) {
        if (currentScores[i].user_id == userId) {
            existingScore = currentScores[i];
            break;
        }
    }
    
    if (existingScore) {
        existingScoreAlert.style.display = 'block';
        currentScoreValue.textContent = existingScore.points;
        pointsInput.value = existingScore.points;
        updateRangeValue(existingScore.points);
        document.getElementById('submitButton').textContent = 'Update Score';
    } else {
        existingScoreAlert.style.display = 'none';
        pointsInput.value = 50;
        updateRangeValue(50);
        document.getElementById('submitButton').textContent = 'Submit Score';
    }
}

// Function to pre-select a user in the modal
function assignToUser(userId, userName) {
    openModal();
    const userSelect = document.getElementById('user_id');
    userSelect.value = userId;
    checkExistingScore(userId);
}

// Add loading state to form submission
document.getElementById('assignScoreForm').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<span>⏳</span> Submitting...';
    submitBtn.disabled = true;
});
</script>

<?php include 'includes/footer.php'; ?>