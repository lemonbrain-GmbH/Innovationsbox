<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Page\Page $c */
/** @var \Concrete\Core\Entity\Site\Site $site */
$site = Core::make('site')->getSite();

$cta1Label = (string) $site->getAttribute('footer_cta_1_label');
$cta1Link = (string) $site->getAttribute('footer_cta_1_link');
$cta2Label = (string) $site->getAttribute('footer_cta_2_label');
$cta2Link = (string) $site->getAttribute('footer_cta_2_link');
$legal1Label = (string) $site->getAttribute('footer_legal_1_label');
$legal1Link = (string) $site->getAttribute('footer_legal_1_link');
$legal2Label = (string) $site->getAttribute('footer_legal_2_label');
$legal2Link = (string) $site->getAttribute('footer_legal_2_link');
?>

<?php
$props = [];
include DIR_COMPONENTS . '/atoms/fab/fab.php';
?>

<footer>
    <div class="lb-container static-content">
        <div class="footer__top-row">
            <div class="logos">
                <?php if (is_object($site->getAttribute('logo_arento'))) { ?>
                    <a href="<?=$site->getAttribute('arento_link') ?? '' ?>" target="_blank" class="logo-link">
                        <img src="<?= $site->getAttribute('logo_arento')?->getRelativePath() ?>" alt="arento ag">
                    </a>
                <?php } ?>
                <?php if (is_object($site->getAttribute('logo_klimaregion_gl'))) { ?>
                    <a href="<?=$site->getAttribute('klimaregion_link') ?? '' ?>" target="_blank" class="logo-link">
                        <img src="<?= $site->getAttribute('logo_klimaregion_gl')?->getRelativePath() ?>" alt="Klimaregion Glarnerland">
                    </a>
                <?php } ?>
                <?php if (is_object($site->getAttribute('logo_klimaglarus'))) { ?>
                    <a href="<?=$site->getAttribute('klimaglarus_link') ?? '' ?>" target="_blank" class="logo-link">
                        <img src="<?= $site->getAttribute('logo_klimaglarus')?->getRelativePath() ?>" alt="klimaglarus.ch">
                    </a>
                <?php } ?>
            </div>
            <?php if ($cta1Label || $cta2Label): ?>
                <div class="footer__cta-row">
                    <?php if ($cta1Label): ?>
                        <a href="<?= htmlspecialchars($cta1Link) ?>" class="button button--outline-accent button--lg"><?= htmlspecialchars($cta1Label) ?></a>
                    <?php endif; ?>
                    <?php if ($cta2Label): ?>
                        <a href="<?= htmlspecialchars($cta2Link) ?>" class="button button--accent button--lg"><?= htmlspecialchars($cta2Label) ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <hr class="footer__divider">

        <div class="footer__bottom-row">
            <p>
                Diese Innovationsbox ist ein Engagement der
                <a href="<?= $site->getAttribute('arento_link') ?? '' ?>" target="_blank" class="footer-link">arento ag</a>
                für die
                <a href="<?= $site->getAttribute('klimaregion_link') ?? '' ?>" target="_blank" class="footer-link">Klimaregion Glarnerland</a>
                und den Verein
                <a href="<?= $site->getAttribute('klimaglarus_link') ?? '' ?>" target="_blank" class="footer-link">KlimaGlarus.ch</a>
            </p>
            <?php if ($legal1Label || $legal2Label): ?>
                <nav class="footer__legal" aria-label="<?= t('Rechtliches') ?>">
                    <?php if ($legal1Label): ?>
                        <a href="<?= htmlspecialchars($legal1Link) ?>" class="footer-link"><?= htmlspecialchars($legal1Label) ?></a>
                    <?php endif; ?>
                    <?php if ($legal1Label && $legal2Label): ?>
                        <span class="footer__legal-sep">|</span>
                    <?php endif; ?>
                    <?php if ($legal2Label): ?>
                        <a href="<?= htmlspecialchars($legal2Link) ?>" class="footer-link"><?= htmlspecialchars($legal2Label) ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    <?php
    $a = new GlobalArea('Accept Cookies');
$a->display($c);
?>
</footer>

<?= $this->inc('elements/footer_bottom.php'); ?>
