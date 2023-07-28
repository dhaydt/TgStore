<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>{{$general_setting->site_title}}</title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="all,follow">
  <link rel="manifest" href="{{url('manifest.json')}}">
  @if(!config('database.connections.saleprosaas_landlord'))
  <link rel="icon" type="image/png" href="{{url('logo', $general_setting->site_logo)}}" />
  <!-- Bootstrap CSS-->
  <link rel="stylesheet" href="<?php echo asset('vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">
  <!-- login stylesheet-->
  <link rel="stylesheet" href="<?php echo asset('css/auth.css') ?>" id="theme-stylesheet" type="text/css">
  <!-- Google fonts - Roboto -->
  <link rel="preload" href="https://fonts.googleapis.com/css?family=Nunito:400,500,700" as="style"
    onload="this.onload=null;this.rel='stylesheet'">
  <noscript>
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,500,700" rel="stylesheet">
  </noscript>
  @else
  <link rel="icon" type="image/png" href="{{url('../../logo', $general_setting->site_logo)}}" />
  <!-- Bootstrap CSS-->
  <link rel="stylesheet" href="<?php echo asset('../../vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">
  <!-- login stylesheet-->
  <link rel="stylesheet" href="<?php echo asset('../../css/auth.css') ?>" id="theme-stylesheet" type="text/css">
  <!-- Google fonts - Roboto -->
  <link rel="preload" href="https://fonts.googleapis.com/css?family=Nunito:400,500,700" as="style"
    onload="this.onload=null;this.rel='stylesheet'">
  <noscript>
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,500,700" rel="stylesheet">
  </noscript>
  @endif
  <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

  <link rel="stylesheet" href="{{ asset('asset_login/css/style.css') }}">
</head>

<body>
  <section class="ftco-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 text-center mb-5">
          <h2 class="heading-section">{{$general_setting->site_title ?? 'Invalid Web Name'}}</h2>
        </div>
      </div>
      <div class="row justify-content-center">
        <div class="col-md-12 col-lg-10">
          <div class="wrap d-md-flex">
            <div class="img" style="background-image: url({{ asset('asset_login/images/bg-1.jpg') }});">
            </div>
            <div class="login-wrap p-4 p-md-5">
              <div class="d-flex">
                <div class="w-100">
                  <h3 class="mb-4">LOGIN</h3>
                </div>
                @if(session()->has('delete_message'))
                <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close"
                    data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{
                  session()->get('delete_message') }}</div>
                @endif
                <!--<div class="w-100">
                    <p class="social-media d-flex justify-content-end">
                      <a href="#" class="social-icon d-flex align-items-center justify-content-center"><span class="fa fa-facebook"></span></a>
                      <a href="#" class="social-icon d-flex align-items-center justify-content-center"><span class="fa fa-twitter"></span></a>
                    </p>
                  </div> -->
              </div>
              <form method="POST" action="{{ route('login') }}" id="login-form" class="signin-form">
                @csrf
                <div class="form-group mb-3">
                  <label class="label" for="name">Username</label>
                  <input id="login-username" type="text" name="name" required class="form-control" value="">
                  @if(session()->has('error'))
                  <p>
                    <strong>{{ session()->get('error') }}</strong>
                  </p>
                  @endif
                </div>
                <div class="form-group mb-3">
                  <label class="label" for="password">Password</label>
                  <input id="login-password" type="password" name="password" required class="form-control" value="">
                  @if(session()->has('error'))
                  <p>
                    <strong>{{ session()->get('error') }}</strong>
                  </p>
                  @endif
                </div>
                <div class="form-group">
                  <button type="submit" class="form-control btn btn-primary rounded submit px-3">LOGIN</button>
                </div>
              </form>
              <!--<div class="form-group d-md-flex">
                    <div class="w-50 text-left">
                      <label class="checkbox-wrap checkbox-primary mb-0">Remember Me
                      <input type="checkbox" checked>
                      <span class="checkmark"></span>
                      </label>
                    </div>
                    <div class="w-50 text-md-right">
                      <a href="#">Forgot Password</a>
                    </div>
                  </div>
                </form>
                <p class="text-center">Not a member? <a data-toggle="tab" href="#signup">Sign Up</a></p>
              </div> -->
            </div>
          </div>
        </div>
      </div>
      <div class="copyrights text-center">
        <p>{{trans('file.Developed By')}} <span class="external">{{$general_setting->developed_by}}</span></p>
      </div>
      </section>
      <script src="{{ asset('asset_login/js/jquery.min.js') }}"></script>
      <script src="{{ asset('asset_login/js/popper.js') }}"></script>
      <script src="{{ asset('asset_login/js/bootstrap.min.js') }}"></script>
      <script src="{{ asset('asset_login/js/main.js') }}"></script>
</body>

</html>
@if(!config('database.connections.saleprosaas_landlord'))
<script type="text/javascript" src="<?php echo asset('vendor/jquery/jquery.min.js') ?>"></script>
@else
<script type="text/javascript" src="<?php echo asset('../../vendor/jquery/jquery.min.js') ?>"></script>
@endif
<script>
  //switch theme code
    var theme = <?php echo json_encode($theme); ?>;
    if(theme == 'dark') {
        $('body').addClass('dark-mode');
        $('#switch-theme i').addClass('dripicons-brightness-low');
    }
    else {
        $('body').removeClass('dark-mode');
        $('#switch-theme i').addClass('dripicons-brightness-max');
    }
    $('.admin-btn').on('click', function(){
        $("input[name='name']").focus().val('admin');
        $("input[name='password']").focus().val('admin');
    });
    
    if ('serviceWorker' in navigator ) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/salepro/service-worker.js').then(function(registration) {
                // Registration was successful
                console.log('ServiceWorker registration successful with scope: ', registration.scope);
            }, function(err) {
                // registration failed :(
                console.log('ServiceWorker registration failed: ', err);
            });
        });
    }

    $('.admin-btn').on('click', function(){
        $("input[name='name']").focus().val('admin');
        $("input[name='password']").focus().val('admin');
    });

  $('.staff-btn').on('click', function(){
      $("input[name='name']").focus().val('staff');
      $("input[name='password']").focus().val('staff');
  });

  $('.customer-btn').on('click', function(){
      $("input[name='name']").focus().val('shakalaka');
      $("input[name='password']").focus().val('shakalaka');
  });
  // ------------------------------------------------------- //
    // Material Inputs
    // ------------------------------------------------------ //

    var materialInputs = $('input.input-material');

    // activate labels for prefilled values
    materialInputs.filter(function() { return $(this).val() !== ""; }).siblings('.label-material').addClass('active');

    // move label on focus
    materialInputs.on('focus', function () {
        $(this).siblings('.label-material').addClass('active');
    });

    // remove/keep label on blur
    materialInputs.on('blur', function () {
        $(this).siblings('.label-material').removeClass('active');

        if ($(this).val() !== '') {
            $(this).siblings('.label-material').addClass('active');
        } else {
            $(this).siblings('.label-material').removeClass('active');
        }
    });
</script>