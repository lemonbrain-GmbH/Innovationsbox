<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Page\Page $c */
/** @var mixed $error */
/** @var mixed $success */
/** @var mixed $message */

echo $this->inc('elements/header.php');
?>
<header>
    <?php
    $a = new Area('Header Image');
$a->display($c);
?>
</header>
<main>
    <?php
View::element(
    'system_errors',
    [
        'format' => 'block',
        'error' => isset($error) ? $error : null,
        'success' => isset($success) ? $success : null,
        'message' => isset($message) ? $message : null,
    ]
);
?>

    <?php
$a = new Area('Main');
$a->display($c);
?>
</main>

<?php
echo $this->inc('elements/footer.php');
