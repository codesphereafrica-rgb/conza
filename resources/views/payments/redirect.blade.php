<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Redirection vers EasyPay</title>
</head>
<body>
    <p>Redirection vers EasyPay...</p>
    <form id="easypay-form" action="{{ $paymentUrl }}" method="POST">
        <input type="hidden" name="auth_token" value="{{ $authToken }}">
        <input type="hidden" name="postBackURL" value="{{ $postBackUrl }}">
    </form>
    <script>document.getElementById('easypay-form').submit();</script>
</body>
</html>