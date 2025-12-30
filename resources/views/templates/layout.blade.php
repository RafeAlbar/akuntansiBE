<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- PWA  -->

    <meta name="theme-color" content="#6777ef" />
    <link rel="icon" type="image/png" href="https://laravel.com/img/logomark.min.svg">
    <link rel="apple-touch-icon" href="https://laravel.com/img/logomark.min.svg">
    {{-- <link rel="manifest" href="{{ asset('/manifest.json') }}"> --}}
    <title>SmartKeuangan</title>

    <!-- Google Font: Source Sans Pro -->
    <style>
        @font-face {
            font-family: 'Source Sans 3';
            src: url("{{ asset('lte/font/SourceSans3-Italic-VariableFont_wght.ttf') }}") format('truetype');
            font-style: italic;
            font-weight: 400;
            font-display: swap;
        }

        body {
            font-family: 'Source Sans 3', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
    </style>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ asset('lte/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="{{ asset('lte/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('lte/dist/css/adminlte.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('lte/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('lte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="{{ asset('lte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('lte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('lte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('lte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet"
        href="{{ asset('lte/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('lte/plugins/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('lte/css/app.css') }}">
    <!-- Bootstrap4 Duallistbox -->
    <link rel="stylesheet" href="{{ asset('lte/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css') }}">
    <!-- daterange picker -->
    <link rel="stylesheet" href="{{ asset('lte/plugins/daterangepicker/daterangepicker.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('lte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    {{-- JQuery --}}
    <script src="{{ asset('lte/plugins/jquery/jquery-3.6.0.min.js') }}"></script>

</head>
<style>
    /* Webkit-based browsers: Chrome, Safari, Edge */
    body::-webkit-scrollbar {
        width: 12px !important;
        background-color: #1a1a1a !important;
    }

    body::-webkit-scrollbar-track {
        background-color: transparent;
        /* Background transparan */
    }

    body::-webkit-scrollbar-thumb {
        background-color: #3a3a3a !important;
        border-radius: 10px !important;
        border: 2px solid #1a1a1a !important;
    }

    body::-webkit-scrollbar-thumb:hover {
        background-color: #555 !important;
        height: 36px;
        /* Menyesuaikan posisi panah dropdown */
    }

    body.dark-mode {
        background-color: #121212;
        color: white;
    }

    .navbar.dark-mode {
        background-color: #333;
        color: white;
    }

    .form-control-navbar.dark-mode {
        background-color: #444;
        color: white;
    }

    .alert {
        padding: 15px;
        border-radius: 5px;
        font-size: 16px;
        color: #fff;
        opacity: 0.9;
        /* Menambah sedikit transparansi */
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        /* Tambahkan shadow untuk efek elegan */
    }

    .alert-success {
        background-color: rgba(40, 167, 69, 0.8);
        /* Hijau dengan transparansi */
    }

    .alert-danger {
        background-color: rgba(220, 53, 69, 0.8);
        /* Merah dengan transparansi */
    }

    .alert-warning {
        background-color: rgba(255, 193, 7, 0.8);
        /* Kuning dengan transparansi */
    }

    .alert-info {
        background-color: rgba(23, 162, 184, 0.8);
        /* Biru dengan transparansi */
    }

    .modal-xxl {
        max-width: 95%;
        /* Atur persentase atau ukuran sesuai kebutuhan */
    }

    .dataTables_wrapper .row {
        overflow-x: auto;
    }


    /* Sidebar Warna Custom  */

    /* Teks & ikon default */
    .nav-sidebar .nav-link {
        color: #1E5296 !important;
    }

    /* Hover */
    .nav-sidebar .nav-link:hover {
        background-color: rgba(30, 82, 150, 0.08) !important;
        color: #1E5296 !important;
    }

    /* Aktif (halaman yang sedang dibuka) */
    .nav-sidebar .nav-link.active {
        background-color: #1E5296 !important;
        /* latar biru */
        color: #fff !important;
        /* teks putih */
    }

    /* submenu aktif */


    .nav-sidebar .nav-link.active i,
    .nav-sidebar .nav-link i {
        color: inherit !important;
    }

    /* Submenu default */
    .nav-sidebar .nav-treeview .nav-link {
        background-color: #fff !important;
        /* putih/abu terang default */
        color: #1E5296 !important;
        /* biru teks */
    }

    /* Submenu hover */
    .nav-sidebar .nav-treeview .nav-link:hover {
        background-color: rgba(30, 82, 150, 0.08) !important;
        color: #1E5296 !important;
    }

    /* Submenu aktif */
    .nav-sidebar .nav-treeview .nav-link.active {
        background-color: #f0f0f0 !important;
        /* abu-abu muda */
        color: #d48806 !important;
        /* teks emas */
    }

    /* Icon bulatan submenu aktif */
    .nav-sidebar .nav-treeview .nav-link.active i {
        color: #d48806 !important;
        /* bulatan emas */
    }

    .main-sidebar .brand-link.brand-logo-wrap {
        height: 120px !important;
        padding: 10px 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        overflow: hidden !important;
    }

    .main-sidebar .brand-link.brand-logo-wrap .brand-logo-img {
        max-height: 150px !important;
        width: auto !important;
        object-fit: contain !important;
        display: block;
    }

    /* jarak antara logo dan search */
    .main-sidebar .sidebar.sidebar-gap {
        padding-top: 50px;
        /* ganti dari margin-top */
    }
</style>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a>
                </li>
                <!-- Dark Mode Button -->
                <li class="nav-item">
                    <a class="nav-link" href="#" role="button">
                        User
                    </a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger">Logout</button>
                    </form>
                </li>
            </ul>

        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-light-primary elevation-4">
            <!-- Brand Logo -->
            <a href="{{ url('/') }}" class="brand-link text-dark brand-logo-wrap">
                <img src="{{ asset('assets/img/logonobg.png') }}" alt="SmartKeuangan" class="brand-logo-img">
            </a>





            <!-- Sidebar -->
            <div class="sidebar sidebar-gap">


                <!-- SidebarSearch Form -->
                <div class="form-inline mt-3">
                    <div class="input-group" data-widget="sidebar-search">
                        <input class="form-control form-control-sidebar" type="search" placeholder="Search"
                            aria-label="Search">
                        <div class="input-group-append">
                            <button class="btn btn-sidebar">
                                <i class="fas fa-search fa-fw"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <li class="nav-item">
                            <a href="{{ route('dashboard.index') }}"
                                class="nav-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-chart-pie"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('buku_besar.index') }}"
                                class="nav-link {{ request()->routeIs('buku_besar.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-layer-group"></i>
                                <p>Setup</p>
                            </a>
                        </li>

                        <li
                            class="nav-item has-treeview {{ request()->routeIs('transaksi*') || request()->routeIs('inventaris.*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->routeIs('transaksi*') || request()->routeIs('inventaris.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-box-open"></i>
                                <p>
                                    Transaksi
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('inventaris.index') }}"
                                        class="nav-link {{ request()->routeIs('inventaris.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Penjualan / Pembelian</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('inventaris.kasbank') }}"
                                        class="nav-link {{ request()->routeIs('inventaris.kasbank') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Kas & Bank</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item has-treeview {{ request()->routeIs('laporan*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('laporan*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-coins"></i>
                                <p>
                                    Laporan
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('laporan_keuangan.index') }}"
                                        class="nav-link {{ request()->routeIs('laporan_keuangan.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Keuangan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('laporan_keuangan.jurnal') }}"
                                        class="nav-link {{ request()->routeIs('laporan_keuangan.jurnal') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Ringkasan Jurnal</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('laporan_keuangan.bukbes') }}"
                                        class="nav-link {{ request()->routeIs('laporan_keuangan.bukbes') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Buku Besar</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('buku_hutang.index') }}"
                                        class="nav-link {{ request()->routeIs('buku_hutang.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Buku Utang / Piutang</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('faktur.index') }}"
                                class="nav-link {{ request()->routeIs('faktur.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p>Faktur / Nota</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('bep.index') }}"
                                class="nav-link {{ request()->routeIs('bep.index') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>Break Event Point</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('pph.index') }}"
                                class="nav-link {{ request()->routeIs('pph.index') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>PPH</p>
                            </a>
                        </li>
                        <!-- <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-user"></i>
                                <p>User</p>
                            </a>
                        </li> -->
                    </ul>
                </nav>
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">@yield('breadcrumbs')</h1>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Info boxes -->
                    @yield('content')

                    <!-- /.row -->
                    <style>
                        input[type="radio"] {
                            display: none;
                        }

                        .kategori-label {
                            opacity: 0.5;
                        }

                        input[type="radio"]:checked+label {
                            box-shadow: 0 0 5px 2px #ddd;
                            opacity: 1;
                        }
                    </style>
                    <!-- /.row -->
                </div><!--/. container-fluid -->
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <!-- Control Sidebar -->
        <aside class="control-sidebar">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->

        <!-- Main Footer -->
        <footer class="main-footer">
            <strong>Copyright &copy; <a href="#">TrafficUp</a>.</strong>
            All rights reserved.
        </footer>
    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED SCRIPTS -->

    <!-- jQuery -->
    <script src="{{ asset('lte/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap -->
    <script src="{{ asset('lte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- overlayScrollbars -->
    <script src="{{ asset('lte/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('lte/dist/js/adminlte.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('lte/plugins/select2/js/select2.full.min.js') }}"></script>

    <!-- PAGE PLUGINS -->
    <!-- jQuery Mapael -->
    <script src="{{ asset('lte/plugins/jquery-mousewheel/jquery.mousewheel.js') }}"></script>
    <script src="{{ asset('lte/plugins/raphael/raphael.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/jquery-mapael/jquery.mapael.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/jquery-mapael/maps/usa_states.min.js') }}"></script>
    <!-- ChartJS -->
    <script src="{{ asset('lte/plugins/chart.js/Chart.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('lte/plugins/toastr/toastr.min.js') }}"></script>

    <!-- Page specific script -->
    <script>
        $(function() {
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })

            $("#example1").DataTable({
                "responsive": false,
                "lengthChange": false,
                "autoWidth": false,
                "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
            $('#example2').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "processing": true,
                "autoWidth": false,
                "responsive": true,
            })
            $('#example3').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
            })
            $('#example4').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
            });
            $('#example5').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "pageLength": 100, // Atur jumlah entri default ke 100
                "lengthMenu": [10, 25, 50, 100, 200] // Menyediakan opsi jumlah entri lainnya
            });
            $('#example6').DataTable({
                "paging": false,
                "lengthChange": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "autoWidth": false,
                "responsive": false,
                "pageLength": 100, // Atur jumlah entri default ke 100
                "lengthMenu": [10, 25, 50, 100, 200] // Menyediakan opsi jumlah entri lainnya
            });
            $('#rpt').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "autoWidth": false,
                "responsive": false,
                "pageLength": 200, // Atur jumlah entri default ke 100
                "lengthMenu": [200, 300, 400, 500, 600] // Menyediakan opsi jumlah entri lainnya
            });
            $('#example7').DataTable({
                "paging": false,
                "lengthChange": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "autoWidth": false,
                "responsive": false,
                "pageLength": 50, // Atur jumlah entri default ke 100
                "lengthMenu": [10, 25, 50, 100, 200] // Menyediakan opsi jumlah entri lainnya
            });
            $('#example8').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "autoWidth": false,
                "responsive": false,
                "pageLength": 20, // Atur jumlah entri default ke 100
                "lengthMenu": [20, 40, 60, 80, 100] // Menyediakan opsi jumlah entri lainnya
            });
            $('#example9').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": false,
                "info": true,
                "autoWidth": false,
                "responsive": false,
                "pageLength": 20, // Atur jumlah entri default ke 100
                "lengthMenu": [20, 40, 60, 80, 100] // Menyediakan opsi jumlah entri lainnya
            });
            //Datemask dd/mm/yyyy
            $('#datemask').inputmask('dd/mm/yyyy', {
                'placeholder': 'dd/mm/yyyy'
            })
            //Datemask2 mm/dd/yyyy
            $('#datemask2').inputmask('mm/dd/yyyy', {
                'placeholder': 'mm/dd/yyyy'
            })
            //Money Euro
            $('[data-mask]').inputmask()

            //Date picker
            $('#reservationdate').datetimepicker({
                format: 'L'
            });

            //Timepicker
            $('#timepicker').datetimepicker({
                format: 'LT'
            })
            //Timepicker2
            $('#timepicker2').datetimepicker({
                format: 'LT'
            })

            //Date and time picker
            $('#reservationdatetime').datetimepicker({
                icons: {
                    time: 'far fa-clock'
                }
            });
            $("input[data-bootstrap-switch]").each(function() {
                $(this).bootstrapSwitch('state', $(this).prop('checked'));
            })

            //Bootstrap Duallistbox
            $('.duallistbox').bootstrapDualListbox()

            //Date range picker
            $('#reservation').daterangepicker()
            //toast
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            $('.toastrDefaultSuccess').click(function() {
                toastr.success('Lorem ipsum dolor sit amet, consetetur sadipscing elitr.')
            });
            $('.toastrDefaultInfo').click(function() {
                toastr.info('Lorem ipsum dolor sit amet, consetetur sadipscing elitr.')
            });
            $('.toastrDefaultError').click(function() {
                toastr.error('Lorem ipsum dolor sit amet, consetetur sadipscing elitr.')
            });
            $('.toastrDefaultWarning').click(function() {
                toastr.warning('Lorem ipsum dolor sit amet, consetetur sadipscing elitr.')
            });

            $('.toastsDefaultDefault').click(function() {
                $(document).Toasts('create', {
                    title: 'Toast Title',
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                })
            });
            $('.toastsDefaultTopLeft').click(function() {
                $(document).Toasts('create', {
                    title: 'Toast Title',
                    position: 'topLeft',
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                })
            });
            $('.toastsDefaultBottomRight').click(function() {
                $(document).Toasts('create', {
                    title: 'Toast Title',
                    position: 'bottomRight',
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                })
            });
            $('.toastsDefaultBottomLeft').click(function() {
                $(document).Toasts('create', {
                    title: 'Toast Title',
                    position: 'bottomLeft',
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                })
            });
            $('.toastsDefaultAutohide').click(function() {
                $(document).Toasts('create', {
                    title: 'Toast Title',
                    autohide: true,
                    delay: 750,
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                })
            });
            $('.toastsDefaultNotFixed').click(function() {
                $(document).Toasts('create', {
                    title: 'Toast Title',
                    fixed: false,
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                })
            });
            $('.toastsDefaultFull').click(function() {
                $(document).Toasts('create', {
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                    title: 'Toast Title',
                    subtitle: 'Subtitle',
                    icon: 'fas fa-envelope fa-lg',
                })
            });
            $('.toastsDefaultFullImage').click(function() {
                $(document).Toasts('create', {
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.',
                    title: 'Toast Title',
                    subtitle: 'Subtitle',
                    image: '../../dist/img/user3-128x128.jpg',
                    imageAlt: 'User Picture',
                })
            });
            $('.toastsDefaultSuccess').click(function() {
                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Toast Title',
                    subtitle: 'Subtitle',
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                })
            });
            $('.toastsDefaultInfo').click(function() {
                $(document).Toasts('create', {
                    class: 'bg-info',
                    title: 'Toast Title',
                    subtitle: 'Subtitle',
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                })
            });
            $('.toastsDefaultWarning').click(function() {
                $(document).Toasts('create', {
                    class: 'bg-warning',
                    title: 'Toast Title',
                    subtitle: 'Subtitle',
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                })
            });
            $('.toastsDefaultDanger').click(function() {
                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Toast Title',
                    subtitle: 'Subtitle',
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                })
            });
            $('.toastsDefaultMaroon').click(function() {
                $(document).Toasts('create', {
                    class: 'bg-maroon',
                    title: 'Toast Title',
                    subtitle: 'Subtitle',
                    body: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
                })
            });
            const darkToggle = document.getElementById('dark-mode-toggle');
            if (darkToggle) {
                darkToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('dark-mode');
                    document.querySelector('.navbar').classList.toggle('dark-mode');

                    const inputs = document.querySelectorAll('.form-control-navbar');
                    inputs.forEach(input => input.classList.toggle('dark-mode'));

                    const icon = this.querySelector('i');
                    if (document.body.classList.contains('dark-mode')) {
                        if (icon) {
                            icon.classList.remove('fa-moon');
                            icon.classList.add('fa-sun');
                        }
                        this.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
                    } else {
                        if (icon) {
                            icon.classList.remove('fa-sun');
                            icon.classList.add('fa-moon');
                        }
                        this.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
                    }
                });
            }

        });
    </script>
    <!-- Bootstrap Switch -->
    <script src="{{ asset('lte/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('lte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- InputMask -->
    <script src="{{ asset('lte/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/inputmask/jquery.inputmask.min.js') }}"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="{{ asset('lte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    <!-- Bootstrap4 Duallistbox -->
    <script src="{{ asset('lte/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js') }}"></script>
    <!-- date-range-picker -->
    <script src="{{ asset('lte/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('lte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('lte/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('lte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    {{-- <script src="{{ asset('/sw.js') }}"></script> --}}
    {{-- <script>
        if ("serviceWorker" in navigator) {
            // Register a service worker hosted at the root of the
            // site using the default scope.
            navigator.serviceWorker.register("/sw.js").then(
                (registration) => {
                    console.log("Service worker registration succeeded:", registration);
                },
                (error) => {
                    console.error(`Service worker registration failed: ${error}`);
                },
            );
        } else {
            console.error("Service workers are not supported.");
        }
    </script> --}}
    <script src="{{ asset('js/helper.js') }}"></script>

    @stack('scripts')
</body>

</html>
