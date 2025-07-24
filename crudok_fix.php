<?php
session_start();
// Koneksi ke database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "toko_bangunan";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Inisialisasi keranjang belanja
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Tambahkan ke keranjang
if (isset($_GET['add_to_cart'])) {
    $product_id = $_GET['add_to_cart'];
    $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + 1;
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Tambah produk
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["create"])) {
    $name = trim($_POST["name"]);
    $price = trim($_POST["price"]);
    $description = trim($_POST["description"]);
    $stock = trim($_POST["stock"]);

    if ($name && $price && $description && $stock) {
        $stmt = $conn->prepare("INSERT INTO products (name, price, description, stock) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdsi", $name, $price, $description, $stock);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

// Edit produk
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update"])) {
    $id = $_POST["id"];
    $name = trim($_POST["name"]);
    $price = trim($_POST["price"]);
    $description = trim($_POST["description"]);
    $stock = trim($_POST["stock"]);

    $stmt = $conn->prepare("UPDATE products SET name=?, price=?, description=?, stock=? WHERE id=?");
    $stmt->bind_param("sdsii", $name, $price, $description, $stock, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

// Hapus produk
if (isset($_GET["delete"])) {
    $id = $_GET["delete"];
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

// Ambil data produk
$products = $conn->query("SHOW TABLES LIKE 'products'")->num_rows ? $conn->query("SELECT * FROM products") : false;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CRUD & Cart Toko Bangunan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5">
    <h2 class="mb-4">Form Tambah Produk</h2>
    <form method="POST" class="row g-3">
        <input type="hidden" name="create" value="1">
        <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Nama Produk" required></div>
        <div class="col-md-2"><input type="number" step="0.01" name="price" class="form-control" placeholder="Harga" required></div>
        <div class="col-md-4"><input type="text" name="description" class="form-control" placeholder="Deskripsi" required></div>
        <div class="col-md-2"><input type="number" name="stock" class="form-control" placeholder="Stok" required></div>
        <div class="col-md-1"><button type="submit" class="btn btn-primary w-100">Tambah</button></div>
    </form>

    <h2 class="mt-5">Daftar Produk</h2>
    <?php if ($products): ?>
    <table class="table table-bordered table-striped mt-3">
        <thead><tr><th>ID</th><th>Nama</th><th>Harga</th><th>Deskripsi</th><th>Stok</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php while($row = $products->fetch_assoc()): ?>
            <tr>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <td><?= $row['id'] ?></td>
                <td><input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control"></td>
                <td><input type="number" step="0.01" name="price" value="<?= $row['price'] ?>" class="form-control"></td>
                <td><input type="text" name="description" value="<?= htmlspecialchars($row['description']) ?>" class="form-control"></td>
                <td><input type="number" name="stock" value="<?= $row['stock'] ?>" class="form-control"></td>
                <td>
                    <button type="submit" name="update" class="btn btn-sm btn-success">Update</button>
                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus produk ini?')">Hapus</a>
                    <a href="?add_to_cart=<?= $row['id'] ?>" class="btn btn-sm btn-warning">+ Keranjang</a>
                </td>
            </form>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="alert alert-danger mt-3">Tabel <strong>products</strong> belum ada. Silakan import <code>ecommerce_toko_bangunan.sql</code> ke database <strong>toko_bangunan</strong>.</div>
    <?php endif; ?>

    <h2 class="mt-5">Keranjang Belanja</h2>
    <table class="table table-bordered mt-3">
        <thead><tr><th>Produk</th><th>Jumlah</th></tr></thead>
        <tbody>
        <?php
        foreach ($_SESSION['cart'] as $id => $qty):
            $result = $conn->query("SELECT name FROM products WHERE id=$id");
            $product = $result->fetch_assoc();
        ?>
            <tr>
                <td><?= htmlspecialchars($product['name'] ?? 'Produk tidak ditemukan') ?></td>
                <td><?= $qty ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php $conn->close(); ?>
