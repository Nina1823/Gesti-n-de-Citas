<?php
require 'config.php';

// Obtener ID de la cita
$id = $_GET['id'];

// Si envió el formulario (POST), actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $service_id = $_POST['service_id'];
    $provider_id = $_POST['provider_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    
    $sql = "UPDATE appointments 
            SET user_id = ?, service_id = ?, provider_id = ?, appointment_date = ?, appointment_time = ? 
            WHERE id_appointment = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissi", $user_id, $service_id, $provider_id, $appointment_date, $appointment_time, $id);
    
    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        $error = "Error al actualizar: " . $stmt->error;
    }
}

// Obtener datos de la cita
$sql = "SELECT * FROM appointments WHERE id_appointment = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$cita = $stmt->get_result()->fetch_assoc();

// Obtener listas para los selects
$providers = $conn->query("SELECT id_provider, name FROM providers");
$services = $conn->query("SELECT id_service, name_service FROM services");
$users = $conn->query("SELECT id_user, name, document_type, document_number FROM users");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cita</title>
</head>
<body>
    <h1>Editar Cita #<?php echo $id; ?></h1>
    
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <form method="POST">
        <label>Paciente:</label>
        <select name="user_id" required>
            <?php while($user = $users->fetch_assoc()): ?>
                <option value="<?php echo $user['id_user']; ?>" <?php echo ($user['id_user'] == $cita['user_id']) ? 'selected' : ''; ?>>
                    <?php echo $user['name'] . " - " . $user['document_type'] . ": " . $user['document_number']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <br><br>
        
        <label>Servicio:</label>
        <select name="service_id" required>
            <?php while($service = $services->fetch_assoc()): ?>
                <option value="<?php echo $service['id_service']; ?>" <?php echo ($service['id_service'] == $cita['service_id']) ? 'selected' : ''; ?>>
                    <?php echo $service['name_service']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <br><br>
        
        <label>Proveedor:</label>
        <select name="provider_id" required>
            <?php while($provider = $providers->fetch_assoc()): ?>
                <option value="<?php echo $provider['id_provider']; ?>" <?php echo ($provider['id_provider'] == $cita['provider_id']) ? 'selected' : ''; ?>>
                    <?php echo $provider['name']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <br><br>
        
        <label>Fecha:</label>
        <input type="date" name="appointment_date" value="<?php echo $cita['appointment_date']; ?>" required>
        <br><br>
        
        <label>Hora:</label>
        <input type="time" name="appointment_time" value="<?php echo $cita['appointment_time']; ?>" required>
        <br><br>
        
        <button type="submit">Guardar Cambios</button>
        <a href="index.php">Cancelar</a>
    </form>
</body>
</html>