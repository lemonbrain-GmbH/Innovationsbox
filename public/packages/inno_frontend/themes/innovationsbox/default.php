<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Page\Page $c */
?>

<?= $this->inc('elements/header.php'); ?>

<header>
    <?php
    $a = new Area('Page Header');
$a->display($c);
?>
</header>
<?php // id main-content-area is the skip-link target where screenreaders would skip to?>
<main id="main-content-area">
    <?php
$a = new Area('Main');
$a->display($c);
?>
</main>

<?= $this->inc('elements/footer.php'); ?>
