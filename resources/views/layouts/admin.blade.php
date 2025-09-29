<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - {{ $siteSettings['site_name'] }}</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Dynamic Favicon -->
        <!-- KaTeX CSS & JS (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
         renderKaTeX();
        });
        
        function renderKaTeX() {
          const elements = document.querySelectorAll('.question-field, .editable-field, .fib-content, .mcq-option, .math-render');
          elements.forEach(element => {
            renderMathInElement(element, {
              delimiters: [
              {left: '$$', right: '$$', display: true},
              {left: '$', right: '$', display: false},
              {left: '\\(', right: '\\)', display: false},
              {left: '\\[', right: '\\]', display: true}
              ],
              throwOnError: false
            });
          });
        }
    </script>
    @if(isset($siteSettings['favicon']) && $siteSettings['favicon'])
    <link rel="icon" type="image/x-icon" href="{{ asset($siteSettings['favicon']) }}">
    <link rel="shortcut icon" href="{{ asset($siteSettings['favicon']) }}">
    @endif

    <!-- Dynamic Theme CSS Variables from Database -->
    <style>
        :root {
            /* Colors from configs table */
            --primary-color: {{ $siteSettings['main_color'] ?? '#960000' }};
            --black-color: {{ $siteSettings['black_color'] ?? '#121212' }};
            --white-color: {{ $siteSettings['white_color'] ?? '#EFEFEF' }};
            --secondary-color: {{ $siteSettings['secondary_color'] ?? '#FFBF66' }};
            --tertiary-color: {{ $siteSettings['tertiary_color'] ?? '#50D200' }};
            --success-color: {{ $siteSettings['success_color'] ?? '#50D200' }};
            --error-color: {{ $siteSettings['error_color'] ?? '#D80000' }};
            --warning-color: {{ $siteSettings['warning_color'] ?? '#FFBF66' }};
            --info-color: {{ $siteSettings['info_color'] ?? '#6D6D6D' }};
            
            /* Typography from configs table */
            --primary-font: {{ $siteSettings['primary_font'] ?? 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif' }};
            --secondary-font: {{ $siteSettings['secondary_font'] ?? 'Georgia, "Times New Roman", Times, serif' }};
            --body-font-size: {{ $siteSettings['body_font_size'] ?? '16px' }};
            --h1-font-size: {{ $siteSettings['h1_font_size'] ?? '32px' }};
            --h2-font-size: {{ $siteSettings['h2_font_size'] ?? '24px' }};
            --h3-font-size: {{ $siteSettings['h3_font_size'] ?? '20px' }};
            --h4-font-size: {{ $siteSettings['h4_font_size'] ?? '18px' }};
            --h5-font-size: {{ $siteSettings['h5_font_size'] ?? '16px' }};
            --body-line-height: {{ $siteSettings['body_line_height'] ?? '1.5' }};
            --heading-line-height: {{ $siteSettings['heading_line_height'] ?? '1.2' }};
            --font-weight-normal: {{ $siteSettings['font_weight_normal'] ?? '400' }};
            --font-weight-medium: {{ $siteSettings['font_weight_medium'] ?? '500' }};
            --font-weight-bold: {{ $siteSettings['font_weight_bold'] ?? '600' }};
            
            /* Layout from configs table */
            --border-radius: {{ $siteSettings['border_radius'] ?? '8px' }};
            --sidebar-width: {{ $siteSettings['sidebar_width'] ?? '280px' }};
            --content-max-width: {{ $siteSettings['content_max_width'] ?? '1400px' }};
        }
        
        /* Login background if applicable */
        @if(isset($siteSettings['login_background']) && $siteSettings['login_background'] && request()->routeIs('login'))
        body {
            background-image: url('{{ asset($siteSettings['login_background']) }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        @endif
    </style>
    
    <!-- Custom Admin Styles -->
    <link rel="stylesheet" href="{{ asset('css/admin-styles.css') }}">

    @stack('styles')
</head>
<body>
    <!-- Main Wrapper -->
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="{{ route('admin.dashboard.index') }}" class="logo">
                    @if(isset($siteSettings['site_logo']) && $siteSettings['site_logo'] && file_exists(public_path($siteSettings['site_logo'])))
                    <img src="{{ asset($siteSettings['site_logo']) }}" alt="Logo" class="site-logo" style="filter: brightness(0) invert(1); height: 32px; width: auto;">
                    @else
                    <i class="fas fa-graduation-cap" style="color: white; font-size: 32px;"></i>
                    @endif
                    <span class="nav-text">{{ $siteSettings['site_shortname'] ?? 'All Gifted' }}</span>
                </a>
            </div>
            <div class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard.*') ? 'active' : '' }}" href="{{ route('admin.dashboard.index') }}">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>

                    <li class="nav-divider">
                        <span>Content Management</span>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.fields.*') ? 'active' : '' }}" href="{{ route('admin.fields.index') }}">
                            <i class="fas fa-tags"></i>
                            <span class="nav-text">Fields</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.skills.*') ? 'active' : '' }}" href="{{ route('admin.skills.index') }}">
                            <i class="fas fa-brain"></i>
                            <span class="nav-text">Skills</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.tracks.*') ? 'active' : '' }}" href="{{ route('admin.tracks.index') }}">
                            <i class="fas fa-route"></i>
                            <span class="nav-text">Tracks</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}" href="{{ route('admin.questions.index') }}">
                            <i class="fas fa-question-circle"></i>
                            <span class="nav-text">Questions</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.assets.*') ? 'active' : '' }}" href="{{ route('admin.assets.index') }}">
                            <i class="fas fa-folder-open"></i>
                            <span class="nav-text">Assets</span>
                        </a>
                    </li>                    
                    <li class="nav-divider">
                        <span>Quality Assurance</span>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.qa.*') ? 'active' : '' }}" href="{{ route('admin.qa.index') }}">
                            <i class="fas fa-clipboard-check"></i>
                            <span class="nav-text">QA Review</span>
                        </a>
                    </li>

                    <li class="nav-divider">
                        <span>User Management</span>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                            <i class="fas fa-users"></i>
                            <span class="nav-text">Users</span>
                        </a>
                    </li>

                    <li class="nav-divider">
                        <span>System</span>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.configuration.*') ? 'active' : '' }}" href="{{ route('admin.configuration.index') }}">
                            <i class="fas fa-signal"></i>
                            <span class="nav-text">Configuration</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.levels.*') || request()->routeIs('admin.statuses.*') || request()->routeIs('admin.difficulties.*') || request()->routeIs('admin.question-types.*') ? 'active' : '' }}" href="{{ route('admin.settings.general') }}">
                            <i class="fas fa-cogs"></i>
                            <span class="nav-text">Settings</span>
                        </a>
                    </li>

                    <li class="nav-divider">
                        <span>Analytics</span>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.usage') }}">
                            <i class="fas fa-chart-bar"></i>
                            <span class="nav-text">Reports</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content Wrapper -->
        <div class="main-wrapper">
            <!-- Header -->
            <header class="main-header">
                <div class="navbar-custom">
                    <button class="btn btn-link sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="navbar-nav ms-auto">
                        <!-- User Dropdown -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle user-dropdown" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                {{ auth()->user()->firstname ?? 'Admin' }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#">
                                        <i class="fas fa-user me-2"></i>Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.settings.general') }}">
                                        <i class="fas fa-cogs me-2"></i>Settings
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('auth.logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Flash Messages -->
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                {{-- Include page header if data is provided --}}
                @if(isset($pageHeader))
                @include('admin.components.page-header', $pageHeader)
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for AJAX functionality) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Make site settings available to JavaScript -->
    <script>
        // Global site settings for JavaScript
        window.siteSettings = {
            siteName: @json($siteSettings['site_name']),
            siteShortname: @json($siteSettings['site_shortname']),
            mainColor: @json($siteSettings['main_color']),
            secondaryColor: @json($siteSettings['secondary_color'] ?? '#FFBF66'),
            email: @json($siteSettings['email']),
            timezone: @json($siteSettings['timezone']),
            dateFormat: @json($siteSettings['date_format']),
            timeFormat: @json($siteSettings['time_format']),
            // Theme settings
            primaryFont: @json($siteSettings['primary_font']),
            bodyFontSize: @json($siteSettings['body_font_size']),
            borderRadius: @json($siteSettings['border_radius']),
        };

        // Dynamic logo color update (if needed via JavaScript)
        function updateLogoColor(newColor) {
            const logoElements = document.querySelectorAll('.site-logo path, .site-logo rect');
            logoElements.forEach(element => {
                element.style.fill = newColor;
            });
        }
    </script>

    <!-- Admin JavaScript -->
    <script>
        // CSRF Token setup for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.querySelector('.main-wrapper').classList.toggle('expanded');
            
            // Save state to localStorage
            const isCollapsed = document.getElementById('sidebar').classList.contains('collapsed');
            localStorage.setItem('sidebar-collapsed', isCollapsed);
        });

        // Restore sidebar state
        document.addEventListener('DOMContentLoaded', function() {
            const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            if (isCollapsed) {
                document.getElementById('sidebar').classList.add('collapsed');
                document.querySelector('.main-wrapper').classList.add('expanded');
            }
        });

        // Toast notifications
        function showToast(message, type = 'info') {
            const toast = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            </div>
            `;
            
            // Create toast container if it doesn't exist
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                toastContainer.style.zIndex = '9999';
                document.body.appendChild(toastContainer);
            }
            
            toastContainer.insertAdjacentHTML('beforeend', toast);
            
            const toastElement = toastContainer.lastElementChild;
            const bsToast = new bootstrap.Toast(toastElement);
            bsToast.show();
            
            // Remove toast element after it's hidden
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }

        // Loading button states
        function setLoadingState(button, isLoading = true) {
            if (isLoading) {
                button.disabled = true;
                button.dataset.originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            } else {
                button.disabled = false;
                button.innerHTML = button.dataset.originalText;
            }
        }

        // Confirm delete functionality
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        // Helper function to format dates according to site settings
        function formatDate(date, format = null) {
            format = format || window.siteSettings.dateFormat;
            // Convert PHP date format to JavaScript format
            const formatMap = {
                'd/m/Y': 'DD/MM/YYYY',
                'm/d/Y': 'MM/DD/YYYY',
                'Y-m-d': 'YYYY-MM-DD',
                'd-m-Y': 'DD-MM-YYYY'
            };
            // Simple date formatting (you can enhance this with a library like moment.js)
            return new Date(date).toLocaleDateString();
        }
// Global Admin Data Management
        // Get data from session and store in localStorage
        const globalData = @json(session('globalAdminData', []));
        
        if (Object.keys(globalData).length > 0) {
            localStorage.setItem('adminGlobalData', JSON.stringify({
                data: globalData,
                timestamp: Date.now()
            }));
        }
        
        // Function to get data from localStorage
        function getGlobalAdminData() {
            const stored = localStorage.getItem('adminGlobalData');
            return stored ? JSON.parse(stored).data : {};
        }
        
        // Function to update specific lookup table
        function updateLookupTable(tableName, newData) {
            const stored = localStorage.getItem('adminGlobalData');
            if (stored) {
                const adminData = JSON.parse(stored);
                adminData.data[tableName] = newData;
                adminData.timestamp = Date.now();
                localStorage.setItem('adminGlobalData', JSON.stringify(adminData));
                
                // Re-populate dropdowns
                populateGlobalDropdowns();
                
                console.log(`Updated ${tableName} in localStorage`);
            }
        }
        
        // Global function to refresh a lookup table from server
        async function refreshLookupTable(tableName) {
            try {
                const response = await fetch(`/admin/api/lookup/${tableName}`, {
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                
                if (response.ok) {
                    const newData = await response.json();
                    updateLookupTable(tableName, newData);
                    
                    showToast(`${tableName.charAt(0).toUpperCase() + tableName.slice(1)} refreshed successfully`, 'success');
                } else {
                    console.error(`Failed to refresh ${tableName}:`, response.statusText);
                }
            } catch (error) {
                console.error(`Failed to refresh ${tableName}:`, error);
            }
        }
        
        // Function to populate all global dropdowns
        function populateGlobalDropdowns() {
            const data = getGlobalAdminData();
            
            const dropdownConfigs = {
                'statuses': { data: data.statuses, value: 'id', label: 'status' },
                'qa-statuses': { data: data.qa_statuses, value: 'key', label: 'value' },
                'difficulties': { data: data.difficulties, value: 'id', label: 'short_description' },
                'fields': { data: data.fields, value: 'id', label: 'field' },
                'levels': { data: data.levels, value: 'id', label: 'level' },
                'tracks': { data: data.tracks, value: 'id', label: 'track' },
                'skills': { data: data.skills, value: 'id', label: 'skill' },
                'types': { data: data.types, value: 'id', label: 'type' }
            };
            
            Object.entries(dropdownConfigs).forEach(([type, config]) => {
                document.querySelectorAll(`[data-populate="${type}"]`).forEach(dropdown => {
                    const currentValue = dropdown.value;
                    dropdown.innerHTML = `<option value="">All ${type.charAt(0).toUpperCase() + type.slice(1)}</option>`;
                    
                    if (config.data) {
                        if (type === 'qa-statuses') {
                            Object.entries(config.data).forEach(([value, label]) => {
                                const selected = currentValue === value ? 'selected' : '';
                                dropdown.innerHTML += `<option value="${value}" ${selected}>${label}</option>`;
                            });
                        } else {
                            config.data.forEach(item => {
                                const selected = currentValue == item[config.value] ? 'selected' : '';
                                dropdown.innerHTML += `<option value="${item[config.value]}" ${selected}>${item[config.label]}</option>`;
                            });
                        }
                    }
                });
            });
        }
        
        // Call this after creating/updating/deleting lookup records
        function handleLookupTableChange(tableName) {
            refreshLookupTable(tableName);
        }
        
        // Auto-populate dropdowns when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Add a small delay to ensure all DOM elements are ready
            setTimeout(populateGlobalDropdowns, 100);
        });
        
        // Debug function to check what's in localStorage
        function debugGlobalData() {
            console.log('Global Admin Data:', getGlobalAdminData());
        }

    </script>

    @stack('scripts')
</body>
</html>