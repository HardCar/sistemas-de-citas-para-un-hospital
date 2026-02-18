<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_especialidad') {
        $nombre = trim($_POST['nombre_especialidad'] ?? '');
        if ($nombre !== '') {
            $stmt = $pdo->prepare("INSERT INTO especialidades (nombre) VALUES (?)");
            $stmt->execute([$nombre]);
        }
        header('Location: admin.php');
        exit;
    }

    if ($action === 'delete_especialidad') {
        $idEsp = (int) ($_POST['id_especialidad'] ?? 0);
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM medicos WHERE id_especialidad = ?");
        $stmt->execute([$idEsp]);
        $row = $stmt->fetch();

        if ($row && (int)$row['total'] === 0) {
            $stmt = $pdo->prepare("DELETE FROM especialidades WHERE id_especialidad = ?");
            $stmt->execute([$idEsp]);
        }
        header('Location: admin.php');
        exit;
    }

    if ($action === 'add_medico') {
        $nombre = trim($_POST['nombre_medico'] ?? '');
        $idEsp  = (int) ($_POST['id_especialidad_medico'] ?? 0);
        if ($nombre !== '' && $idEsp > 0) {
            $stmt = $pdo->prepare("INSERT INTO medicos (nombre, id_especialidad) VALUES (?, ?)");
            $stmt->execute([$nombre, $idEsp]);
        }
        header('Location: admin.php');
        exit;
    }

    if ($action === 'delete_medico') {
        $idMed = (int) ($_POST['id_medico'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM medicos WHERE id_medico = ?");
        $stmt->execute([$idMed]);
        header('Location: admin.php');
        exit;
    }
}

$especialidades = $pdo->query("SELECT * FROM especialidades ORDER BY nombre")->fetchAll();

$stmt = $pdo->query("
    SELECT m.id_medico, m.nombre, e.nombre AS especialidad
    FROM medicos m
    JOIN especialidades e ON m.id_especialidad = e.id_especialidad
    ORDER BY e.nombre, m.nombre
");
$medicos = $stmt->fetchAll();

$dniAdmin = $_SESSION['dni_admin'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel del Administrador</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
  <div class="app-shell">
    <div class="app-container">
      <header class="app-header">
        <div>
          <div class="app-title">
            <span class="logo-dot"></span>
            <span>Panel del Administrador</span>
          </div>
          <p class="app-subtitle">
            Gestión de especialidades y médicos del sistema de citas del hospital.
          </p>
        </div>

        <div style="display:flex; gap:10px; align-items:center;">
          <span class="small-text">
            DNI admin: <strong><?= htmlspecialchars($dniAdmin) ?></strong>
          </span>
          <a href="logout.php" class="link-logout">
            Cerrar sesión
          </a>
        </div>
      </header>

      <main class="grid-2">
        <section class="card">
          <div class="card-header">
            <h2 class="card-title">Especialidades</h2>
            <p class="card-subtitle">Registra y administra las especialidades disponibles.</p>
          </div>

          <form method="post" action="admin.php">
            <input type="hidden" name="action" value="add_especialidad">
            <div class="form-row">
              <label for="nombre-especialidad">Nombre de la especialidad</label>
              <input type="text" id="nombre-especialidad" name="nombre_especialidad"
                     placeholder="Ej. Medicina General" required>
            </div>
            <div class="mt-3">
              <button type="submit" class="btn btn-primary">Agregar especialidad</button>
            </div>
          </form>

          <div class="mt-4">
            <h3 class="section-title">Listado de especialidades</h3>
            <p class="section-desc">Solo se pueden eliminar especialidades sin médicos asociados.</p>

            <div class="table-wrapper mt-2">
              <table>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($especialidades) === 0): ?>
                    <tr>
                      <td colspan="3">No hay especialidades registradas.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($especialidades as $i => $esp): ?>
                      <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($esp['nombre']) ?></td>
                        <td>
                          <form method="post" action="admin.php" style="display:inline;">
                            <input type="hidden" name="action" value="delete_especialidad">
                            <input type="hidden" name="id_especialidad" value="<?= $esp['id_especialidad'] ?>">
                            <button type="submit"
                                    class="btn btn-outline"
                                    style="font-size:0.75rem;padding:4px 9px;"
                                    onclick="return confirm('¿Eliminar esta especialidad? (Solo si no tiene médicos asociados)');">
                              Eliminar
                            </button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <hr style="border:0;border-top:1px solid var(--border-soft);margin:18px 0;opacity:0.7;">

          <h3 class="section-title">Registrar médico</h3>
          <p class="section-desc">Asocia un médico a una especialidad para habilitar sus citas.</p>

          <form method="post" action="admin.php" class="mt-2">
            <input type="hidden" name="action" value="add_medico">
            <div class="form-row">
              <label for="nombre-medico">Nombre del médico</label>
              <input type="text" id="nombre-medico" name="nombre_medico"
                     placeholder="Ej. Dr. Juan Pérez" required>

              <label for="id-especialidad-medico">Especialidad</label>
              <select id="id-especialidad-medico" name="id_especialidad_medico" required>
                <option value="">Seleccione</option>
                <?php foreach ($especialidades as $esp): ?>
                  <option value="<?= $esp['id_especialidad'] ?>">
                    <?= htmlspecialchars($esp['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mt-3">
              <button type="submit" class="btn btn-primary">Agregar médico</button>
            </div>
          </form>
        </section>

        <section class="card">
          <div class="card-header">
            <h2 class="card-title">Médicos registrados</h2>
            <p class="card-subtitle">Resumen de los médicos y su especialidad.</p>
          </div>

          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nombre</th>
                  <th>Especialidad</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($medicos) === 0): ?>
                  <tr>
                    <td colspan="4">No hay médicos registrados.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($medicos as $i => $med): ?>
                    <tr>
                      <td><?= $i + 1 ?></td>
                      <td><?= htmlspecialchars($med['nombre']) ?></td>
                      <td><?= htmlspecialchars($med['especialidad']) ?></td>
                      <td>
                        <form method="post" action="admin.php" style="display:inline;">
                          <input type="hidden" name="action" value="delete_medico">
                          <input type="hidden" name="id_medico" value="<?= $med['id_medico'] ?>">
                          <button type="submit"
                                  class="btn btn-outline"
                                  style="font-size:0.75rem;padding:4px 9px;"
                                  onclick="return confirm('¿Eliminar este médico?');">
                            Eliminar
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <p class="small-text mt-2">
            * Para el alcance del proyecto, no se gestiona el impacto sobre citas antiguas al eliminar médicos.
          </p>
        </section>
      </main>
    </div>
  </div>
</body>
</html>
