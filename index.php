<?php
// ────────────────────────────────────
// index.php
// ────────────────────────────────────

// 1) Configuración de conexión a SQL Server
$serverName = "tcp:servermemo.database.windows.net,1433";
$connectionInfo = [
    "UID"                  => "memo",
    "PWD"                  => "u22050370.",
    "Database"             => "bd",
    "LoginTimeout"         => 30,
    "Encrypt"              => 1,
    "TrustServerCertificate"=> 0
];

// 2) Intentar conectar
$conexion = sqlsrv_connect($serverName, $connectionInfo);
if ($conexion === false) {
    // Si falla la conexión, mostramos el error en pantalla y detenemos la ejecución
    echo "<h2 style='color:red;'>No se pudo conectar a la base de datos:<br>"
         . nl2br(htmlspecialchars(print_r(sqlsrv_errors(), true)))
         . "</h2>";
    exit;
}

// 3) Manejar borrado / inserción antes de imprimir HTML
//    Si se envió "delete_last", borramos el último registro y redirigimos para evitar reenvío del formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_last'])) {
    $tsql_delete = "DELETE TOP (1) FROM personas ORDER BY id DESC";
    $stmtDel = sqlsrv_query($conexion, $tsql_delete);
    // (Opcional) podrías capturar errores:
    // if ($stmtDel === false) { /* manejar error con sqlsrv_errors() */ }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

//    Si se envió el formulario de inserción (nombre + estado_civil), hacemos INSERT y redirigimos.
if ($_SERVER['REQUEST_METHOD'] === 'POST' 
    && isset($_POST['nombre']) 
    && isset($_POST['estado_civil'])) 
{
    $nombre_raw = trim($_POST['nombre']);
    $estado_raw = trim($_POST['estado_civil']);

    if ($nombre_raw !== "" && $estado_raw !== "") {
        // Usamos sentencia parametrizada para evitar inyección
        $tsql_insert = "INSERT INTO personas (nombre, estado_civil) VALUES (?, ?)";
        $params = [ $nombre_raw, $estado_raw ];
        $stmtIns = sqlsrv_query($conexion, $tsql_insert, $params);
        // (Opcional) capturar errores: if ($stmtIns === false) { /* manejar sqlsrv_errors() */ }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 4) Obtener todos los registros (SELECT) para mostrarlos en la tabla
$personas = []; 
$tsql_select = "SELECT id, nombre, estado_civil FROM personas ORDER BY id ASC";
$stmtSel = sqlsrv_query($conexion, $tsql_select);

if ($stmtSel !== false) {
    while ($row = sqlsrv_fetch_array($stmtSel, SQLSRV_FETCH_ASSOC)) {
        $personas[] = $row;
    }
    sqlsrv_free_stmt($stmtSel);
} else {
    // Si ocurre un error en el SELECT, guardamos el mensaje para mostrarlo en pantalla
    $erroresSelect = sqlsrv_errors();
    $mensajeErrorSelect = "";
    if ($erroresSelect !== null) {
        foreach ($erroresSelect as $e) {
            $mensajeErrorSelect .= "[SQLSTATE {$e['SQLSTATE']}] Código {$e['code']} → {$e['message']}<br>";
        }
    }
}

// Cerramos la conexión (opcional)
sqlsrv_close($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Personas</title>
  <style>
    body { font-family: sans-serif; max-width: 600px; margin: 2rem auto; }
    h1 { text-align: center; margin-bottom: 1rem; }
    form, table { width: 100%; margin-bottom: 2rem; }
    input, select, button { padding: .5rem; font-size: 1rem; width: 100%; margin-top: .5rem; }
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: .5rem; border: 1px solid #ccc; text-align: left; }
    .status { padding: .5rem; margin-bottom: 1rem; }
    .success { background-color: #e6ffed; color: #036a0f; }
    .error   { background-color: #ffe6e6; color: #a00; }
    .btn-delete { background: #c00; color: #fff; border: none; cursor: pointer; }
    .btn-delete[disabled] { background: #f2a; cursor: not-allowed; }
  </style>
</head>
<body>
  <h1>Registro de Personas</h1>

  <!-- 5) Estado de la conexión (si llegamos aquí, la conexión fue exitosa) -->
  <div class="status success">
    ✔ Conexión exitosa a la base de datos.
  </div>

  <!-- 6) Botón para eliminar el último registro -->
  <form method="post" onsubmit="return confirm('¿Eliminar el último registro?');">
    <button type="submit" name="delete_last" class="btn-delete">
      🗑 Eliminar último registro
    </button>
  </form>

  <!-- 7) Formulario para insertar un nuevo registro -->
  <form method="post">
    <label>Nombre:
      <input type="text" name="nombre" placeholder="Ej. Juan Pérez" required>
    </label>
    <label>Estado Civil:
      <select name="estado_civil" required>
        <option value="">-- Selecciona --</option>
        <option value="Soltero">Soltero</option>
        <option value="Casado">Casado</option>
        <option value="Divorciado">Divorciado</option>
        <option value="Viudo">Viudo</option>
      </select>
    </label>
    <button type="submit">💾 Guardar</button>
  </form>

  <!-- 8) Mostrar posibles errores en SELECT -->
  <?php if (isset($mensajeErrorSelect) && $mensajeErrorSelect !== ""): ?>
    <div class="status error">
      <strong>✖ Error al leer los registros:</strong><br>
      <?= $mensajeErrorSelect ?>
    </div>
  <?php endif; ?>

  <!-- 9) Tabla dinámica con todos los registros -->
  <h2>Listado de Personas</h2>
  <table>
    <thead>
      <tr><th>ID</th><th>Nombre</th><th>Estado Civil</th></tr>
    </thead>
    <tbody>
      <?php if (count($personas) > 0): ?>
        <?php foreach ($personas as $fila): ?>
          <tr>
            <td><?= htmlspecialchars($fila['id']) ?></td>
            <td><?= htmlspecialchars($fila['nombre']) ?></td>
            <td><?= htmlspecialchars($fila['estado_civil']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="3" style="text-align: center;">No hay registros.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
