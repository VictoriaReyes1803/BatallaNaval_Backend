<!DOCTYPE html>
<html>
<head>
    <title>Activación de cuenta</title>
</head>
<body>
    
<div class="container">
<img src="{{ $message->embed(public_path() . '/images/43976.png') }}" alt="LOGO" style="width: 100px;
                height: 100px;
                filter: brightness(1.1);" />
<br/> 
<h1>Bienvenido a Batalla Naval!</h2>


<p>¡Gracias por registrarte! Antes de comenzar, necesitamos que verifiques tu dirección de correo electrónico.</p>
<br/>
Tu correo electrónico registrado es {{$user['email']}} , Por favor haz clic en el enlace de abajo para verificar tu correo electrónico y activar tu cuenta

<br/>
<a href="{{$url}}">Verificar correo electrónico</a>
</body>
</html>
