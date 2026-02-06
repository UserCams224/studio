<?php
// Configuration de la base de données
$servername = "localhost";
$username = "root"; // À modifier selon votre configuration
$password = ""; // À modifier selon votre configuration
$dbname = "datastudio"; // Nom de la base de données

// Connexion à la base de données
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion à la base de données: " . $conn->connect_error);
}

// Définir le fuseau horaire
date_default_timezone_set('Europe/Paris');

// Traitement du formulaire de réservation
$reservation_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reserver'])) {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $telephone = htmlspecialchars(trim($_POST['telephone']));
    $adresse = htmlspecialchars(trim($_POST['adresse']));
    $message = htmlspecialchars(trim($_POST['message']));
    
    // La date de réservation est générée automatiquement (date et heure actuelles)
    $date_reservation = date('Y-m-d H:i:s'); // Format MySQL DATETIME
    
    // Validation des données
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom est requis";
    }
    
    if (empty($telephone)) {
        $errors[] = "Le téléphone est requis";
    } elseif (!preg_match("/^\+224[0-9]{9}$/", $telephone)) {
        // Format: +224 suivi de 9 chiffres exactement
        $errors[] = "Format de téléphone invalide. Le format attendu est: +224XXXXXXXXX (9 chiffres après +224)";
    }
    
    if (empty($adresse)) {
        $errors[] = "L'adresse est requise";
    }
    
    // Si pas d'erreurs, insertion dans la base
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO reservation (nom, date_reservation, telephone, adresse, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nom, $date_reservation, $telephone, $adresse, $message);
        
        if ($stmt->execute()) {
            $reservation_message = '<div class="success-message">Réservation effectuée avec succès! Nous vous contacterons pour confirmation.</div>';
        } else {
            $reservation_message = '<div class="error-message">Erreur lors de la réservation. Veuillez réessayer.</div>';
        }
        
        $stmt->close();
    } else {
        $reservation_message = '<div class="error-message">' . implode('<br>', $errors) . '</div>';
    }
}

// Traitement de la connexion admin
$admin_error = "";
$admin_logged_in = false;
$reservations = [];

// Vérifier si l'utilisateur est déjà connecté
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $admin_logged_in = true;
    
    // Récupérer les réservations
    $result = $conn->query("SELECT * FROM reservation ORDER BY date_reservation DESC");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }
    }
}

