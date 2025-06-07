<?php
/**
 * Template for displaying membership information
 */

defined('ABSPATH') || exit;

// Add nonces for security
$delete_nonce = wp_create_nonce('delete_member_id');
$generate_nonce = wp_create_nonce('generate_member_id');
?>

<div class="woocommerce-MyAccount-content">
    <h2><?php esc_html_e('Mitgliedsausweis', 'qd-memberid'); ?></h2>
    
    <?php if ($memberid): ?>
        <p><?php esc_html_e(
            'Der Mitgliedsausweis ist dein Zahlmittel für die Selbsbedienungskasse im Laden. ',
            'qd-memberid'); ?></p>

        <p><?php esc_html_e(
            'Bewahre deinen Mitgliedsausweis sicher auf und lösche ihn, wenn du ihn verlierst.', 
            'qd-memberid'); ?></p>
        
        <div class="memberid-barcode">
            <svg id="barcode"></svg>
        </div>

        <!-- <p><button class="button" onclick="deleteMemberID()"><?php esc_html_e('Mitgliedsausweis löschen', 'qd-memberid'); ?></button></p> -->  

    <?php else: ?>
        <p><?php esc_html_e(
            'Du hast aktuell keinen Mitgliedsausweis. ',
            'qd-memberid'); ?></p>

        <p><?php esc_html_e(
            'Der Mitgliedsausweis ist ein Barcode, welchen du für die Selbsbedienungskasse im Laden benötigst. ',
            'qd-memberid'); ?></p>

        <!-- <p><button class="button" onclick="generateMemberID()"><?php esc_html_e('Mitgliedsausweis erstellen', 'qd-memberid'); ?></button></p> -->

    <?php endif; ?>
</div>

<script src="<?php echo plugins_url('js/JsBarcode.all.min.js', dirname(__FILE__)); ?>"></script>

<script>
    <?php if ($memberid): ?>
    jQuery(document).ready(function($) {
        JsBarcode("#barcode", "<?php echo esc_js($memberid); ?>", {
            format: "CODE128",
            width: 3,
            height: 120,
            displayValue: true
        });
    });
    <?php endif; ?>

    function deleteMemberID() {
        if (!confirm('<?php esc_html_e('Bist du sicher, dass du deinen Mitgliedsausweis löschen möchtest?', 'qd-memberid'); ?>')) {
            return;
        }

        const button = document.querySelector('button[onclick="deleteMemberID()"]');
        button.disabled = true;
        button.textContent = '<?php esc_html_e('Wird gelöscht...', 'qd-memberid'); ?>';

        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'delete_member_id',
                nonce: '<?php echo $delete_nonce; ?>'
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('<?php esc_html_e('Fehler beim Löschen des Mitgliedsausweises.', 'qd-memberid'); ?>');
                    button.disabled = false;
                    button.textContent = '<?php esc_html_e('Mitgliedsausweis löschen', 'qd-memberid'); ?>';
                }
            },
            error: function() {
                alert('<?php esc_html_e('Fehler beim Löschen des Mitgliedsausweises.', 'qd-memberid'); ?>');
                button.disabled = false;
                button.textContent = '<?php esc_html_e('Mitgliedsausweis löschen', 'qd-memberid'); ?>';
            }
        });
    }

    function generateMemberID() {
        if (!confirm('<?php esc_html_e('Möchtest du einen neuen Mitgliedsausweis erstellen?', 'qd-memberid'); ?>')) {
            return;
        }

        const button = document.querySelector('button[onclick="generateMemberID()"]');
        button.disabled = true;
        button.textContent = '<?php esc_html_e('Wird erstellt...', 'qd-memberid'); ?>';

        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: { 
                action: 'generate_member_id',
                nonce: '<?php echo $generate_nonce; ?>'
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data || '<?php esc_html_e('Fehler beim Erstellen des Mitgliedsausweises.', 'qd-memberid'); ?>');
                    button.disabled = false;
                    button.textContent = '<?php esc_html_e('Mitgliedsausweis erstellen', 'qd-memberid'); ?>';
                }
            },
            error: function() {
                alert('<?php esc_html_e('Fehler beim Erstellen des Mitgliedsausweises.', 'qd-memberid'); ?>');
                button.disabled = false;
                button.textContent = '<?php esc_html_e('Mitgliedsausweis erstellen', 'qd-memberid'); ?>';
            }
        });
    }
</script>

<style>
.memberid-barcode {
    margin: 20px 0;
    text-align: center;
    background-color: white;
}
.memberid-barcode svg {
    max-width: 100%;
    height: auto;
    margin-top: 60px;
    margin-bottom: 40px;
}
</style>