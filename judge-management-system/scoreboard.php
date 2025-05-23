<?php
require_once 'config/db.php';

// Fetch all users with their total scores
try {
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.name,
            COALESCE(SUM(s.points), 0) as total_points,
            COUNT(s.id) as judges_count
        FROM 
            users u
        LEFT JOIN 
            scores s ON u.id = s.user_id
        GROUP BY 
            u.id, u.name
        ORDER BY 
            total_points DESC, u.name
    ");
    $users = $stmt->fetchAll();
    
    // Find the highest score
    $highest_score = 0;
    if (count($users) > 0) {
        $highest_score = $users[0]['total_points'];
    }
} catch (PDOException $e) {
    $error_message = "Error fetching scoreboard: " . $e->getMessage();
    $users = [];
    $highest_score = 0;
}
?>

<?php include 'includes/header.php'; ?>

<div class="scoreboard-container">
    <h2 class="scoreboard-title">Public Scoreboard</h2>
    
    <?php if (count($users) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Name</th>
                        <th>Total Points</th>
                        <th>Judges Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($users as $user): 
                        $is_top_score = ($user['total_points'] == $highest_score && $highest_score > 0);
                    ?>
                        <tr class="<?php echo $is_top_score ? 'top-score' : ''; ?>">
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['total_points']); ?></td>
                            <td><?php echo htmlspecialchars($user['judges_count']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No scores available yet.</p>
    <?php endif; ?>
</div>

<script>
    // Auto-refresh the page every 10 seconds
    setTimeout(function() {
        location.reload();
    }, 10000);
</script>

<?php include 'includes/footer.php'; ?>