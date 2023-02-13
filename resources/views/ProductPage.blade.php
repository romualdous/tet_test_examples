<?php $language = session('Language'); ?>
<?php
$getJson = file_get_contents("https://translation-tool.ccstudio.lv/VoiceApp/texts_export_backend.php");
$getTranslate = json_decode($getJson, true);
?>

    <!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon"/>
    <meta charset="utf-8">
    <title>Product Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" media="(min-width: 0px)" type="text/css" href="{{ asset('assets/css/pr_mobile.css') }}">
    <link rel="stylesheet" media="(min-width: 768px)" type="text/css" href="{{ asset('assets/css/pr_tablet.css') }}">
    <link rel="stylesheet" media="(min-width: 1024)" type="text/css" href="{{ asset('assets/css/pr_desktop.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@200;300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<header class="header wrapper">
    <nav class="header__nav">
        <ul class="header__nav--left">
            <li>
                <a href="https://dia.lv/">
                    <img src="{{URL::asset('assets/images/logo.svg')}}" alt="dia logo">
                </a>
            </li>
        </ul>
        <ul class="header__nav--right">
            <li>
                @if($language == 'en')
                    <a class="anchor active" href="{{ route('ProductPage', ['language' => "en"]) }}">EN</a>
                @else
                    <a class="anchor" href="{{ route('ProductPage', ['language' => "en"]) }}">EN</a>
                @endif
            </li>
            <li>
                @if($language == 'lv')
                    <a class="anchor active" href="{{ route('ProductPage', ['language' => "lv"]) }}">LV</a>
                @else
                    <a class="anchor" href="{{ route('ProductPage', ['language' => "lv"]) }}">LV</a>
                @endif
            </li>
            <li>
                @if($language == 'ru')
                    <a class="anchor active" href="{{ route('ProductPage', ['language' => "ru"]) }}">RU</a>
                @else
                    <a class="anchor" href="{{ route('ProductPage', ['language' => "ru"]) }}">RU</a>
                @endif
            </li>
        </ul>
    </nav>
</header>

<section class="download wrapper">
    <diV class="download-box">
        <div class="download__title">
            <h1><?php echo $getTranslate['web_profile']['download'][$language]; ?></h1>
        </div>
        <div class="download__buttons">
            <ul class="buttons">
                <li>
                    <a class="buttons--ios" href="https://apps.apple.com/us/app/dia-lv/id1575868095">
                        <img src="{{URL::asset('assets/images/store-ios.svg')}}" alt="topic">
                    </a>
                </li>
                <li>
                    <a class="buttons--android" href="https://play.google.com/store/apps/details?id=lv.dia.app">
                        <img src="{{URL::asset('assets/images/store-android.svg')}}" alt="topic">
                    </a>
                </li>
            </ul>
        </div>
    </diV>
</section>

<footer class="footer wrapper">
    <div class="footer-col">
        <div class="footer__title">
            <h3><?php echo $getTranslate['web_profile']['contacts'][$language]; ?></h3>
        </div>
        <div class="footer__list">
            <ul class="list">
                <li>
                    <a href="mailto:Contact@dia.lv">Contact@dia.lv</a>
                </li>
                <li>
                    <span>371 29909996</span>
                </li>
            </ul>
        </div>
    </div>
    <div class="footer-col">
        <div class="footer__title">
            <h3><?php echo $getTranslate['web_profile']['UsefulL'][$language]; ?></h3>
        </div>
        <div class="footer__list footer__list--two">
            <ul class="list">
                <li>
                    <a href="https://dia.lv/#Home">
                        <?php echo $getTranslate['web_profile']['home'][$language]; ?>
                    </a>
                </li>
                <li>
                    <a href="https://dia.lv/<?php echo $language; ?>#About">
                        <?php echo $getTranslate['web_profile']['tutorial'][$language]; ?>
                    </a>
                </li>
                <li>
                    <a href="https://dia.lv/privacy-policy-<?php echo $language; ?>">
                        <?php echo $getTranslate['web_profile']['privacy'][$language]; ?>
                    </a>
                </li>
            </ul>
            <ul class="list">
                <li>
                    <a href="https://dia.lv/<?php echo $language; ?>#Listeners">
                        <?php echo $getTranslate['web_profile']['listeners'][$language]; ?>
                    </a>
                </li>
                <li>
                    <a href="https://dia.lv/<?php echo $language; ?>#Team">
                        <?php echo $getTranslate['web_profile']['team'][$language]; ?>
                    </a>
                </li>
                <li>
                    <a href="https://dia.lv/eula-<?php echo $language; ?>">
                        <?php echo $getTranslate['web_profile']['eula'][$language]; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="footer-col">
        <div class="footer__title">
            <h3><?php echo $getTranslate['web_profile']['checkus'][$language]; ?></h3>
        </div>
        <div class="footer__buttons">
            <ul class="buttons">
                <li>
                    <a href="https://www.facebook.com/app.dia.lv">
                        <img src="{{URL::asset('assets/images/icon-facebook.svg')}}" alt="facebook">
                    </a>
                </li>
                <li>
                    <a href="https://www.instagram.com/app.dia.lv/">
                        <img src="{{URL::asset('assets/images/icon-instagram.svg')}}" alt="instagram">
                    </a>
                </li>
            </ul>
        </div>
    </div>
</footer>
</body>
</html>
