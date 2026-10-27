<?php
session_start();

$conn = new mysqli("localhost", "root", "", "contact_db");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/* Create table if it doesn't exist */
$conn->query("
    CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        email VARCHAR(150) DEFAULT '',
        address VARCHAR(255) DEFAULT '',
        favorite TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

/* Add favorite column if missing */
$check = $conn->query("SHOW COLUMNS FROM contacts LIKE 'favorite'");

if ($check->num_rows == 0) {
    $conn->query("
        ALTER TABLE contacts
        ADD COLUMN favorite TINYINT(1) DEFAULT 0
    ");
}

/* ADD CONTACT */
if (isset($_POST['add_contact'])) {

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name !== '' && $phone !== '') {

        $stmt = $conn->prepare("
            INSERT INTO contacts
            (name, phone, email, address)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssss",
            $name,
            $phone,
            $email,
            $address
        );

        $stmt->execute();
        $stmt->close();
    }

    header("Location: index.php");
    exit;
}

/* UPDATE CONTACT */
if (isset($_POST['update_contact'])) {

    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($id > 0 && $name !== '' && $phone !== '') {

        $stmt = $conn->prepare("
            UPDATE contacts
            SET name = ?,
                phone = ?,
                email = ?,
                address = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssssi",
            $name,
            $phone,
            $email,
            $address,
            $id
        );

        $stmt->execute();
        $stmt->close();
    }

    header("Location: index.php");
    exit;
}

/* DELETE CONTACT */
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    if ($id > 0) {

        $stmt = $conn->prepare(
            "DELETE FROM contacts WHERE id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: index.php");
    exit;
}

/* FAVORITE */
if (isset($_GET['favorite'])) {

    $id = (int)$_GET['favorite'];

    if ($id > 0) {

        $stmt = $conn->prepare("
            UPDATE contacts
            SET favorite = NOT favorite
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: index.php");
    exit;
}

/* FETCH CONTACTS */
$result = $conn->query("
    SELECT *
    FROM contacts
    ORDER BY id DESC
");

$contacts = [];

while ($row = $result->fetch_assoc()) {
    $contacts[] = $row;
}

$total = count($contacts);

/* FAVORITE COUNT */
$favResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM contacts
    WHERE favorite = 1
");

$favorites = (int)$favResult->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>ContactHub Pro</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f5f7fb;
    color: #172033;
}

/* SIDEBAR */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;

    width: 250px;
    height: 100vh;

    padding: 25px 16px;

    background:
        linear-gradient(
            180deg,
            #111827,
            #1e3a8a
        );

    color: white;

    z-index: 1000;

    box-shadow:
        5px 0 25px rgba(0,0,0,.08);
}

.brand {
    display: flex;
    align-items: center;

    gap: 12px;

    padding: 10px;

    margin-bottom: 35px;
}

.brand-icon {
    width: 43px;
    height: 43px;

    display: flex;
    align-items: center;
    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #3b82f6,
            #6366f1
        );

    border-radius: 13px;

    font-size: 21px;
}

.brand h2 {
    font-size: 20px;
}

.brand small {
    color: #93c5fd;
}

.nav-title {
    font-size: 10px;
    color: #94a3b8;

    margin:
        0 12px 10px;

    text-transform: uppercase;

    letter-spacing: 1.3px;
}

.menu a {
    display: flex;
    align-items: center;

    gap: 13px;

    padding: 13px 15px;

    margin: 5px 0;

    border-radius: 11px;

    color: #cbd5e1;

    text-decoration: none;

    transition: .25s;
}

.menu a:hover,
.menu a.active {
    color: white;

    background:
        rgba(255,255,255,.13);

    transform:
        translateX(3px);
}

.menu-icon {
    width: 22px;
    text-align: center;
}


/* MAIN */

.main {
    margin-left: 250px;

    padding:
        30px 35px;

    max-width: 1600px;
}


/* TOPBAR */

.topbar {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 30px;
}

.welcome h1 {
    font-size: 29px;
}

.welcome p {
    margin-top: 5px;

    color: #64748b;
}

.profile {
    display: flex;

    align-items: center;

    gap: 10px;
}

.profile-avatar {
    width: 43px;
    height: 43px;

    border-radius: 50%;

    display: flex;

    align-items: center;
    justify-content: center;

    color: white;

    font-weight: bold;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );
}


