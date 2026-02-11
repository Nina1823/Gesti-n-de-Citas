<?php
    require 'config.php';

    $providers = $conn->query("SELECT id_provider, name FROM providers");
    $services = $conn->query("SELECT id_service, name_service FROM services");
    $users = $conn->query("SELECT id_user, name, document_type, document_number FROM users");

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // CANCELAR CITA
        if (isset($_POST['accion']) && $_POST['accion'] == 'cancelar') {
            $id_cita = $_POST['id_cita'];
            
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
        }
        
        // AGENDAR NUEVA CITA
        if (!isset($_POST['id_cita'])) {
            $user_id = $_POST['user_id'];
            $service_id = $_POST['service_id'];
            $provider_id = $_POST['provider_id'];
            $appointment_date = $_POST['appointment_date'];
            $appointment_time = $_POST['appointment_time'];
            
            // VALIDAR DUPLICADOS
            $check_sql = "SELECT id_appointment FROM appointments 
                          WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $appointment_date, $appointment_time);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $mensaje = "Error: Ya existe una cita agendada en ese horario";
                $tipo_mensaje = "error";
            } else {
                // No hay duplicados, insertar
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
    }

    // CONSULTA DE CITAS CON FILTROS
    $sql_citas = "SELECT 
        appointment.id_appointment,
        usuario.name as usuario,
        service.name_service as servicio,
        proveedor.name as proveedor,
        appointment.appointment_date,
        appointment.appointment_time,
        appointment.status
    FROM appointments appointment
    JOIN users usuario ON appointment.user_id = usuario.id_user
    JOIN services service ON appointment.service_id = service.id_service
    JOIN providers proveedor ON appointment.provider_id = proveedor.id_provider
    WHERE 1=1";

    // Agregar filtros dinámicamente
    if (!empty($_GET['filtro_fecha'])) {
        $sql_citas .= " AND appointment.appointment_date = '" . $conn->real_escape_string($_GET['filtro_fecha']) . "'";
    }

    if (!empty($_GET['filtro_status'])) {
        $sql_citas .= " AND appointment.status = '" . $conn->real_escape_string($_GET['filtro_status']) . "'";
    }

    $sql_citas .= " ORDER BY appointment.appointment_date, appointment.appointment_time";

    $citas = $conn->query($sql_citas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de citas</title>
</head>
<body>
    <h1>Sistema de agendamiento de citas</h1>
    
    <?php if(isset($mensaje)): ?>
        <p style="color: <?php echo (isset($tipo_mensaje) && $tipo_mensaje == 'success') ? 'green' : 'red'; ?>;">
            <?php echo $mensaje; ?>
        </p>
    <?php endif; ?>
    
    <h2>Agendar una cita nueva</h2>
    <form method="POST">
        <label>Paciente:</label>
        <select name="user_id" required>
            <option value="">Seleccione un paciente</option>
            <?php while($user = $users->fetch_assoc()): ?>
                <option value="<?php echo $user['id_user']; ?>">
                    <?php echo $user['name'] . " - " . $user['document_type'] . ": " . $user['document_number']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <br><br>
        
        <label>Servicio:</label>
        <select name="service_id" required>
            <option value="">Seleccione...</option>
            <?php while($service = $services->fetch_assoc()): ?>
                <option value="<?php echo $service['id_service']; ?>">
                    <?php echo $service['name_service']; ?>
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
        <input type="date" name="appointment_date" required>
        <br><br>
        
        <label>Hora:</label>
        <input type="time" name="appointment_time" required>
        <br><br>
        
        <button type="submit">Agendar Cita</button>
    </form>
    
    <hr>
    
    <h2>Citas Agendadas</h2>

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
        <a href="index.php">Limpiar filtros</a>
    </form>
    <br>

    <table border="1">
        <tr>
            <th>ID</th>
            <th>Paciente</th>
            <th>Servicio</th>
            <th>Proveedor</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
        <?php while($cita = $citas->fetch_assoc()): ?>
        <tr>
            <td><?php echo $cita['id_appointment']; ?></td>
            <td><?php echo $cita['usuario']; ?></td>
            <td><?php echo $cita['servicio']; ?></td>
            <td><?php echo $cita['proveedor']; ?></td>
            <td><?php echo $cita['appointment_date']; ?></td>
            <td><?php echo $cita['appointment_time']; ?></td>
            <td><?php echo $cita['status']; ?></td>
            <td>
                <?php if ($cita['status'] == 'scheduled'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="accion" value="cancelar">
                        <input type="hidden" name="id_cita" value="<?php echo $cita['id_appointment']; ?>">
                        <button type="submit">Cancelar</button>
                    </form>
                    <a href="editar.php?id=<?php echo $cita['id_appointment']; ?>">Editar</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>