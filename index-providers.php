<?php
session_start();
require 'config.php';

// PROTECCIÓN: Si no está logueado, redirigir a login
if (!isset($_SESSION['provider_id'])) {
    header("Location: login.php");
    exit;
}

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

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-calendar-check"></i> Sistema de Citas
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle"></i> Dr. <?php echo $provider_name; ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container mt-4">

        <!-- Encabezado de bienvenida -->
        <div class="row mb-4">
            <div class="col">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h1 class="display-6 mb-0">
                            <i class="bi bi-calendar2-week text-primary"></i> Mis Citas
                        </h1>
                        <p class="text-muted mb-0">
                            Bienvenido Dr. <strong><?php echo $provider_name; ?></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if (isset($mensaje)): ?>
            <div class="alert alert-<?php echo ($tipo_mensaje == 'success') ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo ($tipo_mensaje == 'success') ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?>"></i>
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-funnel"></i> Filtros
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="filtro_fecha" class="form-label">
                            <i class="bi bi-calendar3"></i> Filtrar por fecha
                        </label>
                        <input type="date" class="form-control" id="filtro_fecha" name="filtro_fecha"
                            value="<?php echo isset($_GET['filtro_fecha']) ? $_GET['filtro_fecha'] : ''; ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="filtro_status" class="form-label">
                            <i class="bi bi-tag"></i> Filtrar por estado
                        </label>
                        <select class="form-select" id="filtro_status" name="filtro_status">
                            <option value="">Todos</option>
                            <option value="scheduled" <?php echo (isset($_GET['filtro_status']) && $_GET['filtro_status'] == 'scheduled') ? 'selected' : ''; ?>>
                                Agendadas
                            </option>
                            <option value="cancelled" <?php echo (isset($_GET['filtro_status']) && $_GET['filtro_status'] == 'cancelled') ? 'selected' : ''; ?>>
                                Canceladas
                            </option>
                            <option value="completed" <?php echo (isset($_GET['filtro_status']) && $_GET['filtro_status'] == 'completed') ? 'selected' : ''; ?>>
                                Completadas
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="index-providers.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de citas -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="bi bi-list-check"></i> Citas Agendadas
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center">ID</th>
                                <th>Paciente</th>
                                <th>Servicio</th>
                                <th class="text-center">Fecha</th>
                                <th class="text-center">Hora</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($cita = $citas->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">#<?php echo $cita['id_appointment']; ?></span>
                                    </td>
                                    <td>
                                        <i class="bi bi-person-fill text-primary"></i>
                                        <strong><?php echo $cita['usuario']; ?></strong>
                                    </td>
                                    <td>
                                        <i class="bi bi-gear-fill text-info"></i>
                                        <?php echo $cita['servicio']; ?>
                                    </td>
                                    <td class="text-center">
                                        <i class="bi bi-calendar-event text-muted"></i>
                                        <?php echo date('d/m/Y', strtotime($cita['appointment_date'])); ?>
                                    </td>
                                    <td class="text-center">
                                        <i class="bi bi-clock text-muted"></i>
                                        <?php echo date('h:i A', strtotime($cita['appointment_time'])); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $badge_class = '';
                                        $icon = '';
                                        switch ($cita['status']) {
                                            case 'scheduled':
                                                $badge_class = 'bg-warning text-dark';
                                                $icon = 'clock-history';
                                                $texto = 'Agendada';
                                                break;
                                            case 'completed':
                                                $badge_class = 'bg-success';
                                                $icon = 'check-circle';
                                                $texto = 'Completada';
                                                break;
                                            case 'cancelled':
                                                $badge_class = 'bg-danger';
                                                $icon = 'x-circle';
                                                $texto = 'Cancelada';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <i class="bi bi-<?php echo $icon; ?>"></i>
                                            <?php echo $texto; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($cita['status'] == 'scheduled'): ?>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="accion" value="completar">
                                                    <input type="hidden" name="id_cita" value="<?php echo $cita['id_appointment']; ?>">
                                                    <button type="submit" class="btn btn-outline-success"
                                                        title="Marcar como completada"
                                                        onclick="return confirm('¿Marcar esta cita como completada?');">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                </form>

                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="accion" value="cancelar">
                                                    <input type="hidden" name="id_cita" value="<?php echo $cita['id_appointment']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger"
                                                        title="Cancelar cita"
                                                        onclick="return confirm('¿Cancelar esta cita?');">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>