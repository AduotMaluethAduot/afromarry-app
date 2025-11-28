<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Guide - AfroMarry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../index.php"><i class="fas fa-heart"></i><span>AfroMarry</span></a>
            </div>
            <div class="nav-menu">
                <a href="/AfroMarry/pages/dashboard.php" class="nav-link">Dashboard</a>
                <a href="/AfroMarry/pages/cart.php" class="nav-link">Cart</a>
                <a href="/AfroMarry/auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container" style="max-width:900px;margin:2rem auto;padding:0 1rem;">
        <div class="card" style="background:#fff;border-radius:15px;padding:2rem;box-shadow:0 5px 20px rgba(0,0,0,.1);">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h1>Custom Cultural Guide</h1>
                <a href="/AfroMarry/index.php#tools" class="btn-secondary">Back to Tools</a>
            </div>
            <p>Tell us about both partners and we’ll generate a personalized cultural guide.</p>
            <form method="post" action="#" onsubmit="event.preventDefault(); alert('Guide request submitted!');">
                <div class="form-group">
                    <label>Partner A Tribe / Ethnicity</label>
                    <input type="text" name="tribe_a" placeholder="e.g., Yoruba" required />
                </div>
                <div class="form-group">
                    <label>Partner B Tribe / Ethnicity</label>
                    <input type="text" name="tribe_b" placeholder="e.g., Kikuyu" required />
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" name="country" placeholder="e.g., Nigeria" />
                </div>
                <div class="form-group">
                    <label>Questions / Focus Areas</label>
                    <textarea rows="4" name="notes" placeholder="Dowry expectations, attire, ceremony order..."></textarea>
                </div>
                <button type="submit" class="btn-primary">Request Guide</button>
            </form>
        </div>
    </div>
</body>
</html>


