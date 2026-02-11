<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CREAR SERVICIO
    if (isset($_POST['accion']) && $_POST['accion'] == 'crear') {

        $name_service = $_POST['name_service'];
        $duration_minutes = $_POST['duration_minutes'];

        $sql = "INSERT INTO services (name_service, duration_minutes) VALUES (?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $name_service, $duration_minutes);
        if ($stmt->execute()) {
            $mensaje = "Servicio creado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear el servicio: " . $stmt->error;
            $tipo_mensaje = "error";
        }
    }
    if (isset($_POST['accion']) && $_POST['accion'] == 'editar') {
        $id_service = $_POST['id_service'];
        $name_service = $_POST['name_service'];
        $duration_minutes = $_POST['duration_minutes'];

        $sql = "UPDATE services SET name_service = ?, duration_minutes = ? WHERE id_service = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $name_service, $duration_minutes, $id_service);
        if ($stmt->execute()) {
            $mensaje = "Servicio editado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al editar el servicio: " . $stmt->error;
            $tipo_mensaje = "error";
        }
    }
    // ELIMINAR SERVICIO
    if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar') {
        $id_service = $_POST['id_service'];

        // VALIDAR SI TIENE CITAS ASIGNADAS AL SERVICIO
        $check_sql = "SELECT COUNT(*) as total FROM appointments WHERE service_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id_service);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['total'] > 0) {
            $mensaje = "Error: No se puede eliminar el servicio porque tiene {$row['total']} citas asignada(s)";
            $tipo_mensaje = "error";
        } else {
            $delete_sql = "DELETE FROM services WHERE id_service = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $id_service);

            if ($delete_stmt->execute()) {
                $mensaje = "Servicio eliminado correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar el servicio: " . $delete_stmt->error;
                $tipo_mensaje = "error";
            }
        }
    }
}
// Si estamos editando, obtener datos del servicio
$service_editar = null;
if (isset($_GET['editar'])) {
    $id_service = $_GET['editar'];
    $sql = "SELECT * FROM services WHERE id_service = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_service);
    $stmt->execute();
    $result = $stmt->get_result();
    $service_editar = $result->fetch_assoc();
}
$services = $conn->query("SELECT * FROM services ORDER BY name_service ASC");
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Servicios</title>
</head>

<body>
    <h1>Gestión de Servicios</h1>
    <a href="index.php">← Volver a Citas</a>

    <?php if (isset($mensaje)): ?>
        <p style="color: <?php echo ($tipo_mensaje == 'success') ? 'green' : 'red'; ?>;">
            <?php echo $mensaje; ?>
        </p>
        
    <?php endif; ?> <!-- FORMULARIO DINÁMICO (CREAR O EDITAR) -->
    <h2><?php echo $service_editar ? 'Editar Servicio' : 'Nuevo Servicio'; ?></h2>

    <form method="POST">
        <input type="hidden" name="accion" value="<?php echo $service_editar ? 'editar' : 'crear'; ?>">

        <?php if ($service_editar): ?>
            <input type="hidden" name="id_service" value="<?php echo $service_editar['id_service']; ?>">
        <?php endif; ?>

        <label>Nombre del Servicio:</label>
        <input type="text" name="name_service" value="<?php echo $service_editar ? $service_editar['name_service'] : ''; ?>" required>
        <br><br>

        <label>Duración (minutos):</label>
        <input type="number" name="duration_minutes" value="<?php echo $service_editar ? $service_editar['duration_minutes'] : ''; ?>" min="15" required>
        <br><br>

        <button type="submit"><?php echo $service_editar ? 'Actualizar' : 'Crear'; ?></button>

        <?php if ($service_editar): ?>
            <a href="services.php">Cancelar</a>
        <?php endif; ?>
    </form>

    <hr>

    <!-- TABLA DE SERVICIOS -->
    <h2>Lista de Servicios</h2>

    <table border="1">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Duración (min)</th>
            <th>Acciones</th>
        </tr>
        <?php while ($service = $services->fetch_assoc()): ?>
            <tr>
                <td><?php echo $service['id_service']; ?></td>
                <td><?php echo $service['name_service']; ?></td>
                <td><?php echo $service['duration_minutes']; ?></td>
                <td>
                    <a href="services.php?editar=<?php echo $service['id_service']; ?>">Editar</a>

                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este servicio?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id_service" value="<?php echo $service['id_service']; ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>
