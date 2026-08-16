<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = getPDO();
require_admin();

// params
$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$tf = $_GET['tf'] ?? 'all';
$per_page = (int)($_GET['per_page'] ?? 20);
if (!in_array($per_page, [20,50,100])) $per_page = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $like = "%{$q}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($role !== 'all') { $where[] = 'role = ?'; $params[] = $role; }
if ($status !== 'all') { $where[] = 'status = ?'; $params[] = ($status==='active'?1:0); }
if ($tf !== 'all') { $where[] = ($tf==='enabled' ? 'two_factor_enabled = 1' : 'two_factor_enabled = 0'); }

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// total
$total_st = $pdo->prepare("SELECT COUNT(*) FROM users {$where_sql}");
$total_st->execute($params);
$total = (int)$total_st->fetchColumn();
$sql = "SELECT id,name,email,phone,role,status,created_at,two_factor_enabled FROM users {$where_sql} ORDER BY id DESC LIMIT ? OFFSET ?";
$st = $pdo->prepare($sql);
// execute with params plus limit/offset
$execParams = $params;
$execParams[] = $per_page;
$execParams[] = $offset;
$st->execute($execParams);
$users = $st->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Users</h5>
      <a href="<?= base_url('admin/user-add.php') ?>" class="btn btn-primary">Add User</a>
    </div>
    <form method="get" class="row g-2 mb-3">
      <div class="col-md-4"><input name="q" value="<?= esc($q) ?>" class="form-control" placeholder="Search name, email or mobile"></div>
      <div class="col-md-2"><select name="role" class="form-select"><option value="all">All roles</option><option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option><option value="customer" <?= $role==='customer'?'selected':'' ?>>Customer</option></select></div>
      <div class="col-md-2"><select name="status" class="form-select"><option value="all">All status</option><option value="active" <?= $status==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option></select></div>
      <div class="col-md-2"><select name="tf" class="form-select"><option value="all">2FA (all)</option><option value="enabled" <?= $tf==='enabled'?'selected':'' ?>>Enabled</option><option value="disabled" <?= $tf==='disabled'?'selected':'' ?>>Disabled</option></select></div>
      <div class="col-md-2 d-flex"><select name="per_page" class="form-select me-2"><option value="20">20</option><option value="50">50</option><option value="100">100</option></select><button class="btn btn-outline-primary">Apply</button></div>
    </form>

    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Mobile</th>
            <th>Role</th>
            <th>Status</th>
            <th>2FA</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= esc($u['id']) ?></td>
              <td><?= esc($u['name']) ?></td>
              <td><?= esc($u['email']) ?></td>
              <td><?= esc($u['phone']) ?></td>
              <td><span class="badge bg-secondary"><?= esc($u['role']) ?></span></td>
              <td><?= $u['status']?'<span class="badge bg-success">Active</span>':'<span class="badge bg-warning">Inactive</span>' ?></td>
              <td><?= !empty($u['two_factor_enabled'])?'<span class="badge bg-success">Enabled</span>':'<span class="badge bg-secondary">Disabled</span>' ?></td>
              <td><?= esc($u['created_at']) ?></td>
              <td>
                <a href="<?= base_url('admin/user-view.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-secondary">View</a>
                <a href="<?= base_url('admin/user-edit.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <form method="post" action="<?= base_url('admin/user-actions.php') ?>" class="d-inline-block" onsubmit="return confirm('Are you sure?');">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="user_id" value="<?= esc($u['id']) ?>">
                  <?php if ($u['status']): ?>
                    <input type="hidden" name="action" value="deactivate">
                    <button class="btn btn-sm btn-warning">Deactivate</button>
                  <?php else: ?>
                    <input type="hidden" name="action" value="activate">
                    <button class="btn btn-sm btn-success">Activate</button>
                  <?php endif; ?>
                </form>
                <form method="post" action="<?= base_url('admin/user-actions.php') ?>" class="d-inline-block" onsubmit="return confirm('Delete user? This is irreversible unless the user has no orders.');">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="user_id" value="<?= esc($u['id']) ?>">
                  <input type="hidden" name="action" value="delete">
                  <button class="btn btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php
    // pagination
    $total_pages = (int)ceil($total / $per_page);
    $query = $_GET;
    unset($query['page']);
    $base_url = base_url('admin/users.php') . '?' . http_build_query($query) . (empty($query) ? '' : '&');
    ?>
    <nav>
      <ul class="pagination">
        <?php if ($page>1): ?><li class="page-item"><a class="page-link" href="<?= esc($base_url . 'page=' . ($page-1)) ?>">Previous</a></li><?php endif; ?>
        <?php for ($i=1;$i<=$total_pages;$i++): ?>
          <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="<?= esc($base_url . 'page=' . $i) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <?php if ($page<$total_pages): ?><li class="page-item"><a class="page-link" href="<?= esc($base_url . 'page=' . ($page+1)) ?>">Next</a></li><?php endif; ?>
      </ul>
    </nav>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
