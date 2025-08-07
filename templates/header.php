<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=800">
    <title>موقع أجاوب</title>
    <link rel="icon" href="https://i.ibb.co/bMGxQ9Bh/5.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/footer.css?v=<?= time() ?>">
    <link rel="stylesheet" href="assets/css/light-bulb.css?v=<?= time() ?>">
    <script>
        // FOUC preventer
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();

        // Password Reset Redirect Handler
        (function() {
            // First, get the current view from the URL's query parameters.
            const currentUrlParams = new URLSearchParams(window.location.search);
            const currentView = currentUrlParams.get('view');

            // Check if a recovery token exists in the URL fragment.
            if (window.location.hash) {
                const hashParams = new URLSearchParams(window.location.hash.substring(1));
                if (hashParams.has('access_token') && hashParams.get('type') === 'recovery') {
                    // ONLY redirect if we are NOT already on the reset_password page.
                    // This prevents an infinite redirect loop.
                    if (currentView !== 'reset_password') {
                        window.location.replace('?view=reset_password' + window.location.hash);
                    }
                }
                
                // Handle email verification redirect
                if (hashParams.has('access_token') && hashParams.get('type') === 'signup') {
                    if (currentView !== 'email_verified') {
                        // Clear the hash to avoid loops and clean the URL, then redirect.
                        window.location.replace('?view=email_verified');
                    }
                }
            }
        })();
    </script>
