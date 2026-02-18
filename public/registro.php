<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$mensajeExito = '';
$mensajeError = '';

if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'paciente') {
        header('Location: paciente.php');
        exit;
    }
    if ($_SESSION['rol'] === 'admin') {
        header('Location: admin.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni       = trim($_POST['dni'] ?? '');
    $nombres   = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');

    if ($dni === '' || $nombres === '' || $apellidos === '' || $correo === '' || $password === '' || $password2 === '') {
        $mensajeError = 'Por favor, complete todos los campos obligatorios.';
    } elseif ($password !== $password2) {
        $mensajeError = 'Las contraseñas no coinciden.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensajeError = 'El correo electrónico no tiene un formato válido.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_paciente FROM pacientes WHERE dni = ?");
            $stmt->execute([$dni]);
            $existe = $stmt->fetch();

            if ($existe) {
                $mensajeError = 'Ya existe un paciente registrado con ese DNI.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO pacientes (dni, nombres, apellidos, telefono, correo, password_hash)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$dni, $nombres, $apellidos, $telefono, $correo, $password]);

                $mensajeExito = 'Registro exitoso. Ahora puedes iniciar sesión con tu DNI y contraseña.';
                $dni = $nombres = $apellidos = $telefono = $correo = '';
            }
        } catch (Exception $e) {
            $mensajeError = 'Ocurrió un error al registrar el paciente. Intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Paciente - Sistema de Citas</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <div class="app-shell">
    <div class="app-container">
      <header class="app-header">
        <div>
          <div class="app-title">
            <span class="logo-dot"></span>
            <span>Registro de Paciente</span>
          </div>
          <p class="app-subtitle">
            Crea tu cuenta para poder reservar y consultar citas en el hospital.
          </p>
        </div>

        <a href="index.php" class="link-logout">
          Volver al inicio
        </a>
      </header>

      <main>
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Formulario de registro</h2>
            <p class="card-subtitle">
              Completa tus datos personales. Los campos marcados son obligatorios.
            </p>
          </div>

          <?php if ($mensajeError !== ''): ?>
            <p class="small-text" style="color:#e53935; margin-top:4px; margin-bottom:10px;">
              <?= htmlspecialchars($mensajeError) ?>
            </p>
          <?php endif; ?>

          <?php if ($mensajeExito !== ''): ?>
            <p class="small-text" style="color:#2E7D32; margin-top:4px; margin-bottom:10px;">
              <?= htmlspecialchars($mensajeExito) ?>
            </p>
          <?php endif; ?>

          <form method="post" action="registro.php" class="mt-2">
            <div class="form-row">
              <label for="dni">DNI</label>
              <input
                type="text"
                id="dni"
                name="dni"
                placeholder="Ingresa tu DNI"
                required
                value="<?= isset($dni) ? htmlspecialchars($dni) : '' ?>"
              >

              <label for="nombres">Nombres</label>
              <input
                type="text"
                id="nombres"
                name="nombres"
                placeholder="Ingresa tus nombres"
                required
                value="<?= isset($nombres) ? htmlspecialchars($nombres) : '' ?>"
              >

              <label for="apellidos">Apellidos</label>
              <input
                type="text"
                id="apellidos"
                name="apellidos"
                placeholder="Ingresa tus apellidos"
                required
                value="<?= isset($apellidos) ? htmlspecialchars($apellidos) : '' ?>"
              >

              <label for="telefono">Teléfono</label>
              <input
                type="text"
                id="telefono"
                name="telefono"
                placeholder="Número de contacto"
                value="<?= isset($telefono) ? htmlspecialchars($telefono) : '' ?>"
              >

              <label for="correo">Correo electrónico</label>
              <input
                type="text"
                id="correo"
                name="correo"
                placeholder="ejemplo@correo.com"
                required
                value="<?= isset($correo) ? htmlspecialchars($correo) : '' ?>"
              >

              <label for="password">Contraseña</label>
              <input
                type="password"
                id="password"
                name="password"
                placeholder="Crea una contraseña"
                required
              >

              <label for="password2">Confirmar contraseña</label>
              <input
                type="password"
                id="password2"
                name="password2"
                placeholder="Repite la contraseña"
                required
              >
            </div>

            <div class="mt-3">
              <button type="submit" class="btn btn-primary">Registrar paciente</button>
            </div>
          </form>

          <p class="small-text mt-3">
            ¿Ya tienes una cuenta?
            <a href="index.php">Volver al inicio de sesión</a>
          </p>
        </div>
      </main>
    </div>
  </div>
</body>
</html>
