        // Store screen width in a cookie and reload the page if it changes
        function updateScreenWidth() {
            const screenWidth = window.innerWidth;
            document.cookie = `screen_width=${screenWidth}; path=/`;
            location.reload();
        }

        // Check if screen width is stored in a cookie
        if (!document.cookie.includes('screen_width')) {
            updateScreenWidth();
        }

        // Add an event listener to detect screen size changes
        window.addEventListener('resize', () => {
            clearTimeout(window.resizeTimeout);
            window.resizeTimeout = setTimeout(updateScreenWidth, 500); // Debounce to avoid excessive reloads
        });