</head>
<body>
    <div class="theme-switch-container">
        <svg class="toggle-scene" xmlns="http://www.w3.org/2000/svg" preserveaspectratio="xMinYMin" viewBox="0 0 197.451 481.081">
            <defs>
                <marker id="e" orient="auto" overflow="visible" refx="0" refy="0">
                <path class="toggle-scene__cord-end" fill-rule="evenodd" stroke-width=".2666" d="M.98 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                </marker>
                <marker id="d" orient="auto" overflow="visible" refx="0" refy="0">
                <path class="toggle-scene__cord-end" fill-rule="evenodd" stroke-width=".2666" d="M.98 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                </marker>
                <marker id="c" orient="auto" overflow="visible" refx="0" refy="0">
                <path class="toggle-scene__cord-end" fill-rule="evenodd" stroke-width=".2666" d="M.98 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                </marker>
                <marker id="b" orient="auto" overflow="visible" refx="0" refy="0">
                <path class="toggle-scene__cord-end" fill-rule="evenodd" stroke-width=".2666" d="M.98 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                </marker>
                <marker id="a" orient="auto" overflow="visible" refx="0" refy="0">
                <path class="toggle-scene__cord-end" fill-rule="evenodd" stroke-width=".2666" d="M.98 0a1 1 0 11-2 0 1 1 0 012 0z"></path>
                </marker>
                <clippath id="g" clippathunits="userSpaceOnUse">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4.677" d="M-774.546 827.629s12.917-13.473 29.203-13.412c16.53.062 29.203 13.412 29.203 13.412v53.6s-8.825 16-29.203 16c-21.674 0-29.203-16-29.203-16z"></path>
                </clippath>
                <clippath id="f" clippathunits="userSpaceOnUse">
                <path d="M-868.418 945.051c-4.188 73.011 78.255 53.244 150.216 52.941 82.387-.346 98.921-19.444 98.921-47.058 0-27.615-4.788-42.55-73.823-42.55-69.036 0-171.436-30.937-175.314 36.667z"></path>
                </clippath>
            </defs>
            <g class="toggle-scene__cords">
                <path class="toggle-scene__cord" marker-end="url(#a)" fill="none" stroke-linecap="square" stroke-width="6" d="M123.228-28.56v150.493" transform="translate(-24.503 256.106)"></path>
                <path class="toggle-scene__cord" marker-end="url(#a)" fill="none" stroke-linecap="square" stroke-width="6" d="M123.228-28.59s28 8.131 28 19.506-18.667 13.005-28 19.507c-9.333 6.502-28 8.131-28 19.506s28 19.507 28 19.507" transform="translate(-24.503 256.106)"></path>
                <path class="toggle-scene__cord" marker-end="url(#a)" fill="none" stroke-linecap="square" stroke-width="6" d="M123.228-28.575s-20 16.871-20 28.468c0 11.597 13.333 18.978 20 28.468 6.667 9.489 20 16.87 20 28.467 0 11.597-20 28.468-20 28.468" transform="translate(-24.503 256.106)"></path>
                <path class="toggle-scene__cord" marker-end="url(#a)" fill="none" stroke-linecap="square" stroke-width="6" d="M123.228-28.569s16 20.623 16 32.782c0 12.16-10.667 21.855-16 32.782-5.333 10.928-16 20.623-16 32.782 0 12.16 16 32.782 16 32.782" transform="translate(-24.503 256.106)"></path>
                <path class="toggle-scene__cord" marker-end="url(#a)" fill="none" stroke-linecap="square" stroke-width="6" d="M123.228-28.563s-10 24.647-10 37.623c0 12.977 6.667 25.082 10 37.623 3.333 12.541 10 24.647 10 37.623 0 12.977-10 37.623-10 37.623" transform="translate(-24.503 256.106)"></path>
                <g class="line toggle-scene__dummy-cord">
                <line marker-end="url(#a)" x1="98.7255" x2="98.7255" y1="240.5405" y2="380.5405"></line>
                </g>
                <circle class="toggle-scene__hit-spot" cx="98.7255" cy="380.5405" r="60" fill="transparent"></circle>
            </g>
            <g id="theme-bulb" class="toggle-scene__bulb bulb" transform="translate(844.069 -645.213)">
                <path class="bulb__cap" stroke-linecap="round" stroke-linejoin="round" stroke-width="4.677" d="M-774.546 827.629s12.917-13.473 29.203-13.412c16.53.062 29.203 13.412 29.203 13.412v53.6s-8.825 16-29.203 16c-21.674 0-29.203-16-29.203-16z"></path>
                <path class="bulb__cap-shine" d="M-778.379 802.873h25.512v118.409h-25.512z" clip-path="url(#g)" transform="matrix(.52452 0 0 .90177 -368.282 82.976)"></path>
                <path class="bulb__cap" stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M-774.546 827.629s12.917-13.473 29.203-13.412c16.53.062 29.203 13.412 29.203 13.412v0s-8.439 10.115-28.817 10.115c-21.673 0-29.59-10.115-29.59-10.115z"></path>
                <path class="bulb__cap-outline" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="4.677" d="M-774.546 827.629s12.917-13.473 29.203-13.412c16.53.062 29.203 13.412 29.203 13.412v53.6s-8.825 16-29.203 16c-21.674 0-29.203-16-29.203-16z"></path>
                <g class="bulb__filament" fill="none" stroke-linecap="round" stroke-width="5">
                <path d="M-752.914 823.875l-8.858-33.06"></path>
                <path d="M-737.772 823.875l8.858-33.06"></path>
                </g>
                <path class="bulb__bulb" stroke-linecap="round" stroke-width="5" d="M-783.192 803.855c5.251 8.815 5.295 21.32 13.272 27.774 12.299 8.045 36.46 8.115 49.127 0 7.976-6.454 8.022-18.96 13.273-27.774 3.992-6.7 14.408-19.811 14.408-19.811 8.276-11.539 12.769-24.594 12.769-38.699 0-35.898-29.102-65-65-65-35.899 0-65 29.102-65 65 0 13.667 4.217 26.348 12.405 38.2 0 0 10.754 13.61 14.746 20.31z"></path>
                <circle class="bulb__flash" cx="-745.343" cy="743.939" r="83.725" fill="none" stroke-dasharray="10,30" stroke-linecap="round" stroke-linejoin="round" stroke-width="10"></circle>
                <path class="bulb__shine" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="12" d="M-789.19 757.501a45.897 45.897 0 013.915-36.189 45.897 45.897 0 0129.031-21.957"></path>
            </g>
        </svg>
    </div>

    <nav class="navbar">
        <div class="nav-container">
            <a href="?view=home" class="logo">🎓 موقع أٌجاوب</a>
            
            <ul class="nav-links">
                <li><a href="?view=home" class="<?= $current_view == 'home' ? 'active' : '' ?>">الرئيسية</a></li>
                <li><a href="?view=courses" class="<?= $current_view == 'courses' ? 'active' : '' ?>">الاختبارات </a></li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li><a href="?view=profile" class="<?= $current_view == 'profile' ? 'active' : '' ?>">ملفي الشخصي</a></li>
                    <li><a href="?logout=1" class="logout-btn">تسجيل خروج</a></li>
                <?php else: ?>
                    <li><a href="?view=login" class="<?= $current_view == 'login' ? 'active' : '' ?>">تسجيل دخول</a></li>
                    <li><a href="?view=register" class="<?= $current_view == 'register' ? 'active' : '' ?>">حساب جديد</a></li>
                <?php endif; ?>
            </ul>
            
            <button class="nav-toggle" aria-label="toggle navigation">
                <span class="bar"></span><span class="bar"></span><span class="bar"></span>
            </button>
        </div>
    </nav>
    <main class="main-content">