/* STATS */

.stats {
    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 20px;

    margin-bottom: 25px;
}

.stat-card {
    position: relative;

    overflow: hidden;

    background: white;

    padding: 22px;

    border-radius: 18px;

    box-shadow:
        0 7px 25px
        rgba(15,23,42,.06);

    transition: .3s;
}

.stat-card:hover {
    transform:
        translateY(-4px);
}

.stat-icon {
    width: 45px;
    height: 45px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background: #eff6ff;

    font-size: 20px;

    margin-bottom: 14px;
}

.stat-card h4 {
    font-size: 13px;

    color: #64748b;

    font-weight: 500;
}

.stat-card strong {
    display: block;

    font-size: 29px;

    margin-top: 5px;
}

.stat-decoration {
    position: absolute;

    right: -25px;
    top: -25px;

    width: 100px;
    height: 100px;

    border-radius: 50%;

    background:
        rgba(37,99,235,.06);
}


/* CONTENT */

.content-card {
    background: white;

    padding: 25px;

    border-radius: 20px;

    margin-bottom: 25px;

    box-shadow:
        0 7px 25px
        rgba(15,23,42,.06);
}

.section-head {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;
}

.section-head h2 {
    font-size: 19px;
}

.section-head span {
    color: #64748b;

    font-size: 13px;
}


/* TOOLBAR */

.toolbar {
    display: flex;

    gap: 12px;

    margin-bottom: 23px;
}

.search-wrapper {
    flex: 1;

    position: relative;
}

.search-icon {
    position: absolute;

    left: 15px;

    top: 50%;

    transform:
        translateY(-50%);

    color: #94a3b8;
}

.search {
    width: 100%;

    padding:
        13px
        15px
        13px
        43px;

    border:
        1px solid #dbe2ea;

    border-radius: 11px;

    outline: none;

    font-size: 14px;
}

.search:focus {
    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37,99,235,.1);
}

.primary-btn {
    border: 0;

    padding:
        0 20px;

    border-radius: 11px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    color: white;

    font-weight: 600;

    cursor: pointer;

    transition: .25s;
}

.primary-btn:hover {
    transform:
        translateY(-2px);

    box-shadow:
        0 7px 20px
        rgba(37,99,235,.25);
}


/* CONTACT GRID */

.contact-grid {
    display: grid;

    grid-template-columns:
        repeat(
            auto-fill,
            minmax(270px,1fr)
        );

    gap: 18px;
}

.contact-card {
    position: relative;

    background: white;

    border:
        1px solid #e7ebf1;

    border-radius: 17px;

    padding: 20px;

    transition: .3s;
}

.contact-card:hover {
    transform:
        translateY(-5px);

    border-color:
        #bfdbfe;

    box-shadow:
        0 12px 28px
        rgba(15,23,42,.08);
}

.card-top {
    display: flex;

    justify-content:
        space-between;

    align-items:
        flex-start;
}

.avatar {
    width: 52px;
    height: 52px;

    border-radius: 15px;

    display: flex;

    align-items: center;
    justify-content: center;

    color: white;

    font-size: 19px;

    font-weight: bold;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #7c3aed
        );
}

.star {
    border: 0;

    background: none;

    font-size: 21px;

    cursor: pointer;

    text-decoration: none;

    transition: .2s;
}

.star:hover {
    transform:
        scale(1.2);
}

.contact-name {
    margin-top: 15px;

    font-size: 17px;
}

.contact-role {
    color: #64748b;

    font-size: 12px;

    margin-top: 3px;
}

.contact-info {
    margin-top: 17px;

    display: flex;

    flex-direction: column;

    gap: 9px;
}

.info {
    display: flex;

    gap: 10px;

    align-items: center;

    color: #64748b;

    font-size: 13px;
}

.info-icon {
    width: 27px;
    height: 27px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 8px;

    background: #f1f5f9;

    flex-shrink: 0;
}


/* ACTION BUTTONS */

.card-actions {
    display: grid;

    grid-template-columns:
        1fr 1fr 1fr;

    gap: 7px;

    margin-top: 18px;
}

.action-btn {
    padding: 9px 5px;

    border-radius: 9px;

    text-align: center;

    text-decoration: none;

    font-size: 11px;

    font-weight: 600;

    transition: .2s;

    border: 0;

    cursor: pointer;
}

