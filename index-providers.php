<?php
session_start();

// PROTECCIÓN: Si no está logueado, redirigir a login
if (!isset($_SESSION['provider_id'])) {
    header("Location: login.php");
    exit;
}

require 'config.php';

$provider_id = $_SESSION['provider_id'];
$provider_name = $_SESSION['provider_name'];

// PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CANCELAR CITA
    if (isset($_POST['accion']) && $_POST['accion'] == 'cancelar') {
        $id_cita = $_POST['id_cita'];

        // Verificar que la cita pertenece al provider logueado
        $check_sql = "SELECT provider_id FROM appointments WHERE id_appointment = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id_cita);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $cita_data = $check_result->fetch_assoc();

        if ($cita_data['provider_id'] == $provider_id) {
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

    // COMPLETAR CITA
    if (isset($_POST['accion']) && $_POST['accion'] == 'completar') {
        $id_cita = $_POST['id_cita'];

        // Verificar que la cita pertenece al provider
        $check_sql = "SELECT provider_id FROM appointments WHERE id_appointment = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id_cita);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $cita_data = $check_result->fetch_assoc();

        if ($cita_data['provider_id'] == $provider_id) {
            $complete_sql = "UPDATE appointments SET status = 'completed' WHERE id_appointment = ?";
            $complete_stmt = $conn->prepare($complete_sql);
            $complete_stmt->bind_param("i", $id_cita);

            if ($complete_stmt->execute()) {
                $mensaje = "Cita marcada como completada";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al completar";
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "No tienes permiso para modificar esta cita";
            $tipo_mensaje = "error";
        }
    }
}

// CONSULTA DE CITAS - SOLO DEL PROVIDER LOGUEADO
$sql_citas = "SELECT 
    appointment.id_appointment,
    usuario.name as usuario,
    service.name_service as servicio,
    appointment.appointment_date,
    appointment.appointment_time,
    appointment.status
FROM appointments appointment
JOIN users usuario ON appointment.user_id = usuario.id_user
JOIN services service ON appointment.service_id = service.id_service
WHERE appointment.provider_id = ?";

// Agregar filtros dinámicamente
$params = [$provider_id];
$types = "i";

if (!empty($_GET['filtro_fecha'])) {
    $sql_citas .= " AND appointment.appointment_date = ?";
    $params[] = $_GET['filtro_fecha'];
    $types .= "s";
}

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
    <title>Mis Citas - <?php echo $provider_name; ?></title>
</head>

<body>
    <h1>Mis Citas - <?php echo $provider_name; ?></h1>

    <div style="float: right;">
        <a href="logout.php">Cerrar Sesión</a>
    </div>

    <p>Bienvenido, <strong><?php echo $provider_name; ?></strong></p>

    <?php if (isset($mensaje)): ?>
        <p style="color: <?php echo ($tipo_mensaje == 'success') ? 'green' : 'red'; ?>;">
            <?php echo $mensaje; ?>
        </p>
    <?php endif; ?>

    <hr>

    <h2>Mis Citas Agendadas</h2>

    <form method="GET">
        <label>Filtrar por fecha:</label>
        <input type="date" name="filtro_fecha" value="<?php echo isset($_GET['filtro_fecha']) ? $_GET['filtro_fecha'] : ''; ?>">

        <label>Filtrar por estado:</label>
        <select name="filtro_status">
            <option value="">Todos</option>
            <option value="scheduled" <?php echo (isset($_GET['filtro_status']) && $_GET['filtro_status'] == 'scheduled') ? 'selected' : ''; ?>>Agendadas</option>
            <option value="cancelled" <?php echo (isset($_GET['filtro_status']) && $_GET['filtro_status'] == 'cancelled') ? 'selected' : ''; ?>>Canceladas</option>
            <option value="completed" <?php echo (isset($_GET['filtro_status']) && $_GET['filtro_status'] == 'completed') ? 'selected' : ''; ?>>Completadas</option>
        </select>

        <button type="submit">Filtrar</button>
        <a href="index-providers.php">Limpiar filtros</a>
    </form>
    <br>

    <table border="1">
        <tr>
            <th>ID</th>
            <th>Paciente</th>
            <th>Servicio</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
        <?php while ($cita = $citas->fetch_assoc()): ?>
            <tr>
                <td><?php echo $cita['id_appointment']; ?></td>
                <td><?php echo $cita['usuario']; ?></td>
                <td><?php echo $cita['servicio']; ?></td>
                <td><?php echo $cita['appointment_date']; ?></td>
                <td><?php echo $cita['appointment_time']; ?></td>
                <td><?php echo $cita['status']; ?></td>
                <td>
                    <?php if ($cita['status'] == 'scheduled'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="accion" value="completar">
                            <input type="hidden" name="id_cita" value="<?php echo $cita['id_appointment']; ?>">
                            <button type="submit">Completar</button>
                        </form>

                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="accion" value="cancelar">
                            <input type="hidden" name="id_cita" value="<?php echo $cita['id_appointment']; ?>">
                            <button type="submit">Cancelar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>