<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'paciente') {
    header('Location: index.php');
    exit;
}

$dniPaciente = $_SESSION['dni'];

$stmt = $pdo->prepare("SELECT * FROM pacientes WHERE dni = ?");
$stmt->execute([$dniPaciente]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header('Location: index.php');
    exit;
}

// Cancelar cita
if (isset($_GET['cancelar'])) {
    $idCita = (int) $_GET['cancelar'];
    $stmt = $pdo->prepare("DELETE FROM citas WHERE id_cita = ? AND dni_paciente = ?");
    $stmt->execute([$idCita, $dniPaciente]);
    header('Location: paciente.php');
    exit;
}

$mensajeReserva = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reservar') {
    $idEspecialidad = (int) ($_POST['id_especialidad'] ?? 0);
    $idMedico       = (int) ($_POST['id_medico'] ?? 0);
    $fecha          = trim($_POST['fecha'] ?? '');
    $hora           = trim($_POST['hora'] ?? '');

    if (!$idEspecialidad || !$idMedico || $fecha === '' || $hora === '') {
        $mensajeReserva = 'Completa todos los campos para reservar la cita.';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO citas (dni_paciente, id_medico, fecha, hora)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$dniPaciente, $idMedico, $fecha, $hora]);
        $mensajeReserva = 'Cita registrada correctamente.';
    }
}

// Datos para selects
$especialidades = $pdo->query("SELECT * FROM especialidades ORDER BY nombre")->fetchAll();

$idEspecialidadSeleccionada = isset($_POST['id_especialidad']) ? (int) $_POST['id_especialidad'] : 0;
if ($idEspecialidadSeleccionada > 0) {
    $stmt = $pdo->prepare("SELECT * FROM medicos WHERE id_especialidad = ? ORDER BY nombre");
    $stmt->execute([$idEspecialidadSeleccionada]);
    $medicos = $stmt->fetchAll();
} else {
    $medicos = $pdo->query("SELECT * FROM medicos ORDER BY nombre")->fetchAll();
}

$stmt = $pdo->prepare("
    SELECT c.id_cita, c.fecha, c.hora,
           m.nombre AS medico,
           e.nombre AS especialidad
    FROM citas c
    JOIN medicos m ON c.id_medico = m.id_medico
    JOIN especialidades e ON m.id_especialidad = e.id_especialidad
    WHERE c.dni_paciente = ?
    ORDER BY c.fecha, c.hora
");
$stmt->execute([$dniPaciente]);
$citas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel del Paciente</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <div class="app-shell">
    <div class="app-container">
      <header class="app-header">
        <div>
          <div class="app-title">
            <span class="logo-dot"></span>
            <span>Panel del Paciente</span>
          </div>
          <p class="app-subtitle">Reserva y consulta tus citas médicas en el hospital.</p>
        </div>

        <a href="logout.php" class="link-logout">
          Cerrar sesión
        </a>
      </header>

      <main class="grid-2">
        <section class="card">
          <div class="card-header">
            <h2 class="card-title">Mi perfil</h2>
            <p class="card-subtitle">Información registrada con tu DNI.</p>
          </div>

          <p class="small-text">
            <span class="badge">
              <span class="badge-dot"></span>
              DNI:
              <strong style="margin-left: 4px;"><?= htmlspecialchars($paciente['dni']) ?></strong>
            </span>
          </p>

          <div class="mt-3 small-text">
            <p><strong>Nombre completo:</strong>
              <?= htmlspecialchars($paciente['nombres'] . ' ' . $paciente['apellidos']) ?>
            </p>
            <p><strong>Teléfono:</strong>
              <?= htmlspecialchars($paciente['telefono'] ?? '—') ?>
            </p>
            <p><strong>Correo electrónico:</strong>
              <?= htmlspecialchars($paciente['correo'] ?? '—') ?>
            </p>
          </div>

          <hr style="border:0;border-top:1px solid var(--border-soft);margin:18px 0;opacity:0.7;">

          <h3 class="section-title">Reservar nueva cita</h3>
          <p class="section-desc">Selecciona la especialidad, médico y horario disponible.</p>

          <?php if ($mensajeReserva !== ''): ?>
            <p class="small-text" style="color:#2E7D32; margin-top:8px;">
              <?= htmlspecialchars($mensajeReserva) ?>
            </p>
          <?php endif; ?>

          <form method="post" action="paciente.php" class="mt-2">
            <input type="hidden" name="action" value="reservar">
            <div class="form-row">
              <label for="id_especialidad">Especialidad</label>
              <select id="id_especialidad" name="id_especialidad" required>
                <option value="">Seleccione</option>
                <?php foreach ($especialidades as $esp): ?>
                  <option value="<?= $esp['id_especialidad'] ?>"
                    <?= $idEspecialidadSeleccionada == $esp['id_especialidad'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($esp['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <label for="id_medico">Médico</label>
              <select id="id_medico" name="id_medico" required>
                <option value="">Seleccione</option>
                <?php foreach ($medicos as $med): ?>
                  <option value="<?= $med['id_medico'] ?>">
                    <?= htmlspecialchars($med['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <label for="fecha">Fecha</label>
              <input type="date" id="fecha" name="fecha" required>

              <label for="hora">Hora</label>
              <input type="time" id="hora" name="hora" required>
            </div>

            <div class="mt-3">
              <button type="submit" class="btn btn-primary">Confirmar reserva</button>
            </div>
          </form>
        </section>

        <section class="card">
          <div class="card-header">
            <h2 class="card-title">Mis citas</h2>
            <p class="card-subtitle">Listado de citas asociadas a tu DNI.</p>
          </div>

          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Especialidad</th>
                  <th>Médico</th>
                  <th>Fecha</th>
                  <th>Hora</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($citas) === 0): ?>
                  <tr>
                    <td colspan="5">No tienes citas registradas.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($citas as $c): ?>
                    <tr>
                      <td><?= htmlspecialchars($c['especialidad']) ?></td>
                      <td><?= htmlspecialchars($c['medico']) ?></td>
                      <td><?= htmlspecialchars($c['fecha']) ?></td>
                      <td><?= htmlspecialchars($c['hora']) ?></td>
                      <td>
                        <a href="paciente.php?cancelar=<?= $c['id_cita'] ?>"
                           class="small-text"
                           style="color:#e53935;"
                           onclick="return confirm('¿Seguro que deseas cancelar esta cita?');">
                          Cancelar
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <p class="small-text mt-2">
            * Solo puedes cancelar tus propias citas desde este panel.
          </p>
        </section>
      </main>
    </div>
  </div>
</body>
</html>
