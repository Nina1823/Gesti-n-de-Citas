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
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-calendar-check"></i> Sistema de Citas
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i> Volver a Citas
            </a>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="container">

        <!-- Título -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-5">
                    <i class="bi bi-people-fill text-primary"></i> Gestión de Proveedores
                </h1>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if (isset($mensaje)): ?>
            <div class="alert alert-<?php echo ($tipo_mensaje == 'success') ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo ($tipo_mensaje == 'success') ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="row mb-4">
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-<?php echo $provider_editar ? 'pencil-square' : 'plus-circle'; ?>"></i>
                            <?php echo $provider_editar ? 'Editar Proveedor' : 'Nuevo Proveedor'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="accion" value="<?php echo $provider_editar ? 'editar' : 'crear'; ?>">

                            <?php if ($provider_editar): ?>
                                <input type="hidden" name="id_provider" value="<?php echo $provider_editar['id_provider']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    <i class="bi bi-person"></i> Nombre
                                </label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?php echo $provider_editar ? $provider_editar['name'] : ''; ?>"
                                    placeholder="Ej: Dr. Juan Pérez" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope"></i> Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo $provider_editar ? $provider_editar['email'] : ''; ?>"
                                    placeholder="ejemplo@correo.com" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i class="bi bi-card-text"></i> Descripción
                                </label>
                                <textarea class="form-control" id="description" name="description"
                                    rows="3" placeholder="Especialidad y experiencia..." required><?php echo $provider_editar ? $provider_editar['description'] : ''; ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-<?php echo $provider_editar ? 'warning' : 'primary'; ?>">
                                    <i class="bi bi-<?php echo $provider_editar ? 'save' : 'plus-circle'; ?>"></i>
                                    <?php echo $provider_editar ? 'Actualizar' : 'Crear'; ?> Proveedor
                                </button>

                                <?php if ($provider_editar): ?>
                                    <a href="providers.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Cancelar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabla de proveedores -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul"></i> Lista de Proveedores
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Descripción</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($provider = $providers->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $provider['id_provider']; ?></td>
                                            <td>
                                                <i class="bi bi-person-badge text-primary"></i>
                                                <strong><?php echo $provider['name']; ?></strong>
                                            </td>
                                            <td>
                                                <i class="bi bi-envelope text-muted"></i>
                                                <?php echo $provider['email']; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo substr($provider['description'], 0, 50) . '...'; ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="providers.php?editar=<?php echo $provider['id_provider']; ?>"
                                                        class="btn btn-outline-primary" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>

                                                    <form method="POST" style="display:inline;"
                                                        onsubmit="return confirm('¿Seguro que deseas eliminar a <?php echo $provider['name']; ?>?');">
                                                        <input type="hidden" name="accion" value="eliminar">
                                                        <input type="hidden" name="id_provider" value="<?php echo $provider['id_provider']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>