.edit-btn {
    background: #eff6ff;

    color: #2563eb;
}

.favorite-btn {
    background: #fff7ed;

    color: #c2410c;
}

.delete-btn {
    background: #fef2f2;

    color: #dc2626;
}

.action-btn:hover {
    filter: brightness(.96);

    transform:
        translateY(-1px);
}


/* EMPTY */

.empty {
    padding: 55px 20px;

    text-align: center;

    color: #64748b;
}

.empty-icon {
    font-size: 45px;

    margin-bottom: 10px;
}


/* SETTINGS DOES NOT EXIST */


/* MODAL */

.modal {
    display: none;

    position: fixed;

    inset: 0;

    background:
        rgba(15,23,42,.6);

    backdrop-filter:
        blur(5px);

    z-index: 2000;

    align-items: center;

    justify-content: center;

    padding: 20px;
}

.modal.show {
    display: flex;
}

.modal-box {
    width: 100%;

    max-width: 570px;

    background: white;

    border-radius: 20px;

    padding: 28px;

    animation:
        modalIn .25s ease;
}

@keyframes modalIn {

    from {
        opacity: 0;

        transform:
            translateY(20px)
            scale(.97);
    }

    to {
        opacity: 1;

        transform: none;
    }
}

.modal-head {
    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 25px;
}

.modal-head h2 {
    font-size: 21px;
}

.modal-head p {
    color: #64748b;

    font-size: 12px;

    margin-top: 4px;
}

.close {
    width: 34px;
    height: 34px;

    border: 0;

    background: #f1f5f9;

    border-radius: 9px;

    font-size: 20px;

    cursor: pointer;
}

.form-grid {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 15px;
}

.form-group {
    display: flex;

    flex-direction: column;

    gap: 7px;
}

.form-group label {
    font-size: 11px;

    font-weight: 700;

    color: #475569;

    letter-spacing: .4px;
}

.form-group input {
    padding: 12px;

    border:
        1px solid #dbe2ea;

    border-radius: 10px;

    outline: none;

    font-size: 14px;
}

.form-group input:focus {
    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37,99,235,.08);
}

.save-btn {
    width: 100%;

    padding: 13px;

    margin-top: 20px;

    border: 0;

    border-radius: 10px;

    color: white;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    font-weight: bold;

    cursor: pointer;
}


/* TOAST */

.toast {
    position: fixed;

    right: 25px;

    bottom: 25px;

    background: #172033;

    color: white;

    padding:
        14px 18px;

    border-radius: 11px;

    box-shadow:
        0 10px 30px
        rgba(0,0,0,.2);

    transform:
        translateY(100px);

    opacity: 0;

    transition: .3s;

    z-index: 3000;

    font-size: 13px;
}

.toast.show {
    transform:
        translateY(0);

    opacity: 1;
}


/* RESPONSIVE */

@media(max-width:900px) {

    .sidebar {
        position: relative;

        width: 100%;

        height: auto;
    }

    .main {
        margin-left: 0;

        padding: 22px;
    }

    .stats {
        grid-template-columns: 1fr;
    }
}

