<?php
require 'config.php';
        // Pregunta: ¿El usuario envió el formulario (POST) o solo está viendo la página (GET)?
        // Primera vez que abres providers.php → GET (no entra aquí)
        // Cuando presionas un botón "Crear" o "Editar" → POST (SÍ entra aquí)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && $_POST['accion'] == 'crear') {
        $name = $_POST['name']; //obtener el valor del input name="name" del HTML
        $email = $_POST['email'];
        $description = $_POST['description'];
        $name = $_POST['name'];

        // HTML envía:
        // <input type="text" name="name" value="Dr. Juan">
        //                    ↑                    ↑
        //              Esto es la clave      Esto es el valor
        //
        // PHP recibe: $_POST['name'] = "Dr. Juan"
        // Guardamos en variable: $name = "Dr. Juan"

        $sql = "INSERT INTO providers (name, email, description) VALUES (?,?,?)";
        //Los ? son contenedores vacíos que bind_param llenará de forma SEGURA
        // MySQL trata cualquier valor como TEXTO, no como código SQL
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $description);

        if ($stmt->execute()) {
            $mensaje = "Proveedor creado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear el proveedor: " . $stmt->error;
            $tipo_mensaje = "error";
        }
    }

    //EDITAR PROVEEDOR:
    // Pregunta 1: ¿Existe la variable $_POST['accion']? → isset() verifica esto
    // Pregunta 2: ¿Su valor es 'crear'? → $_POST['accion'] == 'crear'

    // ¿De dónde viene 'accion'?
    // Del formulario HTML:
    // <input type="hidden" name="accion" value="crear">
    //                      ↑             ↑
    //                   name="accion"  value="crear"

    // PHP recibe: $_POST['accion'] = 'crear'

    // ¿Por qué necesitamos esto?
    // Porque en la misma página tienes 3 botones POST:
    // - Botón "Crear proveedor" → accion='crear'
    // - Botón "Editar proveedor" → accion='editar'  
    // - Botón "Eliminar proveedor" → accion='eliminar'
    // Entonces PHP pregunta: "¿Cuál botón presionó el usuario?"

    if (isset($_POST['accion']) && $_POST['accion'] == 'editar') {
        $id = $_POST['id_provider'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $description = $_POST['description'];

        $sql = "UPDATE providers SET name = ?, email = ?, description = ? WHERE id_provider = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $email, $description, $id);


        if ($stmt->execute()) {
            $mensaje = "Proveedor editado correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al editar el proveedor:" . $stmt->error;
            $tipo_mensaje = "error";
        }
    }

    //ELIMINAR PROVEEDOR
    if (isset($_POST['accion']) && $_POST['accion'] == 'eliminar') {
        $id = $_POST['id_provider'];

        //VALIDAR SIS TIENE CITAS ASIGNADAS AL PROVEEDOR
        $check_sql = "SELECT COUNT(*) as total FROM appointments WHERE provider_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result(); //EXPLICACION
        $row = $result->fetch_assoc(); // EXPLICACION

        if ($row['total'] > 0) {
            $mensaje = "Error: No se puede eliminar el proveedor porque tiene {$row['total']}citas asignada(s)";
            $tipo_mensaje = "error";
        } else {
            $delete_sql = "DELETE FROM providers WHERE id_provider = ?"; //EXPLICACION: porque = ? y no el campo del html
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $id);

            if ($delete_stmt->execute()) {
                $mensaje = "Proveedor eliminado correctamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar el proveedor: " . $delete_stmt->error;
                $tipo_mensaje = "error";
            }
        }
    }
}

//Si estamos editando, obtener datos del proveedor
$provider_editar = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $sql = "SELECT * FROM providers WHERE id_provider = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $provider_editar = $stmt->get_result()->fetch_assoc(); //EXPLICACION fetch_assoc()
}

// OBTENER LISTA DE PROVEEDORES
$providers = $conn->query("SELECT * FROM providers ORDER BY name ASC");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores</title>
</head>

<body>
    <h1>Gestión de Proveedores</h1>
    <a href="index.php">← Volver a Citas</a>

    <?php if (isset($mensaje)): ?>
        <p style="color: <?php echo ($tipo_mensaje == 'success') ? 'green' : 'red'; ?>;">
            <?php echo $mensaje; ?>
        </p>
    <?php endif; ?>

    <hr>

    <h2><?php echo $provider_editar ? 'Editar Proveedor' : 'Nuevo Proveedor'; ?></h2>

    <form method="POST">
        <input type="hidden" name="accion" value="<?php echo $provider_editar ? 'editar' : 'crear'; ?>">

        <?php if ($provider_editar): ?>
            <input type="hidden" name="id_provider" value="<?php echo $provider_editar['id_provider']; ?>">
        <?php endif; ?>

        <label>Nombre:</label>
        <input type="text" name="name" value="<?php echo $provider_editar ? $provider_editar['name'] : ''; ?>" required>
        <br><br>

        <label>Email:</label>
        <input type="email" name="email" value="<?php echo $provider_editar ? $provider_editar['email'] : ''; ?>" required>
        <br><br>

        <label>Descripción:</label>
        <textarea name="description" required><?php echo $provider_editar ? $provider_editar['description'] : ''; ?></textarea>
        <br><br>

        <button type="submit"><?php echo $provider_editar ? 'Actualizar' : 'Crear'; ?></button>

        <?php if ($provider_editar): ?>
            <a href="providers.php">Cancelar</a>
        <?php endif; ?>
    </form>

    <hr>

    <h2>Lista de Proveedores</h2>

    <table border="1">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Descripción</th>
            <th>Acciones</th>
        </tr>
        <?php while ($provider = $providers->fetch_assoc()): ?>
            <tr>
                <td><?php echo $provider['id_provider']; ?></td>
                <td><?php echo $provider['name']; ?></td>
                <td><?php echo $provider['email']; ?></td>
                <td><?php echo $provider['description']; ?></td>
                <td>
                    <a href="providers.php?editar=<?php echo $provider['id_provider']; ?>">Editar</a>

                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este proveedor?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id_provider" value="<?php echo $provider['id_provider']; ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>

</html>