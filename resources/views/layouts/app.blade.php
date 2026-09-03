{{-- 
    ============================================================
    LAYOUT PRINCIPAL - APP
    ============================================================
    Este es el layout base de toda la aplicación.
    Contiene:
    - Sidebar (menú lateral) con todos los módulos
    - Top bar (navbar superior)
    - Área de contenido principal (@yield('content'))
    - Estilos y scripts globales
    - 🔥 Enlace a "Remuneraciones" en el menú lateral
--}}

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard MINEDUC</title>
    
    {{-- MDBootstrap (diseño profesional) --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet">
    
    {{-- Font Awesome (iconos) --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    {{-- Chart.js (gráficos) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* ============================================================
                   RESET Y BASE
                   ============================================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        
        /* ============================================================
                   SIDEBAR (Menú lateral)
                   ============================================================ */
        .sidebar {
            width: 280px;
            min-height: 100vh;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            flex-shrink: 0;
        }
        .sidebar .logo {
            padding: 25px 20px;
            font-size: 22px;
            font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar .logo i {
            color: #3b82f6;
        }
        .sidebar nav {
            padding: 20px 15px;
        }
        .sidebar nav a {
            color: #a0aec0;
            text-decoration: none;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-radius: 10px;
            transition: all 0.3s;
            margin-bottom: 4px;
            font-size: 15px;
        }
        .sidebar nav a:hover {
            color: white;
            background: rgba(255,255,255,0.08);
        }
        .sidebar nav a.active {
            color: white;
            background: #3b82f6;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .sidebar nav a i {
            width: 20px;
            text-align: center;
        }
        
        /* Scrollbar personalizada */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
        }
        
        /* ============================================================
                   MAIN CONTENT
                   ============================================================ */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            transition: all 0.3s ease;
            flex: 1;
            width: calc(100% - 280px);
        }
        
        /* ============================================================
                   TOP BAR (Barra superior)
                   ============================================================ */
        .top-bar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .top-bar .page-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
        }
        .top-bar .user-area {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .top-bar .user-area .badge-notification {
            background: #ef4444;
            color: white;
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 11px;
            position: relative;
            top: -10px;
            left: -8px;
        }
        
        /* ============================================================
                   CONTENT AREA
                   ============================================================ */
        .content-area {
            padding: 25px 30px;
            max-width: 100%;
            width: 100%;
        }
        .content-area .row {
            margin-left: 0;
            margin-right: 0;
        }
        .content-area .row > * {
            padding-left: 12px;
            padding-right: 12px;
        }
        
        /* ============================================================
                   CARDS
                   ============================================================ */
        .card-hover {
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            border-radius: 12px;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
        }
        .card-hover .card-body {
            padding: 20px 22px;
        }
        .card-hover h6 {
            font-size: 14px;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        .card-hover h2 {
            font-size: 28px;
            font-weight: 700;
        }
        .card-hover small {
            font-size: 13px;
            opacity: 0.85;
        }
        
        /* ============================================================
                   TABLA
                   ============================================================ */
        .table-responsive {
            overflow-x: auto;
        }
        .table th, .table td {
            white-space: nowrap;
            vertical-align: middle;
        }
        .table thead th {
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table tfoot td {
            font-weight: 700;
            font-size: 14px;
        }
        
        /* ============================================================
                   BOTONES
                   ============================================================ */
        .btn-importar {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .btn-importar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
            color: white;
        }
        
        /* ============================================================
                   RESPONSIVE
                   ============================================================ */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #1a1a2e;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
                position: fixed;
            }
            .sidebar.open {
                width: 280px;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            .sidebar-toggle {
                display: block;
            }
            .top-bar {
                padding: 12px 18px;
            }
            .content-area {
                padding: 15px;
            }
            .content-area .row > * {
                padding-left: 8px;
                padding-right: 8px;
            }
            .card-hover h2 {
                font-size: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .content-area {
                padding: 10px;
            }
            .top-bar .page-title {
                font-size: 15px;
            }
            .card-hover .card-body {
                padding: 15px;
            }
            .card-hover h2 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        
        <!-- ============================================================
        SIDEBAR - MENÚ LATERAL
        ============================================================ -->
        <div class="sidebar" id="sidebar">
            <!-- Logo -->
            <div class="logo">
                <i class="fas fa-chart-bar"></i>
                <span>MINEDUC</span>
            </div>
            
            <!-- Navegación -->
            <nav>
                {{-- 🔹 INICIO --}}
                <a href="{{ route('dashboard') }}" class="active">
                    <i class="fas fa-home"></i> Inicio
                </a>
                
                {{-- 🔹 IMPORTAR --}}
                <a href="{{ route('importar.form') }}">
                    <i class="fas fa-upload"></i> Importar
                </a>
                
                {{-- 🔹 REMUNERACIONES (NUEVO MÓDULO) --}}
                <a href="{{ route('remuneraciones.index') }}">
                    <i class="fas fa-money-bill-wave"></i> Remuneraciones
                </a>
                
                {{-- 🔹 DATOS --}}
                <a href="{{ route('datos') }}">
                    <i class="fas fa-table"></i> Datos
                </a>
                
                {{-- 🔹 REPORTES --}}
                <a href="#">
                    <i class="fas fa-chart-line"></i> Reportes
                </a>
                
                {{-- 🔹 CONFIGURACIÓN --}}
                <a href="#">
                    <i class="fas fa-cog"></i> Configuración
                </a>
            </nav>
        </div>

        <!-- ============================================================
        MAIN CONTENT
        ============================================================ -->
        <div class="main-content">
            
            <!-- TOP BAR -->
            <div class="top-bar">
                <div>
                    {{-- Botón para abrir/cerrar sidebar en móvil --}}
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span class="page-title ms-2">Dashboard</span>
                </div>
                <div class="user-area">
                    {{-- Notificaciones --}}
                    <div style="position: relative; cursor: pointer;">
                        <i class="fas fa-bell fs-5"></i>
                        <span class="badge-notification">3</span>
                    </div>
                    {{-- Perfil de usuario --}}
                    <i class="fas fa-user-circle fs-4" style="cursor: pointer;"></i>
                </div>
            </div>

            <!-- CONTENIDO PRINCIPAL -->
            <div class="content-area">
                {{-- Aquí se inyecta el contenido de cada vista --}}
                @yield('content')
            </div>
        </div>
    </div>

    <!-- ============================================================
    SCRIPTS GLOBALES
    ============================================================ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>
    
    <script>
        /**
         * ============================================================
         * FUNCIÓN: toggleSidebar()
         * ============================================================
         * Abre o cierra el menú lateral en dispositivos móviles.
         */
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        /**
         * ============================================================
         * CIERRE AUTOMÁTICO DEL SIDEBAR
         * ============================================================
         * En móviles, al hacer clic fuera del sidebar, se cierra.
         */
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            
            // Solo en pantallas pequeñas (<= 768px)
            if (window.innerWidth <= 768) {
                // Si el clic fue fuera del sidebar y fuera del botón toggle
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
        
        /**
         * ============================================================
         * DETECTAR PÁGINA ACTIVA EN EL MENÚ
         * ============================================================
         * Marca el enlace del menú como "active" según la URL actual.
         */
        document.addEventListener('DOMContentLoaded', function() {
            const currentUrl = window.location.pathname;
            const links = document.querySelectorAll('.sidebar nav a');
            
            links.forEach(link => {
                const href = link.getAttribute('href');
                // Si el href coincide con la URL actual (o es la raíz)
                if (href === currentUrl || (href === '/' && currentUrl === '/')) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });
    </script>
    
    {{-- Stack de scripts para vistas hijas --}}
    @stack('scripts')
    
</body>
</html>