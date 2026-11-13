<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');
?>

<?php Loader::element('footer_required'); ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (window.spa) {
            window.addEventListener('spaConfigPreDone', function() {
                window.spa.util.uriAnchor.configModule();
            });
            window.addEventListener('spaInitPreDone', function() {
                window.spa.util.uriAnchor.initModule();
            });

            window.spa.configModule();
            window.spa.initModule();
        }
    });
</script>

</div><!-- page wrapper -->
</body>

</html>