@media(max-width:600px) {

    .profile {
        display: none;
    }

    .toolbar {
        flex-direction: column;
    }

    .primary-btn {
        height: 45px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}

</style>

</head>

<body>


<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="brand">

        <div class="brand-icon">
            👥
        </div>

        <div>

            <h2>
                ContactHub
            </h2>

            <small>
                Management System
            </small>

        </div>

    </div>


    <div class="nav-title">
        MAIN MENU
    </div>


    <nav class="menu">

        <a
            href="#dashboard"
            class="active"
            onclick="setActive(this)">

            <span class="menu-icon">
                ⌂
            </span>

            Dashboard

        </a>


        <a
            href="#contacts"
            onclick="setActive(this)">

            <span class="menu-icon">
                👤
            </span>

            All Contacts

        </a>


        <a
            href="#favorites"
            onclick="setActive(this)">

            <span class="menu-icon">
                ★
            </span>

            Favorites

        </a>

    </nav>

</aside>


<!-- MAIN -->

<main class="main">


<!-- TOPBAR -->

<div class="topbar">

    <div class="welcome">

        <h1>
            Good day 👋
        </h1>

        <p>
            Here's what's happening with your contacts.
        </p>

    </div>


    <div class="profile">

        <div class="profile-avatar">
            C
        </div>

        <div>

            <strong>
                Contact Manager
            </strong>

            <small
                style="
                display:block;
                color:#64748b;
                font-size:11px;
                margin-top:2px;
                ">

                Administrator

            </small>

        </div>

    </div>

</div>


<!-- DASHBOARD -->

<section id="dashboard">

<div class="stats">


    <div class="stat-card">

        <div class="stat-icon">
            👥
        </div>

        <h4>
            Total Contacts
        </h4>

        <strong>
            <?= $total ?>
        </strong>

        <div class="stat-decoration"></div>

    </div>


    <div class="stat-card">

        <div class="stat-icon">
            ⭐
        </div>

        <h4>
            Favorite Contacts
        </h4>

        <strong>
            <?= $favorites ?>
        </strong>

        <div class="stat-decoration"></div>

    </div>


    <div class="stat-card">

        <div class="stat-icon">
            ✓
        </div>

        <h4>
            System Status
        </h4>

        <strong
            style="
            font-size:20px;
            color:#16a34a;
            margin-top:14px;
            ">

            ● All Systems Online

        </strong>

        <div class="stat-decoration"></div>

    </div>


</div>

</section>


<!-- ALL CONTACTS -->

<section
    class="content-card"
    id="contacts">


    <div class="section-head">

        <div>

            <h2>
                All Contacts
            </h2>

            <span id="resultCount">
                <?= $total ?> contacts available
            </span>

        </div>

    </div>


    <div class="toolbar">


        <div class="search-wrapper">

            <span class="search-icon">
                🔍
            </span>

            <input
                type="text"
                id="search"
                class="search"
                placeholder="Search by name, phone or email..."
                autocomplete="off"
                autocapitalize="off"
                spellcheck="false">

        </div>


        <button
            class="primary-btn"
            onclick="openAddModal()">

            ＋ Add Contact

        </button>

    </div>


    <div
        class="contact-grid"
        id="contactGrid">


        <?php if ($total > 0): ?>


            <?php foreach ($contacts as $contact): ?>


                <?php

                $initial =
                    strtoupper(
                        substr(
                            trim($contact['name']),
                            0,
                            1
                        )
                    );

                $searchText =
                    strtolower(
                        $contact['name'] . ' ' .
                        $contact['phone'] . ' ' .
                        $contact['email'] . ' ' .
                        $contact['address']
                    );

                ?>


                <article
                    class="contact-card"
                    data-search="<?= htmlspecialchars($searchText) ?>">


                    <div class="card-top">


                        <div class="avatar">

                            <?= htmlspecialchars(
                                $initial
                            ) ?>

                        </div>


                        <a
                            href="?favorite=<?= $contact['id'] ?>"
                            class="star"
                            title="Favorite">

                            <?= $contact['favorite']
                                ? '⭐'
                                : '☆'
                            ?>

                        </a>


                    </div>


                    <h3 class="contact-name">

                        <?= htmlspecialchars(
                            $contact['name']
                        ) ?>

                    </h3>


                    <div class="contact-role">
                        Contact
                    </div>


                    <div class="contact-info">


                        <div class="info">

                            <span class="info-icon">
                                📞
                            </span>

                            <span>
                                <?= htmlspecialchars(
                                    $contact['phone']
                                ) ?>
                            </span>

                        </div>


                        <?php if (!empty($contact['email'])): ?>

                        <div class="info">

                            <span class="info-icon">
                                ✉
                            </span>

                            <span>
                                <?= htmlspecialchars(
                                    $contact['email']
                                ) ?>
                            </span>

                        </div>

                        <?php endif; ?>


                        <?php if (!empty($contact['address'])): ?>

                        <div class="info">

                            <span class="info-icon">
                                📍
                            </span>

                            <span>
                                <?= htmlspecialchars(
                                    $contact['address']
                                ) ?>
                            </span>

                        </div>

                        <?php endif; ?>


                    </div>


                    <div class="card-actions">


                        <button
                            class="action-btn edit-btn"
                            onclick='openEditModal(
                                <?= json_encode($contact) ?>
                            )'>

                            ✏ Edit

                        </button>


                        <a
                            href="?favorite=<?= $contact['id'] ?>"
                            class="action-btn favorite-btn">

                            <?= $contact['favorite']
                                ? '★ Fav'
                                : '☆ Fav'
                            ?>

                        </a>


                        <a
                            href="?delete=<?= $contact['id'] ?>"
                            class="action-btn delete-btn"
                            onclick="
                            return confirm(
                            'Are you sure you want to delete this contact?'
                            );
                            ">

                            🗑 Delete

                        </a>


                    </div>


                </article>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="empty">

                <div class="empty-icon">
                    👤
                </div>

                <h3>
                    No contacts yet
                </h3>

                <p>
                    Add your first contact to get started.
                </p>

            </div>


        <?php endif; ?>


    </div>

