<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Betarigs Manager</title>

    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

    <style>
      body {
        margin-top:30px;
        text-rendering: optimizeLegibility;
        speak: none;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        font-smoothing: antialiased;
      }

      .container {
        width:90% !important;
      }

      h3 {
        margin-bottom:15px;
      }
    </style>

    <!--[if lt IE 9]>
      <script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="//oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    @yield('content')

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>

    @yield('scripts')
  </body>
</html>