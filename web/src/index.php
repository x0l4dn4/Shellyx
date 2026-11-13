<?php
// Landing de Shellyx. Solo presentación.
require_once __DIR__ . '/lib/layout.php';
sx_header('Shellyx — Hardware Hacking Tools', 'inicio');
?>

<!-- ===================== HERO ===================== -->
<header class="hero">
  <div class="container hero__grid">
    <div>
      <span class="tag"><span class="dot"></span> Ciberseguridad · Hardware</span>
      <h1 style="margin-top:18px">Hardware hacking<br><span class="accent">por y para hackers</span></h1>
      <p class="lead">Gadgets programables, firmware a medida y un ecosistema de scripts y módulos que se actualiza cada mes. Abierto, asequible y pensado tanto para quien empieza como para profesionales.</p>
      <div class="hero__cta">
        <a class="btn btn--primary" href="/tienda/"><?= sx_icon('cart', 18) ?> Explorar la tienda</a>
        <a class="btn btn--ghost" href="/foro/"><?= sx_icon('community', 18) ?> Entrar al foro</a>
      </div>
      <div class="hero__meta">
        <div><b>&lt;100€</b><span>Gama de entrada</span></div>
        <div><b>XMR·BTC·ETH</b><span>Pago anónimo</span></div>
        <div><b>+Retos/mes</b><span>Contenido formativo</span></div>
      </div>
    </div>

    <div class="terminal" aria-hidden="true">
      <div class="terminal__bar"><i></i><i></i><i></i><span>root@shellyx:~</span></div>
      <div class="terminal__body">
        <div><span class="pr">$</span> <span class="cm">shellyx flash --module wifi_recon</span></div>
        <div class="out">[*] Detectando dispositivo en /dev/ttyUSB0</div>
        <div class="out">[*] Escribiendo firmware <span class="mut">(v2.4.1)</span> ...</div>
        <div class="ok">[+] Firmware instalado · módulo listo</div>
        <div style="margin-top:10px"><span class="pr">$</span> <span class="cm">shellyx run recon --scan</span></div>
        <div class="out">[+] 3 redes · 12 clientes · captura activa</div>
        <div style="margin-top:10px"><span class="pr">$</span> <span class="cursor"></span></div>
      </div>
    </div>
  </div>
</header>

<!-- ===================== PROPUESTA (3 cards) ===================== -->
<section class="section" id="modulos">
  <div class="container">
    <span class="eyebrow">Qué construimos</span>
    <h2>Un ecosistema, no solo un cacharro</h2>
    <p class="lead" style="margin-bottom:34px">Hardware real desde el primer día, con el software y la comunidad que lo hacen útil.</p>
    <div class="cards">
      <div class="card">
        <div class="card__icon"><?= sx_icon('chip') ?></div>
        <h3>Gadgets programables</h3>
        <p>Dispositivos abiertos para crear tus propias herramientas. Flashea firmwares, carga módulos y experimenta sin cajas negras.</p>
      </div>
      <div class="card">
        <div class="card__icon"><?= sx_icon('firmware') ?></div>
        <h3>Software a medida</h3>
        <p>Firmwares y scripts listos para usarse. ¿No quieres programar a mano? Compra los módulos oficiales y empieza ya.</p>
      </div>
      <div class="card">
        <div class="card__icon"><?= sx_icon('community') ?></div>
        <h3>Comunidad abierta</h3>
        <p>Un ecosistema que crece con la gente: foro de soporte, repositorio en GitHub y retos nuevos cada mes.</p>
      </div>
    </div>
  </div>
</section>

<!-- ===================== BANDA: desarrollo continuo ===================== -->
<div class="band" id="comunidad">
  <div class="container band__inner">
    <div>
      <span class="eyebrow" style="margin-bottom:6px">En constante desarrollo</span>
      <h2 style="margin:0">Mantenimiento continuo de scripts y módulos propios</h2>
    </div>
    <a class="btn btn--primary" href="/foro/"><?= sx_icon('bolt', 18) ?> Unirse a la comunidad</a>
  </div>
</div>

<!-- ===================== PARA QUIÉN ===================== -->
<section class="section" id="gama">
  <div class="container">
    <span class="eyebrow">Para quién</span>
    <h2>De los primeros pasos al pentest profesional</h2>
    <div class="cards" style="margin-top:28px">
      <div class="card">
        <div class="card__icon"><?= sx_icon('terminal') ?></div>
        <h3>Principiantes</h3>
        <p>Aprende ciberseguridad con hardware real desde el día uno, sin conocimientos previos de electrónica. De forma divertida y guiada.</p>
      </div>
      <div class="card">
        <div class="card__icon"><?= sx_icon('cpu') ?></div>
        <h3>Profesionales</h3>
        <p>Gadgets asequibles, abiertos y programables para pentesters y auditores: flashea firmwares ajenos y crea tus propias herramientas.</p>
      </div>
      <div class="card">
        <div class="card__icon"><?= sx_icon('board') ?></div>
        <h3>Centros y empresas</h3>
        <p>Kits listos para clase o formación interna (B2B), orientados siempre al uso ético y legal de la seguridad ofensiva.</p>
      </div>
    </div>
  </div>
