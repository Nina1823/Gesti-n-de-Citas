<?php
session_start();

// PROTECCIÓN: Si no está logueado, redirigir a login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'paciente') {
    header("Location: login.php");
    exit;
}

require 'config.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// PROCESAR ACCIONES (si quieres que pacientes cancelen citas)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CANCELAR CITA
    if (isset($_POST['accion']) && $_POST['accion'] == 'cancelar') {
        $id_cita = $_POST['id_cita'];

        // Verificar que la cita pertenece al paciente logueado
        $check_sql = "SELECT user_id FROM appointments WHERE id_appointment = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id_cita);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $cita_data = $check_result->fetch_assoc();

        if ($cita_data['user_id'] == $user_id) {
            $cancel_sql = "UPDATE appointments SET status = 'cancelled' WHERE id_appointment = ?";
            $cancel_stmt = $conn->prepare($cancel_sql);
            $cancel_stmt->bind_param("i", $id_cita);

            if ($cancel_stmt->execute()) {
                $mensaje = "Cita cancelada correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al cancelar";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "No tienes permiso para cancelar esta cita";
            $tipo_mensaje = "error";
        }
    }
}

// CONSULTA DE CITAS - SOLO DEL PACIENTE LOGUEADO
$sql_citas = "SELECT 
    appointment.id_appointment,
    service.name_service as servicio,
    service.duration_minutes,
    proveedor.name as proveedor,
    appointment.appointment_date,
    appointment.appointment_time,
    appointment.status
FROM appointments appointment
JOIN services service ON appointment.service_id = service.id_service
JOIN providers proveedor ON appointment.provider_id = proveedor.id_provider
WHERE appointment.user_id = ?";

// Filtros opcionales
$params = [$user_id];
$types = "i";

if (!empty($_GET['filtro_status'])) {
    $sql_citas .= " AND appointment.status = ?";
    $params[] = $_GET['filtro_status'];
    $types .= "s";
}

$sql_citas .= " ORDER BY appointment.appointment_date DESC, appointment.appointment_time DESC";

$stmt_citas = $conn->prepare($sql_citas);
$stmt_citas->bind_param($types, ...$params);
$stmt_citas->execute();
$citas = $stmt_citas->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citas - <?php echo $user_name; ?></title>
</head>

<body>
    <h1>Mis Citas - <?php echo $user_name; ?></h1>

    <div style="float: right;">
        <a href="logout.php">Cerrar Sesión</a>
    </div>

    <p>Bienvenido paciente, <strong><?php echo $user_name; ?></strong></p>
    <p>Documento: <?php echo $_SESSION['user_document']; ?></p>

    <?php if (isset($mensaje)): ?>
        <p style="color: <?php echo ($tipo_mensaje == 'success') ? 'green' : 'red'; ?>;">
            <?php echo $mensaje; ?>
        </p>
    <?php endif; ?>

    <hr>

    <h2>Mis Citas Agendadas</h2>

    <!-- Filtros -->
    <form method="GET">
        <label>Filtrar por estado:</label>
        <select name="filtro_status">
            <option value="">Todas</option>
            <option value="scheduled" <?php echo (isset($_GET['filtro_status']) && $_GET['filtro_status'] == 'scheduled') ? 'selected' : ''; ?>>Agendadas</option>
            <option value="cancelled" <?php echo (isset($_GET['filtro_status']) && $_GET['filtro_status'] == 'cancelled') ? 'selected' : ''; ?>>Canceladas</option>
            <option value="completed" <?php echo (isset($_GET['filtro_status']) && $_GET['filtro_status'] == 'completed') ? 'selected' : ''; ?>>Completadas</option>
        </select>

        <button type="submit">Filtrar</button>
        <a href="mis-citas.php">Limpiar</a>
    </form>
    <br>

    <table border="1">
        <tr>
            <th>ID</th>
            <th>Servicio</th>
            <th>Duración</th>
            <th>Proveedor</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
        <?php if ($citas->num_rows > 0): ?>
            <?php while ($cita = $citas->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $cita['id_appointment']; ?></td>
                    <td><?php echo $cita['servicio']; ?></td>
                    <td><?php echo $cita['duration_minutes']; ?> min</td>
                    <td><?php echo $cita['proveedor']; ?></td>
                    <td><?php echo $cita['appointment_date']; ?></td>
                    <td><?php echo $cita['appointment_time']; ?></td>
                    <td><?php echo $cita['status']; ?></td>
                    <td>
                        <?php if ($cita['status'] == 'scheduled'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas cancelar esta cita?');">
                                <input type="hidden" name="accion" value="cancelar">
                                <input type="hidden" name="id_cita" value="<?php echo $cita['id_appointment']; ?>">
                                <button type="submit">Cancelar</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center;">No tienes citas agendadas</td>
            </tr>
        <?php endif; ?>
    </table>

    <hr>
    <p><a href="agendar-cita.php">Agendar nueva cita</a></p>
</body>

</html>