<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$errorPaciente = '';
$errorAdmin = '';

// Si ya está logueado, redirigimos directo
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

// Manejo de formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 🔹 Login PACIENTE SOLO con DNI
    if ($action === 'login_paciente') {
        $dni = trim($_POST['dni_paciente'] ?? '');

        if ($dni === '') {
            $errorPaciente = 'Debe ingresar su DNI.';
        } else {
            // Buscar paciente por DNI
            $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE dni = ?");
            $stmt->execute([$dni]);
            $paciente = $stmt->fetch();

            if ($paciente) {
                $_SESSION['rol'] = 'paciente';
                $_SESSION['dni'] = $dni;
                header('Location: paciente.php');
                exit;
            } else {
                $errorPaciente = 'El DNI ingresado no está registrado.';
            }
        }
    }

    // 🔹 Login ADMIN con DNI + contraseña
    if ($action === 'login_admin') {
        $dniAdmin = trim($_POST['dni_admin'] ?? '');
        $password = trim($_POST['password_admin'] ?? '');

        if ($dniAdmin === '' || $password === '') {
            $errorAdmin = 'Debe ingresar DNI y contraseña.';
        } else {
            // Admin fijo para el proyecto: DNI 00000000 / pass admin123
            if ($dniAdmin === '00000000' && $password === 'admin123') {
                $_SESSION['rol'] = 'admin';
                $_SESSION['dni_admin'] = $dniAdmin;
                header('Location: admin.php');
                exit;
            } else {
                $errorAdmin = 'Credenciales de administrador incorrectas (DNI/contraseña).';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sistema de Citas Médicas - Inicio</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <div class="app-shell">
    <div class="app-container">

      <header class="app-header">
        <div>
          <div class="app-title">
            <span class="logo-dot"></span>
            <span>Sistema de Citas del Hospital</span>
          </div>
          <p class="app-subtitle">
            Plataforma web para agendar, gestionar y consultar citas médicas en el centro de salud.
          </p>
        </div>
      </header>

      <main>
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Acceso al sistema</h2>
            <p class="card-subtitle">
              Ingresa como paciente para gestionar tus citas, o como administrador para gestionar la plataforma.
            </p>
          </div>

          <div class="btn-role-group">
            <button type="button" id="btn-rol-paciente" class="btn btn-primary">Soy paciente</button>
            <button type="button" id="btn-rol-admin" class="btn btn-outline">Soy administrador</button>
          </div>

          <!-- 🔹 Login Paciente SOLO DNI -->
          <section id="login-paciente" style="margin-top: 10px;">
            <h3 class="section-title">Inicio de sesión - Paciente</h3>
            <p class="section-desc">
              Ingresa tu DNI para acceder a tus citas registradas en el sistema.
            </p>

            <?php if ($errorPaciente !== ''): ?>
              <p class="small-text" style="color:#e53935; margin-top:8px;">
                <?= htmlspecialchars($errorPaciente) ?>
              </p>
            <?php endif; ?>

            <form id="form-login-paciente" class="mt-2" method="post" action="index.php">
              <input type="hidden" name="action" value="login_paciente">

              <div class="form-row">
                <label for="dni-paciente">DNI</label>
                <input
                  type="text"
                  id="dni-paciente"
                  name="dni_paciente"
                  placeholder="Ingresa tu DNI"
                  required
                >
              </div>

              <div class="mt-3">
                <button type="submit" class="btn btn-primary">Entrar como paciente</button>
              </div>
            </form>

            <p class="small-text mt-2">
              ¿Aún no tienes cuenta?
              <a href="registro.php">Regístrate aquí</a>
            </p>
          </section>

          <!-- 🔹 Login Administrador (DNI + contraseña) -->
          <section id="login-admin" style="display:none; margin-top: 22px;">
            <h3 class="section-title">Inicio de sesión - Administrador</h3>
            <p class="section-desc">
              Ingresa con tu DNI y contraseña para gestionar especialidades, médicos y citas.
            </p>

            <?php if ($errorAdmin !== ''): ?>
              <p class="small-text" style="color:#e53935; margin-top:8px;">
                <?= htmlspecialchars($errorAdmin) ?>
              </p>
            <?php endif; ?>

            <form id="form-login-admin" class="mt-2" method="post" action="index.php">
              <input type="hidden" name="action" value="login_admin">

              <div class="form-row">
                <label for="dni-admin">DNI administrador</label>
                <input
                  type="text"
                  id="dni-admin"
                  name="dni_admin"
                  placeholder="Ingresa tu DNI"
                  required
                >

                <label for="password-admin">Contraseña</label>
                <input
                  type="password"
                  id="password-admin"
                  name="password_admin"
                  placeholder="Contraseña"
                  required
                >
              </div>

              <div class="mt-3">
                <button type="submit" class="btn btn-primary">Entrar como administrador</button>
              </div>
            </form>
          </section>
        </div>
      </main>
    </div>
  </div>

  <!-- Script para alternar entre login paciente / admin -->
  <script>
    const btnRolPaciente = document.getElementById('btn-rol-paciente');
    const btnRolAdmin = document.getElementById('btn-rol-admin');
    const loginPaciente = document.getElementById('login-paciente');
    const loginAdmin = document.getElementById('login-admin');

    btnRolPaciente.addEventListener('click', () => {
      loginPaciente.style.display = 'block';
      loginAdmin.style.display = 'none';
      btnRolPaciente.classList.add('btn-primary');
      btnRolPaciente.classList.remove('btn-outline');
      btnRolAdmin.classList.add('btn-outline');
      btnRolAdmin.classList.remove('btn-primary');
    });

    btnRolAdmin.addEventListener('click', () => {
      loginPaciente.style.display = 'none';
      loginAdmin.style.display = 'block';
      btnRolAdmin.classList.add('btn-primary');
      btnRolAdmin.classList.remove('btn-outline');
      btnRolPaciente.classList.add('btn-outline');
      btnRolPaciente.classList.remove('btn-primary');
    });
  </script>
</body>
</html>
