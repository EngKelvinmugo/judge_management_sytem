<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Ensure user is logged in as admin
requireAdmin();

$success_message = '';
$error_message = '';

// Process form submission to add a new judge
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $display_name = trim($_POST['display_name']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($display_name) || empty($password)) {
        $error_message = "Please fill in all fields.";
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM judges WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $error_message = "Username already exists. Please choose a different username.";
            } else {
                // Hash the password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new judge
                $stmt = $pdo->prepare("INSERT INTO judges (username, display_name, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$username, $display_name, $password_hash]);
                
                $success_message = "Judge added successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch dashboard statistics
try {
    // Count judges
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM judges");
    $judges_count = $stmt->fetch()['count'];
    
    // Count users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $users_count = $stmt->fetch()['count'];
    
    // Count total scores
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM scores");
    $scores_count = $stmt->fetch()['count'];
    
    // Get recent activity (latest scores)
    $stmt = $pdo->query("
        SELECT j.display_name as judge_name, u.name as user_name, s.points, s.id
        FROM scores s
        JOIN judges j ON s.judge_id = j.id
        JOIN users u ON s.user_id = u.id
        ORDER BY s.id DESC
        LIMIT 5
    ");
    $recent_scores = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $judges_count = 0;
    $users_count = 0;
    $scores_count = 0;
    $recent_scores = [];
}

// Fetch all judges
try {
    $stmt = $pdo->query("SELECT id, username, display_name FROM judges ORDER BY id");
    $judges = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching judges: " . $e->getMessage();
    $judges = [];
}
?>

<?php include 'includes/header.php'; ?>

<style>
/* Additional styles for the dashboard */
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

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
}

.stat-card.judges {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card.users {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.scores {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1rem;
    opacity: 0.9;
    font-weight: 500;
}

.stat-icon {
    float: right;
    font-size: 2rem;
    opacity: 0.7;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.recent-activity {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
}

.judges-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.table-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

<div class="dashboard-header">
    <h1 class="dashboard-title">Admin Dashboard</h1>
    <button class="btn-primary" onclick="openModal()">
        <span>➕</span> Add New Judge
    </button>
</div>

<!-- Dashboard Statistics -->
<div class="dashboard-stats">
    <div class="stat-card judges">
        <div class="stat-icon">👨‍⚖️</div>
        <div class="stat-number"><?php echo $judges_count; ?></div>
        <div class="stat-label">Total Judges</div>
    </div>
    <div class="stat-card users">
        <div class="stat-icon">👥</div>
        <div class="stat-number"><?php echo $users_count; ?></div>
        <div class="stat-label">Participants</div>
    </div>
    <div class="stat-card scores">
        <div class="stat-icon">📊</div>
        <div class="stat-number"><?php echo $scores_count; ?></div>
        <div class="stat-label">Total Scores</div>
    </div>
</div>

<!-- Alert Messages -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<!-- Dashboard Grid -->
<div class="dashboard-grid">
    <!-- Judges Table -->
    <div class="judges-table">
        <div class="table-header">
            <h3 class="table-title">Judges Management</h3>
            <span><?php echo count($judges); ?> judges</span>
        </div>
        
        <?php if (count($judges) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Display Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($judges as $judge): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($judge['id']); ?></td>
                                <td><?php echo htmlspecialchars($judge['username']); ?></td>
                                <td><?php echo htmlspecialchars($judge['display_name']); ?></td>
                                <td>
                                    <button class="btn btn-sm" onclick="viewJudge(<?php echo $judge['id']; ?>)">
                                        👁️ View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="padding: 2rem; text-align: center; color: #64748b;">
                <p>No judges found. Add your first judge to get started!</p>
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
                        <strong><?php echo htmlspecialchars($activity['judge_name']); ?></strong>
                        scored <strong><?php echo htmlspecialchars($activity['user_name']); ?></strong>
                    </div>
                    <div class="activity-score"><?php echo htmlspecialchars($activity['points']); ?> pts</div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #64748b; text-align: center; padding: 1rem;">No recent activity</p>
        <?php endif; ?>
    </div>
</div>

<!-- Add Judge Modal -->
<div id="addJudgeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add New Judge</h3>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="addJudgeForm">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="display_name">Display Name:</label>
                    <input type="text" id="display_name" name="display_name" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeModal()" style="background: #6b7280; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        Add Judge
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functionality
function openModal() {
    document.getElementById('addJudgeModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('addJudgeModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    // Reset form
    document.getElementById('addJudgeForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addJudgeModal');
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

// View judge function (placeholder)
function viewJudge(judgeId) {
    alert('View judge functionality - Judge ID: ' + judgeId);
    // You can implement this to show judge details, scores given, etc.
}

// Auto-close success/error messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
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
});

// Add loading state to form submission
document.getElementById('addJudgeForm').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<span>⏳</span> Adding Judge...';
    submitBtn.disabled = true;
});

// Add smooth animations to stat cards
document.addEventListener('DOMContentLoaded', function() {
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
</script>

<?php include 'includes/footer.php'; ?>