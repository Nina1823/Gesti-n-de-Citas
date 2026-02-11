<?php
require('config.php');
// En users.php, antes del HTML
$document_types
    = [
        'CC' => 'Cédula de Ciudadanía',
        'CE' => 'Cédula de Extranjería',
        'TI' => 'Tarjeta de Identidad',
        'PASAPORTE' => 'Pasaporte',
        'NIT' => 'NIT'
    ];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CREAR USUARIO
    if (isset($_POST['accion']) && $_POST['accion'] == 'crear') {
        $document_type = $_POST['document_type'];
        $document_number = $_POST['document_number'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];

        $sql = "INSERT INTO users (document_type, document_number, name, email, phone) VALUES (?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $document_type, $document_number, $name, $email, $phone);
        if ($stmt->execute()) {
            $mensaje = "Usuario creado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear el usuario: " . $stmt->error;
            $tipo_mensaje = "error";
        }
    }
    //EDITAR USUARIO
    if (isset($_POST['accion']) && $_POST['accion'] == 'editar') {
        $id_user = $_POST['id_user'];
        $document_type = $_POST['document_type'];
        $document_number = $_POST['document_number'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];

        $sql = "UPDATE users SET document_type = ?, document_number = ?, name = ?, email = ?, phone = ? WHERE id_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $document_type, $document_number, $name, $email, $phone, $id_user);
        if ($stmt->execute()) {
            $mensaje = "Usuario editado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al editar el usuario: " . $stmt->error;
            $tipo_mensaje = "error";
        }
    }
    // ELIMINAR USUARIO
    if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar') {
        $id_user = $_POST['id_user'];

        // VALIDAR SI TIENE CITAS
        $check_sql = "SELECT COUNT(*) as total FROM appointments WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id_user);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['total'] > 0) {
            $mensaje = "Error: No se puede eliminar el usuario porque tiene {$row['total']} cita(s) agendada(s)";
            $tipo_mensaje = "error";
        } else {
            $delete_sql = "DELETE FROM users WHERE id_user = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $id_user);

            if ($delete_stmt->execute()) {
                $mensaje = "Usuario eliminado correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar el usuario: " . $delete_stmt->error;
                $tipo_mensaje = "error";
            }
        }
    }
}

$user_editar = null;
// Si estamos editando, obtener datos del usuario
if (isset($_GET['editar'])) {
    $id_user = $_GET['editar'];
    $sql = "SELECT * FROM users WHERE id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_editar = $result->fetch_assoc();
}
$users = $conn->query("SELECT * FROM users ORDER BY name ASC");


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
</head>

<body>
    <h1>Gestión de Pacientes/Usuarios</h1>
    <a href="index.php">← Volver a Citas</a>

    <?php if (isset($mensaje)): ?>
        <p style="color: <?php echo ($tipo_mensaje == 'success') ? 'green' : 'red'; ?>;">
            <?php echo $mensaje; ?>
        </p>
    <?php endif; ?>

    <hr>

    <h2><?php echo $user_editar ? 'Editar Usuario' : 'Nuevo Usuario'; ?></h2>

    <form method="POST">
        <input type="hidden" name="accion" value="<?php echo $user_editar ? 'editar' : 'crear'; ?>">

        <?php if ($user_editar): ?>
            <input type="hidden" name="id_user" value="<?php echo $user_editar['id_user']; ?>">
        <?php endif; ?>

        <label>Tipo de Documento:</label>
        <select name="document_type" required>
            <option value="">Seleccione...</option>
            <?php foreach ($document_types as $value => $label): ?>
                <option value="<?php echo $value; ?>"
                    <?php echo ($user_editar && $user_editar['document_type'] == $value) ? 'selected' : ''; ?>>
                    <?php echo $label; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <label>Número de Documento:</label>
        <input type="text" name="document_number" value="<?php echo $user_editar ? $user_editar['document_number'] : ''; ?>" required>
        <br><br>

        <label>Nombre:</label>
        <input type="text" name="name" value="<?php echo $user_editar ? $user_editar['name'] : ''; ?>" required>
        <br><br>

        <label>Email:</label>
        <input type="email" name="email" value="<?php echo $user_editar ? $user_editar['email'] : ''; ?>" required>
        <br><br>

        <label>Teléfono:</label>
        <input type="tel" name="phone" value="<?php echo $user_editar ? $user_editar['phone'] : ''; ?>">
        <br><br>

        <button type="submit"><?php echo $user_editar ? 'Actualizar' : 'Crear'; ?></button>

        <?php if ($user_editar): ?>
            <a href="users.php">Cancelar</a>
        <?php endif; ?>
    </form>

    <hr>

    <h2>Listado de Pacientes</h2>

    <table border="1">
        <tr>
            <th>ID</th>
            <th>Tipo Documento</th>
            <th>Número Documento</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Acciones</th>
        </tr>
        <?php while ($user = $users->fetch_assoc()): ?>
            <tr>
                <td><?php echo $user['id_user']; ?></td>
                <td><?php echo $user['document_type']; ?></td>
                <td><?php echo $user['document_number']; ?></td>
                <td><?php echo $user['name']; ?></td>
                <td><?php echo $user['email']; ?></td>
                <td><?php echo $user['phone']; ?></td>
                <td>
                    <a href="users.php?editar=<?php echo $user['id_user']; ?>">Editar</a>

                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id_user" value="<?php echo $user['id_user']; ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>