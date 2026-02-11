<?php
session_start();

// PROTECCIÓN
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'paciente') {
    header("Location: login.php");
    exit;
}

require 'config.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Obtener datos para los select
$providers = $conn->query("SELECT id_provider, name FROM providers ORDER BY name");
$services = $conn->query("SELECT id_service, name_service, duration_minutes FROM services ORDER BY name_service");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = $_POST['service_id'];
    $provider_id = $_POST['provider_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    
    // Validar que no haya duplicados
    $check_sql = "SELECT id_appointment FROM appointments 
                  WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $appointment_date, $appointment_time);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $mensaje = "Error: Ya existe una cita en ese horario";
        $tipo_mensaje = "error";
    } else {
        // Insertar cita
        $sql = "INSERT INTO appointments (user_id, service_id, provider_id, appointment_date, appointment_time) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiss", $user_id, $service_id, $provider_id, $appointment_date, $appointment_time);
        
        if ($stmt->execute()) {
            $mensaje = "Cita agendada correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al agendar: " . $stmt->error;
            $tipo_mensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita</title>
</head>
<body>
    <h1>Agendar Nueva Cita</h1>
    
    <p>Paciente: <strong><?php echo $user_name; ?></strong></p>
    <p><a href="mis-citas-paciente.php">← Volver a mis citas</a> | <a href="logout.php">Cerrar sesión</a></p>
    
    <?php if (isset($mensaje)): ?>
        <p style="color: <?php echo ($tipo_mensaje == 'success') ? 'green' : 'red'; ?>;">
            <?php echo $mensaje; ?>
        </p>
    <?php endif; ?>
    
    <hr>
    
    <form method="POST">
        <label>Servicio:</label>
        <select name="service_id" required>
            <option value="">Seleccione...</option>
            <?php while($service = $services->fetch_assoc()): ?>
                <option value="<?php echo $service['id_service']; ?>">
                    <?php echo $service['name_service'] . " (" . $service['duration_minutes'] . " min)"; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <br><br>
        
        <label>Proveedor:</label>
        <select name="provider_id" required>
            <option value="">Seleccione...</option>
            <?php while($provider = $providers->fetch_assoc()): ?>
                <option value="<?php echo $provider['id_provider']; ?>">
                    <?php echo $provider['name']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <br><br>
        
        <label>Fecha:</label>
        <input type="date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
        <br><br>
        
        <label>Hora:</label>
        <input type="time" name="appointment_time" required>
        <br><br>
        
        <button type="submit">Agendar Cita</button>
    </form>
</body>
</html>