</section>


<!-- FAVORITES -->

<section
    class="content-card"
    id="favorites">


    <div class="section-head">

        <div>

            <h2>
                ⭐ Favorite Contacts
            </h2>

            <span>
                Your important contacts
            </span>

        </div>

    </div>


    <?php

    $favList =
        $conn->query("
            SELECT *
            FROM contacts
            WHERE favorite = 1
            ORDER BY name
        ");

    ?>


    <?php if ($favList->num_rows > 0): ?>


        <div class="contact-grid">


        <?php while ($fav = $favList->fetch_assoc()): ?>


            <article class="contact-card">


                <div class="card-top">


                    <div class="avatar">

                        <?= strtoupper(
                            substr(
                                $fav['name'],
                                0,
                                1
                            )
                        ) ?>

                    </div>


                    <span>
                        ⭐
                    </span>


                </div>


                <h3 class="contact-name">

                    <?= htmlspecialchars(
                        $fav['name']
                    ) ?>

                </h3>


                <div class="contact-info">


                    <div class="info">

                        <span class="info-icon">
                            📞
                        </span>

                        <span>
                            <?= htmlspecialchars(
                                $fav['phone']
                            ) ?>
                        </span>

                    </div>


                    <?php if (!empty($fav['email'])): ?>

                    <div class="info">

                        <span class="info-icon">
                            ✉
                        </span>

                        <span>
                            <?= htmlspecialchars(
                                $fav['email']
                            ) ?>
                        </span>

                    </div>

                    <?php endif; ?>


                </div>


                <div class="card-actions">


                    <button
                        class="action-btn edit-btn"
                        onclick='openEditModal(
                            <?= json_encode($fav) ?>
                        )'>

                        ✏ Edit

                    </button>


                    <a
                        href="?favorite=<?= $fav['id'] ?>"
                        class="action-btn favorite-btn">

                        ★ Remove

                    </a>


                    <a
                        href="?delete=<?= $fav['id'] ?>"
                        class="action-btn delete-btn"
                        onclick="
                        return confirm(
                        'Delete this contact?'
                        );
                        ">

                        🗑 Delete

                    </a>


                </div>


            </article>


        <?php endwhile; ?>


        </div>


    <?php else: ?>


        <div class="empty">

            <div class="empty-icon">
                ☆
            </div>

            <h3>
                No favorites yet
            </h3>

            <p>
                Star a contact to see it here.
            </p>

        </div>


    <?php endif; ?>


</section>


</main>


<!-- ADD / EDIT MODAL -->

<div
    class="modal"
    id="contactModal"
    onclick="outsideClose(event)">


    <div class="modal-box">


        <div class="modal-head">


            <div>

                <h2 id="modalTitle">
                    Add New Contact
                </h2>

                <p id="modalSubtitle">
                    Save a new contact to your directory
                </p>

            </div>


            <button
                class="close"
                onclick="closeModal()">

                ×

            </button>


        </div>


        <form
            method="POST"
            id="contactForm"
            autocomplete="off">


            <input
                type="hidden"
                name="id"
                id="contactId">


            <div class="form-grid">


                <div class="form-group">

                    <label>
                        FULL NAME
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="contactName"
                        placeholder="Enter full name"
                        required
                        autocomplete="off">

                </div>


                <div class="form-group">

                    <label>
                        PHONE
                    </label>

                    <input
                        type="tel"
                        name="phone"
                        id="contactPhone"
                        placeholder="Enter phone number"
                        required
                        autocomplete="off">

                </div>


                <div class="form-group">

                    <label>
                        EMAIL
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="contactEmail"
                        placeholder="name@example.com"
                        autocomplete="off">

                </div>


                <div class="form-group">

                    <label>
                        ADDRESS
                    </label>

                    <input
                        type="text"
                        name="address"
                        id="contactAddress"
                        placeholder="City / Location"
                        autocomplete="off">

                </div>


            </div>


            <button
                type="submit"
                name="add_contact"
                id="saveButton"
                class="save-btn">

                Save Contact

            </button>


        </form>


    </div>

