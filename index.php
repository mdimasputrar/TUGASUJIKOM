<?php
session_start();
include 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Menambah tugas ke dalam database
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_task'])) {
    $nama = $_POST['nama'];
    $prioritas = $_POST['prioritas'];
    $tanggal = $_POST['tanggal'];

    if (!empty($nama) && !empty($prioritas) && !empty($tanggal)) {
        $sql = "INSERT INTO tasks (nama, prioritas, tanggal, user_id, status) 
                VALUES ('$nama', '$prioritas', '$tanggal', '$user_id', 'Belum Selesai')";
        if ($conn->query($sql) === TRUE) {
            header("Location: index.php");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Semua field harus diisi!";
    }
}

// Menambah subtasks ke dalam database
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subtask'])) {
    $task_id = $_POST['task_id'];
    $subtask_nama = $_POST['subtask_nama'];

    if (!empty($task_id) && !empty($subtask_nama)) {
        $sql = "INSERT INTO subtasks (task_id, nama, status) 
                VALUES ('$task_id', '$subtask_nama', 'Belum Selesai')";
        if ($conn->query($sql) === TRUE) {
            header("Location: index.php");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        echo "Subtask tidak boleh kosong!";
    }
}

// Pencarian dan filter tugas
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$query = "SELECT * FROM tasks WHERE user_id='$user_id'";
if (!empty($search)) {
    $query .= " AND nama LIKE '%$search%'";
}
if (!empty($status_filter)) {
    $query .= " AND status='$status_filter'";
}
$query .= " ORDER BY id DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <link rel="stylesheet" href="styles.css"> <!-- Menghubungkan file CSS -->
    <script>
        function confirmDelete(id) {
            return confirm("Apakah Anda yakin ingin menghapus item ini?");
        }
    </script>
</head>
<body>
    <div class="navbar">
        <div class="container">
            <h2>To-Do List</h2>
            <div class="user-info">
                <p>Halo, <?= $_SESSION['user_id']; ?>! <a href="logout.php" class="logout-btn">Logout</a></p>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Form Pencarian -->
        <div class="search-form">
            <form method="GET">
                <input type="text" name="search" placeholder="Cari Tugas" value="<?= $search; ?>" class="search-input">
                <select name="status" class="status-select">
                    <option value="">Semua Status</option>
                    <option value="Belum Selesai" <?= $status_filter == "Belum Selesai" ? "selected" : ""; ?>>Belum Selesai</option>
                    <option value="Selesai" <?= $status_filter == "Selesai" ? "selected" : ""; ?>>Selesai</option>
                </select>
                <button type="submit" class="search-btn">Cari</button>
            </form>
        </div>

        <!-- Form Tambah Tugas -->
        <div class="add-task-form">
            <form method="POST">
                <input type="text" name="nama" placeholder="Nama Tugas" required class="task-input">
                <select name="prioritas" class="priority-select">
                    <option value="Tinggi">Tinggi</option>
                    <option value="Sedang">Sedang</option>
                    <option value="Rendah">Rendah</option>
                </select>
                <input type="date" name="tanggal" required class="date-input">
                <button type="submit" name="add_task" class="btn-add-task">Tambah Tugas</button>
            </form>
        </div>

        <h3>Daftar Tugas</h3>
        <div class="task-list">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="task-item">
                    <div class="task-header">
                        <strong><?= $row['nama']; ?></strong> 
                        <span class="task-meta"><?= $row['prioritas']; ?>, <?= $row['tanggal']; ?>, <?= $row['status']; ?></span>
                    </div>
                    <div class="task-actions">
                        <a href="edit.php?id=<?= $row['id']; ?>" class="btn-action">Edit</a> |
                        <a href="update.php?id=<?= $row['id']; ?>" class="btn-action btn-finish">Selesai</a> |
                        <a href="delete.php?id=<?= $row['id']; ?>" class="btn-action btn-delete" onclick="return confirmDelete(<?= $row['id']; ?>)">Hapus</a>
                    </div>

                    <!-- Menampilkan subtasks sebagai list di dalam tugas -->
                    <div class="subtasks">
                        <ul>
                            <?php 
                            $task_id = $row['id'];
                            $subtasks = $conn->query("SELECT * FROM subtasks WHERE task_id='$task_id'");
                            while ($subtask = $subtasks->fetch_assoc()): ?>
                                <li class="subtask-item">
                                    <?= $subtask['nama']; ?> (<?= $subtask['status']; ?>)
                                    <a href="update_subtask.php?id=<?= $subtask['id']; ?>" class="btn-action btn-finish">Selesai</a> |
                                    <a href="delete_subtask.php?id=<?= $subtask['id']; ?>" class="btn-action btn-delete" onclick="return confirmDelete(<?= $subtask['id']; ?>)">Hapus</a>
                                </li>
                            <?php endwhile; ?>
                        </ul>

                        <!-- Form untuk menambahkan subtasks -->
                        <form method="POST" class="add-subtask-form">
                            <input type="hidden" name="task_id" value="<?= $row['id']; ?>">
                            <input type="text" name="subtask_nama" placeholder="Nama Subtask" required class="subtask-input">
                            <button type="submit" name="add_subtask" class="btn-add-subtask">Tambah Subtask</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
