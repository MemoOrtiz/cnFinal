<?php
// --- 1. Conexion ---
$serverName = "tcp:servermemo.database.windows.net,1433";
$connectionInfo = [
    "UID" => "memo",
    "pwd" => "u22050370.",
    "Database" => "bd",
    "LoginTimeout" => 30,
    "Encrypt" => 1,
    "TrustServerCertificate" => 0
];

// --- 2. Intentar conectar a MySQL ---
$conexion = sqlsrv_connect($serverName, $connectionInfo);
if ($conexion === false) {
    die("Error de conexión: " . print_r(sqlsrv_errors(), true));
}

$ok = true;
$error = "";
if ($conexion === false) {
    $ok = false;
    // Recopilar mensajes de error de SQL Server
    $errs = sqlsrv_errors();
    ...
}

// --- 4. Manejar eliminación del último registro ---
$tsql_delete = "DELETE TOP (1) FROM personas ORDER BY id DESC";
$stmtDel = sqlsrv_query($conexion, $tsql_delete);

// --- 5. Manejar inserción de nuevo registro ---
$tsql_insert = "INSERT INTO personas (nombre, estado_civil) VALUES (?, ?)";
$params = [ $nombre, $estado ];
$stmtIns = sqlsrv_query($conexion, $tsql_insert, $params);


// --- 6. Obtener registros si hay conexión ---
$tsql_select = "SELECT id, nombre, estado_civil FROM personas ORDER BY id ASC";
$stmtSel = sqlsrv_query($conexion, $tsql_select);
while ($row = sqlsrv_fetch_array($stmtSel, SQLSRV_FETCH_ASSOC)) {
    $registros[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Personas</title>
  <style>
    body { font-family: sans-serif; max-width: 600px; margin: 2rem auto; }
    form, table { width: 100%; margin-bottom: 2rem; }
    input, select, button { padding: .5rem; font-size: 1rem; width: 100%; margin-top: .5rem; }
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: .5rem; border: 1px solid #ccc; }
    .status { padding: .5rem; margin-bottom: 1rem; }
    .success { background-color: #e6ffed; color: #036a0f; }
    .error   { background-color: #ffe6e6; color: #a00; }
    .btn-delete { background: #c00; color: #fff; border: none; cursor: pointer; }
  </style>
</head>
<body>
  <h1>Registro de Personas</h1>

  <!-- Estado de conexión -->
  <div class="status <?= $ok ? 'success' : 'error' ?>">
    <?= $ok
        ? '✔ Conexión exitosa a la BD.'
        : '✖ No se pudo conectar a la BD: ' . htmlspecialchars($error) 
    ?>
  </div>

  <!-- Botón para eliminar el último registro (solo si está conectado) -->
  <?php if ($ok): ?>
  <form method="post" onsubmit="return confirm('¿Eliminar el último registro?');">
    <button type="submit" name="delete_last" class="btn-delete">Eliminar último registro</button>
  </form>
  <?php endif; ?>

  <!-- Formulario de inserción (siempre visible, pero solo inserta si hay conexión) -->
  <form method="post">
    <label>Nombre:
      <input type="text" name="nombre" required <?= $ok?'':'disabled' ?>>
    </label>
    <label>Estado Civil:
      <select name="estado_civil" required <?= $ok?'':'disabled' ?>>
        <option value="">-- Selecciona --</option>
        <option value="Soltero">Soltero</option>
        <option value="Casado">Casado</option>
        <option value="Divorciado">Divorciado</option>
        <option value="Viudo">Viudo</option>
      </select>
    </label>
    <button type="submit" <?= $ok?'':'disabled' ?>>Guardar</button>
  </form>

  <!-- Tabla dinámica (solo si hay conexión) -->
  <?php if ($ok): ?>
    <h2>Listado de Personas</h2>
    <table>
      <thead>
        <tr><th>ID</th><th>Nombre</th><th>Estado Civil</th></tr>
      </thead>
      <tbody>
        <?php if ($res && $res->num_rows > 0): ?>
          <?php while($row = $res->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= $row['estado_civil'] ?></td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="3">No hay registros.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>