</div>


<!-- TOAST -->

<div
    class="toast"
    id="toast">

    ✓ ContactHub ready

</div>


<script>

/* MENU */

function setActive(element) {

    document
        .querySelectorAll(".menu a")
        .forEach(function(item) {

            item.classList.remove("active");

        });

    element.classList.add("active");
}


/* ADD MODAL */

function openAddModal() {

    const modal =
        document.getElementById(
            "contactModal"
        );

    const form =
        document.getElementById(
            "contactForm"
        );

    form.reset();

    document.getElementById(
        "contactId"
    ).value = "";

    document.getElementById(
        "modalTitle"
    ).textContent =
        "Add New Contact";

    document.getElementById(
        "modalSubtitle"
    ).textContent =
        "Save a new contact to your directory";

    const button =
        document.getElementById(
            "saveButton"
        );

    button.name = "add_contact";

    button.textContent =
        "Save Contact";

    modal.classList.add("show");

    setTimeout(function() {

        document
            .getElementById("contactName")
            .focus();

    }, 200);
}


/* EDIT MODAL */

function openEditModal(contact) {

    document.getElementById(
        "contactId"
    ).value = contact.id;

    document.getElementById(
        "contactName"
    ).value = contact.name || "";

    document.getElementById(
        "contactPhone"
    ).value = contact.phone || "";

    document.getElementById(
        "contactEmail"
    ).value = contact.email || "";

    document.getElementById(
        "contactAddress"
    ).value = contact.address || "";

    document.getElementById(
        "modalTitle"
    ).textContent =
        "Edit Contact";

    document.getElementById(
        "modalSubtitle"
    ).textContent =
        "Update contact information";

    const button =
        document.getElementById(
            "saveButton"
        );

    button.name = "update_contact";

    button.textContent =
        "Update Contact";

    document
        .getElementById(
            "contactModal"
        )
        .classList.add("show");

    setTimeout(function() {

        document
            .getElementById("contactName")
            .focus();

    }, 200);
}


/* CLOSE MODAL */

function closeModal() {

    document
        .getElementById(
            "contactModal"
        )
        .classList.remove("show");
}


function outsideClose(event) {

    if (
        event.target ===
        document.getElementById(
            "contactModal"
        )
    ) {

        closeModal();

    }
}


document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {
            closeModal();
        }

    }
);


/* SEARCH */

document
    .getElementById("search")
    .addEventListener(
        "input",
        function() {

            const value =
                this.value
                    .toLowerCase()
                    .trim();

            const cards =
                document.querySelectorAll(
                    "#contactGrid .contact-card"
                );

            let visible = 0;

            cards.forEach(function(card) {

                const text =
                    card.dataset.search || "";

                if (text.includes(value)) {

                    card.style.display = "";
                    visible++;

                } else {

                    card.style.display = "none";

                }

            });

            const result =
                document.getElementById(
                    "resultCount"
                );

            if (value === "") {

                result.textContent =
                    "<?= $total ?> contacts available";

            } else {

                result.textContent =
                    visible +
                    " matching contact" +
                    (visible === 1 ? "" : "s");

            }

        }
    );


/* SCROLL NAVIGATION */

window.addEventListener(
    "scroll",
    function() {

        const sections = [
            "dashboard",
            "contacts",
            "favorites"
        ];

        let current = "";

        sections.forEach(function(id) {

            const section =
                document.getElementById(id);

            if (
                section &&
                window.scrollY >=
                section.offsetTop - 160
            ) {

                current = id;

            }

        });

        document
            .querySelectorAll(".menu a")
            .forEach(function(link) {

                link.classList.remove(
                    "active"
                );

                if (
                    link.getAttribute("href") ===
                    "#" + current
                ) {

                    link.classList.add(
                        "active"
                    );

                }

            });

    }
);

</script>

</body>
</html>