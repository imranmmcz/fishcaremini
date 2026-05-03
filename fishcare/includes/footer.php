<?php
/**
 * Fish Care System - Footer
 */
?>
<footer class="main-footer" style="background: rgba(15, 23, 42, 0.95); border-top: 1px solid var(--border-color); padding: 20px; text-align: center; margin-top: 50px;">
    <p style="color: var(--text-secondary); margin: 0; font-size: 14px;">
        &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME_BN; ?>। সর্বস্বত্ব সংরক্ষিত।
    </p>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Main JS -->
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>

<?php if (isset($extraScripts)): ?>
<?php echo $extraScripts; ?>
<?php endif; ?>

</body>
</html>
