<?php
    session_start();

// Si ya está logueado, redirigir
if (isset($_SESSION['provider_id'])) {
    header("Location: index-providers.php");
    exit;
}

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] ==='POST'){
    $email = $_POST['email'];
    $password = $_POST['password'];

    //Buscar provider por email
    $sql = "SELECT id_provider, name, email, password FROM providers WHERE email= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows ==1){
        $provider = $result->fetch_assoc();

        //verificar contraseña
        if(password_verify($password, $provider['password'])){
            //Contraseña correcta, crear sesión
            $_SESSION['provider_id'] = $provider['id_provider'];
            $_SESSION['provider_name'] = $provider['name'];
            $_SESSION['provider_email'] = $provider['email'];

            header("Location: index-providers.php");
            exit;
        }else{
            $error= "Contraseña incorrecta";
        }
    }else{
        $error= "email no encontrado";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Proveedores</title>
</head>
<body>
    <h1>Iniciar Sesión - Proveedores</h1>
    
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <form method="POST">
        <label>Email:</label>
        <input type="email" name="email" required autofocus>
        <br><br>
        
        <label>Contraseña:</label>
        <input type="password" name="password" required>
        <br><br>
        
        <button type="submit">Entrar</button>
    </form>
    
    <hr>
    <p><strong>Para testing:</strong></p>
    <p>Email: carlos@clinic.com (o ana@clinic.com, juan@clinic.com)</p>
    <p>Contraseña: password</p>
</body>
</html>