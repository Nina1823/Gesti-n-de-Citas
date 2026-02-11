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
$titulo = "Login";

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
    <title><?php echo $titulo; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-calendar-check text-primary" style="font-size: 3rem;"></i>
                            <h2 class="mt-2">Iniciar Sesión</h2>
                            <p class="text-muted">Sistema de Gestión de Citas</p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="user_type" class="form-label">Tipo de usuario</label>
                                <select name="user_type" id="user_type" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <option value="paciente">Paciente</option>
                                    <option value="provider">Proveedor de Servicios</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required autofocus>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Entrar
                            </button>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="text-muted mb-2">¿No tienes cuenta?</p>
                            <a href="registro.php" class="btn btn-outline-primary">Regístrate aquí</a>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <p class="mb-1"><strong>Para testing:</strong></p>
                        <small class="text-muted">
                            Paciente: pedro@email.com / password<br>
                            Proveedor: carlos@clinic.com / password
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>