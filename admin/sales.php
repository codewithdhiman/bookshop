<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_header.php';

require_admin();
$pdo = getPDO();

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$export = ($_GET['export'] ?? '') === 'csv';

$stmt = $pdo->prepare("SELECT DATE(created_at) as day, SUM(total_amount) as total_sales, COUNT(*) as orders_count FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY DATE(created_at) DESC");
$stmt->execute([$from, $to]);
$rows = $stmt->fetchAll();

if ($export) {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="sales-' . $from . '-to-' . $to . '.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['date','orders','total_sales']);
  foreach ($rows as $r) fputcsv($out, [$r['day'], $r['orders_count'], $r['total_sales']]);
  fclose($out);
  exit;
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Sales Report</h3>
  <div>
    <a class="btn btn-outline-primary" href="?from=<?= esc($from) ?>&to=<?= esc($to) ?>&export=csv">Export CSV</a>
  </div>
</div>
<form class="row g-2 mb-3">
  <div class="col-auto"><input type="date" name="from" class="form-control" value="<?= esc($from) ?>"></div>
  <div class="col-auto"><input type="date" name="to" class="form-control" value="<?= esc($to) ?>"></div>
  <div class="col-auto"><button class="btn btn-secondary">Filter</button></div>
</form>

<div class="admin-card p-3">
  <div class="table-responsive">
    <table class="table mb-0 table-sm">
      <thead><tr><th>Date</th><th>Orders</th><th>Total Sales</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= esc($r['day']) ?></td>
          <td><?= (int)$r['orders_count'] ?></td>
          <td><?= currency_format($r['total_sales']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
