            </main>
            <footer class="footer">
                <p>&copy; 2026 <?php echo htmlspecialchars(getAppName()); ?> - Email Reminder System</p>
                <p>Dev by Jeremías Palazzesi</p>
            </footer>
        </div>
    </div>
    
    <?php if (isset($_SESSION['toast'])): ?>
    <div class="toast <?php echo $_SESSION['toast']['type']; ?>" id="toast">
        <?php echo htmlspecialchars($_SESSION['toast']['message']); ?>
    </div>
    <?php unset($_SESSION['toast']); endif; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/lang/summernote-es-ES.min.js"></script>
    <script src="/shooter/assets/js/main.js"></script>
</body>
</html>
