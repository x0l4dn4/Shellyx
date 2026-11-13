<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
$user = current_user();

// Directorio de miembros con su número de mensajes (hilos + respuestas).
$members = [];
if ($r = mysqli_query(db(), "SELECT u.id, u.username,
        (SELECT COUNT(*) FROM posts p WHERE p.user_id = u.id)   AS hilos,
        (SELECT COUNT(*) FROM replies r WHERE r.user_id = u.id) AS respuestas
      FROM users u ORDER BY u.username")) {
    while ($row = mysqli_fetch_assoc($r)) {
        $row['msgs'] = (int)$row['hilos'] + (int)$row['respuestas'];
        $members[] = $row;
    }
}

forum_head('Miembros — Foro Shellyx');
forum_bar($user);
forum_breadcrumb([['Inicio', 'index.php'], ['Miembros', null]]);
?>
<section class="posts"><div class="wrap">
  <div class="fh-bhead"><h1>Miembros</h1></div>
  <div class="fh-cards">
    <?php foreach ($members as $m): [$rl, $rc] = member_role($m['username']); ?>
    <article class="fh-card">
      <?= avatar_html($m['username'], 'uc-ava') ?>
      <div class="fh-b">
        <h3><a href="perfil.php?u=<?= urlencode($m['username']) ?>"><?= e($m['username']) ?></a></h3>
        <p><span class="role <?= $rc ?>"><?= e($rl) ?></span></p>
        <div class="fh-counts"><span><b><?= (int)$m['msgs'] ?></b> mensajes</span><span>desde <?= member_since($m['username']) ?></span></div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</div></section>
<?php forum_footer(); ?>
