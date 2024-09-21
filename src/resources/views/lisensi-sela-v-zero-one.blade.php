<!doctype html>
<html data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable" data-theme="interactive"
  data-theme-colors="default" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

  <meta charset="utf-8" />
  <title>Lisensi | {{ env('APP_NAME') }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sela CMS adalah content management sistem yang ringan, cepat, dan modern" />
  <meta name="author" content="Sevenlight.ID" />
  <!-- App favicon -->
  <link href="{{ url('themes/backend') }}/assets/images/favicon.ico" rel="shortcut icon">

  <!-- Layout config Js -->
  <script src="{{ url('themes/backend') }}/assets/js/layout.js"></script>
  <!-- Bootstrap Css -->
  <link type="text/css" href="{{ url('themes/backend') }}/assets/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Icons Css -->
  <link type="text/css" href="{{ url('themes/backend') }}/assets/css/icons.min.css" rel="stylesheet" />
  <!-- App Css-->
  <link type="text/css" href="{{ url('themes/backend') }}/assets/css/app.min.css" rel="stylesheet" />
  <!-- custom Css-->
  <link type="text/css" href="{{ url('themes/backend') }}/assets/css/custom.min.css" rel="stylesheet" />

  <style>
    body::before {
      opacity: 100% !important;
    }

    body {
      background: url('{{ url('assets/defaults/gambar/lisensi.png') }}');
      background-size: cover;
      background-position: top;
      background-repeat: no-repeat;
    }

    .opacity-custom {
      opacity: {{ config('sela-lisensi.opacity') }};
    }
  </style>

</head>

<body>

  <div class="auth-page-wrapper d-flex justify-content-center align-items-end vh-100 opacity-custom bg-white py-5">

    <div class="auth-page-content overflow-hidden p-0">
      <div class="container">

        <div class="row justify-content-center">
          <div class="col-xl-7 col-lg-8">

            <div class="text-center">
              <h3 class="text-uppercase fw-bold text-dark">{{ $pesan }}</h3>
              <p class="text-dark mb-0">
                Silahkan hubungi <a href="https://sevenlight.id">Sevenlight.ID</a> untuk info lebih lanjut.
              </p>
            </div>

          </div>
        </div>

      </div>
    </div>

  </div>

</body>

</html>