// Traitement de la connexion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $user_name = htmlspecialchars(trim($_POST['user_name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $mot_de_passe = $_POST['mot_de_passe'];
    
    // Vérification des identifiants
    if ($user_name === "Sharawi" && $email === "aboubacar625prod@gmail.com" && $mot_de_passe === "@Fatoumata625") {
        $_SESSION['admin_logged_in'] = true;
        $admin_logged_in = true;
        
        // Récupérer les réservations
        $result = $conn->query("SELECT * FROM reservation ORDER BY date_reservation DESC");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $reservations[] = $row;
            }
        }
    } else {
        $admin_error = '<div class="error-message">Identifiants incorrects. Veuillez réessayer.</div>';
    }
}

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sharawi Prod - Studio d'enregistrement professionnel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles généraux */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #0f0f1a;
            color: #f1f1f1;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 20px 0;
            border-bottom: 2px solid #8a2be2;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .logo i {
            color: #8a2be2;
            font-size: 2.5rem;
        }
        
        .logo h1 {
            font-size: 2.5rem;
            background: linear-gradient(90deg, #8a2be2, #4a00e0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 0 15px rgba(138, 43, 226, 0.3);
        }
        
        nav ul {
            display: flex;
            justify-content: center;
            list-style: none;
            gap: 30px;
            margin-top: 15px;
        }
        
        nav a {
            color: #e0e0ff;
            text-decoration: none;
            font-weight: 500;
            font-size: 1.1rem;
            transition: color 0.3s;
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        nav a:hover {
            color: #8a2be2;
            background-color: rgba(138, 43, 226, 0.1);
        }
        
        /* Section Hero */
        .hero {
            background: linear-gradient(rgba(10, 10, 20, 0.85), rgba(10, 10, 20, 0.9)), url('https://images.unsplash.com/photo-1511379938547-c1f69419868d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 60px;
        }
        
        .hero h2 {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ffffff;
            text-shadow: 0 0 10px rgba(138, 43, 226, 0.7);
        }
        
        .hero p {
            font-size: 1.3rem;
            max-width: 800px;
            margin: 0 auto 30px;
            color: #ccccff;
        }
        
        .highlight {
            color: #8a2be2;
            font-weight: bold;
            font-style: italic;
        }
        
        /* Section Présentation */
        .presentation {
            padding: 60px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            font-size: 2.5rem;
            color: #8a2be2;
            position: relative;
        }
        
        .section-title:after {
            content: '';
            display: block;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, #8a2be2, #4a00e0);
            margin: 15px auto;
            border-radius: 2px;
        }
        
        .studio-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .studio-card {
            background-color: #1a1a2e;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #2a2a3e;
        }
        
        .studio-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(138, 43, 226, 0.2);
        }
        
        .studio-card i {
            font-size: 3rem;
            color: #8a2be2;
            margin-bottom: 20px;
        }
        
        .studio-card h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: #e0e0ff;
        }
        
        /* Section Réservation */
        .reservation-section {
            background-color: #1a1a2e;
            padding: 60px 0;
            border-radius: 15px;
            margin-bottom: 60px;
            border: 1px solid #2a2a3e;
        }
        
        .reservation-form {
            max-width: 800px;
            margin: 0 auto;
            background-color: #232339;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ccccff;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background-color: #0f0f1a;
            border: 1px solid #3a3a4e;
            border-radius: 5px;
            color: #f1f1f1;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #8a2be2;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(90deg, #8a2be2, #4a00e0);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 15px rgba(138, 43, 226, 0.4);
        }
        
        .btn-block {
            display: block;
            width: 100%;
        }
        
        .success-message {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2ecc71;
        }
        
        .error-message {
            background-color: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        
        /* Afficheur de date et heure */
        .datetime-display {
            text-align: center;
            margin: 20px 0 30px;
            padding: 15px;
            background-color: rgba(138, 43, 226, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(138, 43, 226, 0.3);
        }
        
        .datetime-label {
            font-size: 1.1rem;
            color: #ccccff;
            margin-bottom: 10px;
        }
        
        .datetime-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #8a2be2;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        
        .datetime-note {
            font-size: 0.9rem;
            color: #8888aa;
            margin-top: 10px;
            font-style: italic;
        }
        
        /* Aide format téléphone */
        .phone-format {
            display: block;
            margin-top: 5px;
            font-size: 0.9rem;
            color: #8a2be2;
            font-weight: 500;
        }
        
        .phone-example {
            background-color: rgba(138, 43, 226, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            border-left: 3px solid #8a2be2;
        }
        
        .phone-example span {
            font-family: 'Courier New', monospace;
            color: #ffffff;
            font-weight: bold;
        }
        
        /* Interface Admin */
        .admin-section {
            padding: 60px 0;
            min-height: 70vh;
        }
        
        .login-form {
            max-width: 500px;
            margin: 0 auto;
            background-color: #232339;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #8a2be2;
            cursor: pointer;
        }
        
        .reservations-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background-color: #232339;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
        
        .reservations-table th {
            background-color: #8a2be2;
            color: white;
            padding: 15px;
            text-align: left;
        }
        
        .reservations-table td {
            padding: 15px;
            border-bottom: 1px solid #3a3a4e;
        }
        
        .reservations-table tr:nth-child(even) {
            background-color: #1f1f33;
        }
        
        .reservations-table tr:hover {
            background-color: #2a2a3e;
        }
        
        .no-reservations {
            text-align: center;
            padding: 40px;
            color: #8888aa;
            font-style: italic;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        /* Footer */
        footer {
            background-color: #0a0a14;
            padding: 40px 0;
            border-top: 1px solid #2a2a3e;
            margin-top: 60px;
        }
        
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-links a {
            color: #ccccff;
            text-decoration: none;
            margin-left: 20px;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #8a2be2;
        }
        
        .copyright {
            margin-top: 20px;
            text-align: center;
            width: 100%;
            color: #8888aa;
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .logo h1 {
                font-size: 2rem;
            }
            
            nav ul {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .hero h2 {
                font-size: 2.2rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .reservation-form, .login-form {
                padding: 25px;
            }
            
            .datetime-value {
                font-size: 1.4rem;
            }
            
            .reservations-table {
                display: block;
                overflow-x: auto;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 20px;
            }
            
            .footer-links a {
                margin: 0 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header avec navigation -->
    <header>
        <div class="container">
            <div class="logo">
                <i class="fas fa-headphones"></i>
                <h1>Sharawi Prod</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="#presentation"><i class="fas fa-info-circle"></i> Présentation</a></li>
                    <li><a href="#equipement"><i class="fas fa-sliders-h"></i> Équipement</a></li>
                    <li><a href="#reservation"><i class="fas fa-calendar-alt"></i> Réservation</a></li>
                    <li><a href="#contact"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Section Hero -->
    <section class="hero">
        <div class="container">
            <h2>Votre studio d'enregistrement professionnel</h2>
            <p>
                <!-- Commentaire: Message d'accueil inspirant -->
                Vous recherchez un studio professionnel ? Vous êtes au bon endroit. 
                <span class="highlight">Sharawi Prod</span> vous offre une expérience d'enregistrement exceptionnelle 
                pour donner à votre musique une autre dimension.
            </p>
            <a href="#reservation" class="btn">Réserver maintenant</a>
        </div>
    </section>

    <!-- Section Présentation -->
    <section id="presentation" class="presentation">
        <div class="container">
            <h2 class="section-title">Notre Studio</h2>
            <p style="text-align: center; max-width: 900px; margin: 0 auto 50px; font-size: 1.2rem;">
                Sharawi Prod est un studio d'enregistrement de pointe, équipé des dernières technologies 
                pour vous offrir une qualité sonore exceptionnelle. Que vous soyez artiste solo, groupe ou 
                producteur, notre studio est conçu pour répondre à tous vos besoins créatifs.
            </p>
            
            <div class="studio-info">
                <div class="studio-card">
                    <i class="fas fa-microphone-alt"></i>
                    <h3>Cabine d'enregistrement</h3>
                    <p>Cabine insonorisée professionnelle avec traitement acoustique de haute qualité pour des prises de son parfaites.</p>
                </div>
                
                <div class="studio-card">
                    <i class="fas fa-sliders-h"></i>
                    <h3>Salle de contrôle</h3>
                    <p>Équipée d'une console de mixage numérique et de moniteurs de studio de référence pour un mixage précis.</p>
                </div>
                
                <div id="equipement" class="studio-card">
                    <i class="fas fa-guitar"></i>
                    <h3>Instruments & Équipements</h3>
                    <p>Large sélection d'instruments et d'équipements professionnels à votre disposition.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Réservation -->
    <section id="reservation" class="reservation-section">
        <div class="container">
            <h2 class="section-title">Réservation</h2>
            
            <!-- Afficheur de date et heure en temps réel -->
            <div class="datetime-display">
                <div class="datetime-label">Date et heure actuelles :</div>
                <div class="datetime-value" id="live-datetime">
                    <!-- L'heure sera mise à jour en temps réel par JavaScript -->
                    <?php echo date('d/m/Y H:i:s'); ?>
                </div>
                <div class="datetime-note">
                    La date et l'heure de votre réservation seront automatiquement enregistrées au moment de votre soumission
                </div>
            </div>
            
            <?php if (!empty($reservation_message)) echo $reservation_message; ?>
            
            <div class="reservation-form">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nom">Nom complet *</label>
                        <input type="text" id="nom" name="nom" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Numéro de téléphone *</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" required 
                               placeholder="+224XXXXXXXXX" 
                               pattern="\+224[0-9]{9}"
                               title="Format: +224 suivi de 9 chiffres">
                        <span class="phone-format">Format requis: <strong>+224</strong> suivi de <strong>9 chiffres</strong></span>
                        <div class="phone-example">
                            Exemple: <span>+224621234567</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="adresse">Adresse *</label>
                        <input type="text" id="adresse" name="adresse" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message (précisions sur votre projet, durée estimée, etc.)</label>
                        <textarea id="message" name="message" class="form-control" rows="5"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="datetime-note" style="text-align: center; padding: 10px; background-color: rgba(138, 43, 226, 0.05); border-radius: 5px;">
                            <i class="fas fa-info-circle"></i> Votre réservation sera enregistrée avec la date et l'heure actuelles
                        </div>
                    </div>
                    
                    <button type="submit" name="reserver" class="btn btn-block">
                        <i class="fas fa-calendar-check"></i> Confirmer la réservation
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Interface Admin -->
    <?php if (!$admin_logged_in): ?>
    <!-- Section de connexion admin (cachée dans le footer) -->
    <footer id="contact">
        <div class="container">
            <div class="footer-content">
                <div>
                    <h3>Sharawi Prod</h3>
                    <p>Studio d'enregistrement professionnel</p>
                </div>
                <div class="footer-links">
                    <a href="#presentation">Présentation</a>
                    <a href="#reservation">Réservation</a>
                    <a href="#" id="admin-link">Admin</a>
                </div>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> Sharawi Prod. Tous droits réservés.
            </div>
        </div>
    </footer>

    <!-- Modal de connexion admin -->
    <div id="admin-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;">
        <div class="login-form">
            <h2 class="section-title">Connexion Admin</h2>
            
            <?php if (!empty($admin_error)) echo $admin_error; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="user_name">Nom d'utilisateur *</label>
                    <input type="text" id="user_name" name="user_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe *</label>
                    <div class="password-container">
                        <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" required>
                        <button type="button" class="toggle-password" id="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <button type="submit" name="login" class="btn">Se connecter</button>
                    <button type="button" id="close-modal" class="btn" style="background: #666;">Annuler</button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Interface admin une fois connecté -->
    <section class="admin-section">
        <div class="container">
            <div class="admin-header">
                <h2 class="section-title">Interface Administrateur</h2>
                <a href="?logout=true" class="btn">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
            
            <h3 style="margin-bottom: 20px; color: #ccccff;">Liste des réservations</h3>
            
            <?php if (count($reservations) > 0): ?>
            <table class="reservations-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Date & Heure de réservation</th>
                        <th>Téléphone</th>
                        <th>Adresse</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td><?php echo $reservation['id']; ?></td>
                        <td><?php echo htmlspecialchars($reservation['nom']); ?></td>
                        <td>
                            <?php 
                            // Formatage de la date pour l'affichage
                            $date = new DateTime($reservation['date_reservation']);
                            echo $date->format('d/m/Y à H:i:s');
                            ?>
                        </td>
                        <td>
                            <span style="font-family: 'Courier New', monospace; color: #8a2be2;">
                                <?php echo htmlspecialchars($reservation['telephone']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($reservation['adresse']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['message']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-reservations">
                <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 20px; color: #666;"></i>
                <h3>Aucune réservation pour le moment</h3>
                <p>Les réservations effectuées via le formulaire apparaîtront ici.</p>
            </div>
            <?php endif; ?>
            
            <!-- Affichage du nombre total de réservations -->
            <div style="margin-top: 20px; padding: 15px; background-color: rgba(138, 43, 226, 0.1); border-radius: 10px; text-align: center;">
                <p style="color: #ccccff; font-size: 1.1rem;">
                    <i class="fas fa-chart-bar"></i> 
                    Total des réservations : <strong style="color: #8a2be2;"><?php echo count($reservations); ?></strong>
                </p>
            </div>
        </div>
    </section>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div>
                    <h3>Sharawi Prod - Admin</h3>
                    <p>Interface d'administration</p>
                </div>
                <div class="footer-links">
                    <a href="?logout=true">Déconnexion</a>
                </div>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> Sharawi Prod. Tous droits réservés.
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <script>
        // Gestion de l'affichage/masquage du mot de passe
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('mot_de_passe');
            
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            // Gestion du modal admin
            const adminLink = document.getElementById('admin-link');
            const adminModal = document.getElementById('admin-modal');
            const closeModal = document.getElementById('close-modal');
            
            if (adminLink && adminModal) {
                adminLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    adminModal.style.display = 'flex';
                });
                
                closeModal.addEventListener('click', function() {
                    adminModal.style.display = 'none';
                });
                
                // Fermer le modal en cliquant en dehors
                adminModal.addEventListener('click', function(e) {
                    if (e.target === adminModal) {
                        adminModal.style.display = 'none';
                    }
                });
            }
            
            // Navigation fluide
            document.querySelectorAll('nav a').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href.startsWith('#')) {
                        e.preventDefault();
                        const targetId = href.substring(1);
                        const targetElement = document.getElementById(targetId);
                        if (targetElement) {
                            window.scrollTo({
                                top: targetElement.offsetTop - 80,
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });
            
            // Mise à jour en temps réel de la date et l'heure
            function updateDateTime() {
                const now = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                };
                
                // Format français
                const dateStr = now.toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
                
                const timeStr = now.toLocaleTimeString('fr-FR', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                
                // Affichage formaté
                const datetimeElement = document.getElementById('live-datetime');
                if (datetimeElement) {
                    datetimeElement.textContent = `${dateStr} ${timeStr}`;
                }
            }
            
            // Mettre à jour l'heure toutes les secondes
            updateDateTime();
            setInterval(updateDateTime, 1000);
            
            // Validation du format téléphone en temps réel
            const phoneInput = document.getElementById('telephone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    const value = this.value;
                    const phonePattern = /^\+224[0-9]{0,9}$/;
                    
                    if (!phonePattern.test(value) && value !== '') {
                        this.style.borderColor = '#e74c3c';
                    } else {
                        this.style.borderColor = value.length === 13 ? '#2ecc71' : '#3a3a4e';
                    }
                });
                
                // Aide automatique pour le format
                phoneInput.addEventListener('focus', function() {
                    if (this.value === '') {
                        this.value = '+224';
                    }
                });
                
                // Empêcher la suppression du +224
                phoneInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value === '+224') {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>