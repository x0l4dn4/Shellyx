<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';

// Lista de miembros.
$user = current_user();

$members = [];
$res = mysqli_query(db(), "SELECT u.id, u.username,
                                  (SELECT COUNT(*) FROM posts   p WHERE p.user_id = u.id) AS hilos,
                                  (SELECT COUNT(*) FROM replies r WHERE r.user_id = u.id) AS respuestas,
                                  (SELECT MAX(x.created_at) FROM (SELECT user_id, created_at FROM posts
                                                                  UNION ALL SELECT user_id, created_at FROM replies) x
                                    WHERE x.user_id = u.id) AS ultima
                           FROM users u");
while ($res && $row = mysqli_fetch_assoc($res)) { $members[] = $row; }

// Staff primero, luego por nº de mensajes.
$rank = fn($n) => $n === 'admin' ? 0 : ($n === 'moderador' ? 1 : 2);
usort($members, function ($a, $b) use ($rank) {
    return [$rank($a['username']), -($a['hilos'] + $a['respuestas']), $a['username']]
       <=> [$rank($b['username']), -($b['hilos'] + $b['respuestas']), $b['username']];
});

forum_head('Miembros — Shellyx');
forum_bar($user, 'miembros');
forum_breadcrumb([['Inicio', 'index.php'], ['Miembros', null]]);
?>
<section class="page"><div class="wrap">
  <div class="page-head">
    <h1>Miembros</h1>
    <p><?= count($members) ?> miembros en la comunidad. Pulsa en uno para ver su perfil y sus mensajes.</p>
  </div>
  <div class="members">
    <?php foreach ($members as $m): [$roleLabel, $roleClass] = member_role($m['username']); ?>
    <a class="member" href="<?= profile_url($m['username']) ?>">
      <?= avatar_html($m['username'], 't-ava') ?>
      <div class="m-main">
        <div class="m-name"><?= e($m['username']) ?></div>
        <div class="m-meta"><span class="role <?= $roleClass ?>"><?= e($roleLabel) ?></span><span>desde <?= member_since($m['username']) ?></span></div>
      </div>
      <dl class="m-stats">
        <div><dt>Mensajes</dt><dd><?= (int)$m['hilos'] + (int)$m['respuestas'] ?></dd></div>
        <div><dt>Últ. actividad</dt><dd><?= $m['ultima'] ? e(hace($m['ultima'])) : '—' ?></dd></div>
      </dl>
    </a>
    <?php endforeach; ?>
  </div>
</div></section>
<?php forum_footer(); ?>