</section>

<!-- ===================== PRIVACIDAD ===================== -->
<section class="section section--tight" id="privacidad">
  <div class="container">
    <div class="panel panel--pad" style="display:grid;grid-template-columns:1fr 1fr;gap:34px;align-items:center">
      <div>
        <span class="eyebrow">Privacidad &amp; anonimato</span>
        <h2>Lo que no guardamos también es una característica</h2>
        <p class="muted">Solo almacenamos lo imprescindible para proteger la plataforma: usuario, hash de la contraseña, y registros de acceso con IP y marca de tiempo.</p>
        <p class="muted" style="margin:0">Nunca guardamos datos bancarios ni monederos, correos, teléfonos, nombres ni direcciones. No se requieren.</p>
        <div class="hero__cta">
          <a class="btn btn--ghost" href="#empresa"><?= sx_icon('lock', 18) ?> Cómo tratamos los datos</a>
        </div>
      </div>
      <div class="grid" style="grid-template-columns:1fr 1fr">
        <div class="card"><div class="card__icon"><?= sx_icon('crypto') ?></div><h3>Pago en cripto</h3><p>Software de pago único con XMR, BTC o ETH. Más barato si lo compras junto al gadget.</p></div>
        <div class="card"><div class="card__icon"><?= sx_icon('shield') ?></div><h3>Anonimato</h3><p>Sin correos ni datos personales: tu cuenta es tu usuario y nada más.</p></div>
        <div class="card"><div class="card__icon"><?= sx_icon('antenna') ?></div><h3>Accesorios</h3><p>Antenas, carcasas y placas de expansión para ampliar tus equipos.</p></div>
        <div class="card"><div class="card__icon"><?= sx_icon('wifi') ?></div><h3>Módulos</h3><p>Repositorio de scripts y firmwares que se actualiza en continuo.</p></div>
      </div>
    </div>
  </div>
</section>

<!-- ===================== SOBRE LA EMPRESA ===================== -->
<section class="section" id="empresa">
  <div class="container">
    <span class="eyebrow">Sobre Shellyx</span>
    <h2>El hardware para practicar no debería ser caro</h2>
    <p class="lead">Muchos que quieren entrar en la ciberseguridad no saben cómo crear sus propias herramientas. Shellyx diseña y vende accesorios con un ecosistema de scripts y módulos actualizados, para aprender de forma divertida, orientativa y original — y para que los profesionales construyan sobre dispositivos potentes.</p>
    <div class="band__inner" style="padding:28px 0 0">
      <div class="pay">
        <span>I+D</span><span>Producto</span><span>Marketing</span><span>Datos</span><span>Ingeniería</span>
      </div>
      <a class="btn btn--primary" href="/tienda/">Ver catálogo <?= sx_icon('arrow', 18) ?></a>
    </div>
  </div>
</section>

<!-- ===================== RETOS MENSUALES ===================== -->
<section class="section" id="retos">
  <div class="container">
    <span class="eyebrow">Comunidad</span>
    <h2>Retos mensuales</h2>
    <p class="lead">Cada mes publicamos un reto práctico en el foro: un escenario para trastear con los gadgets, compartir tu solución y aprender de la de los demás. Hay un hilo por reto y una categoría donde quedan archivados para que puedas repasarlos cuando quieras.</p>
    <div class="band__inner" style="padding:28px 0 0">
      <div class="pay"><span>Firmware</span><span>Radio</span><span>NFC</span><span>WiFi</span></div>
      <a class="btn btn--primary" href="/foro/"><?= sx_icon('bolt', 18) ?> Ir al foro</a>
    </div>
  </div>
</section>

<!-- ===================== USO ÉTICO ===================== -->
<section class="section section--tight" id="etica">
  <div class="container">
    <span class="eyebrow">Uso responsable</span>
    <h2>Uso ético y legal</h2>
    <p class="lead">Los dispositivos de Shellyx son herramientas de aprendizaje y auditoría. Úsalos solo sobre sistemas y redes de tu propiedad o para los que tengas permiso explícito por escrito, y nunca con datos o credenciales de terceros. El uso que hagas del hardware y del software es responsabilidad tuya.</p>
  </div>
</section>

<?php sx_footer(); ?>
