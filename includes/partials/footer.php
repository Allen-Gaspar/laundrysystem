<?php
require_once dirname(__DIR__) . '/paths.php';
$baseHref = base_path();
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= htmlspecialchars($baseHref) ?>assets/js/notifications.js"></script>
</body>
</html>
