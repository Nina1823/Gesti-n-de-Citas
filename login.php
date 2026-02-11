<?php
session_start();

// Si ya está logueado, redirigir según rol
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'provider') {
        header("Location: index-providers.php");
    } else {
        header("Location: mis-citas.php");
    }
    exit;
}

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type']; // 'provider' o 'paciente'

    if ($user_type === 'provider') {
        // Buscar en tabla providers
        $sql = "SELECT id_provider, name, email, password FROM providers WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_type'] = 'provider';
                $_SESSION['provider_id'] = $user['id_provider'];
                $_SESSION['provider_name'] = $user['name'];
                $_SESSION['provider_email'] = $user['email'];

                header("Location: index-providers.php");
                exit;
            } else {
                $error = "Contraseña incorrecta";
            }
        } else {
            $error = "Proveedor no encontrado";
        }
    } elseif ($user_type === 'paciente') {
        // Buscar en tabla users
        $sql = "SELECT id_user, name, email, password, document_type, document_number FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_type'] = 'paciente';
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_document'] = $user['document_type'] . ' ' . $user['document_number'];

                header("Location: mis-citas-paciente.php");
                exit;
            } else {
                $error = "Contraseña incorrecta";
            }
        } else {
            $error = "Paciente no encontrado";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
</head>

<body>
    <h1>Iniciar Sesión</h1>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Tipo de usuario:</label>
        <select name="user_type" required>
            <option value="">Seleccione...</option>
            <option value="paciente">Paciente</option>
            <option value="provider">Proveedor de Servicios</option>
        </select>
        <br><br>

        <label>Email:</label>
        <input type="email" name="email" required autofocus>
        <br><br>

        <label>Contraseña:</label>
        <input type="password" name="password" required>
        <br><br>

        <button type="submit">Entrar</button>
    </form>

    <hr>
    <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
</body>

</html>