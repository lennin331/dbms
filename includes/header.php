<?php
// includes/header.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaManage - Pharmaceutical Management System</title>
    <link rel="stylesheet" href="/pharma/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container header-content">
            <div class="logo">
                <i class="fas fa-pills"></i>
                Pharma<span style="color: var(--accent-primary);">Manage</span>
            </div>
            <div class="header-right">
                <span class="badge badge-success">
                    <i class="fas fa-database"></i> MySQL 8.0 Connected
                </span>
            </div>
        </div>
    </header>