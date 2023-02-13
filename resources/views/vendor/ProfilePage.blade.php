<?php $user = session('User'); ?>
<?php $language = session('Language'); ?>
<?php
$getJson = file_get_contents("https://translation-tool.ccstudio.lv/VoiceApp/texts_export_backend.php");
$getTranslate = json_decode($getJson, true);
use Carbon\Carbon;
$age = Carbon::parse($user->date_of_birth)->diff(Carbon::now())->y;
$devServer = 'https://voice-api.fkn.cool/storage/';
$imageFolder =  'assets/images/topic-';
$extension = '.svg';
?>

<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo strip_tags($user->full_name, '<br>'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" media="(min-width: 0px)" type="text/css" href="{{ asset('assets/css/mobile.css') }}">
    <link rel="stylesheet" media="(min-width: 768px)" type="text/css" href="{{ asset('assets/css/tablet.css') }}">
    <link rel="stylesheet" media="(min-width: 1024)" type="text/css" href="{{ asset('assets/css/desktop.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@200;300;400;500;600&display=swap" rel="stylesheet">

    <meta property="og:url" content="<?php echo $user->profile_url; ?>" />
    <meta property="og:title" content="<?php echo strip_tags($user->full_name, '<br>'); ?>" />
    <meta property="og:description" content="<?php $getStrip = strip_tags($user->bio, '<br>');$description = str_replace(["\r\n", "\r", "\n"], "<br/>", $getStrip);
                                    echo $description;
                                    ?>" />
    <meta property="og:image" content="<?php echo $devServer.$user->photo; ?>" />
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="<?php echo strip_tags($user->full_name, '<br>'); ?>" />
    <meta name="twitter:description" content="<?php $getStrip = strip_tags($user->bio, '<br>');$description = str_replace(["\r\n", "\r", "\n"], "<br/>", $getStrip);
                                    echo $description;
                                    ?>" />
    <meta name="twitter:image" content="<?php echo $devServer.$user->photo; ?>" />
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
                    <a class="anchor active" href="en">En</a>
                @else
                    <a class="anchor" href="en">En</a>
                @endif
            </li>
            <li>
                @if($language == 'lv')
                    <a class="anchor active" href="lv">Lv</a>
                @else
                    <a class="anchor" href="lv">Lv</a>
                @endif
            </li>
            <li>
                @if($language == 'ru')
                    <a class="anchor active" href="ru">Ru</a>
                @else
                    <a class="anchor" href="ru">Ru</a>
                @endif
            </li>
        </ul>
    </nav>
</header>

<section class="review wrapper">
    <div class="review__title">
        <h1><?php echo strip_tags($user->full_name, '<br>'); ?>, <?php echo $age; ?></h1>
    </div>
    <div class="review__box">
        <figure>
				<span class="profile">
					<span class="cropper">
						<img src="{{URL::asset($devServer.$user->photo)}}" alt="profile">
                    </span>
				</span>
            <p class="text">
                <?php
                    $getStrip = strip_tags($user->bio, '<br>');
                    $description = str_replace(["\r\n", "\r", "\n"], "<br/>", $getStrip);
                    echo $description;
                ?>
            </p>
        </figure>
    </div>
</section>

<section class="rating wrapper">
    <div class="rating__box">
        <label class="rating__box--left">
            <span class="label"><?php echo $getTranslate['web_profile']['rating'][$language]; ?></span>
        </label>
        <div class="rating__box--right">
            <span class="overall"><?php echo $user->rating; ?></span>
            <figure class="starline-box">
                <?php $ratingWidth = $user->rating * 20; ?>
                <ul class="starline starline--fill" style="width: <?php echo $ratingWidth; ?>%;">
                    <li><img src="{{URL::asset('assets/images/star-fill.svg')}}" alt="star"></li>
                    <li><img src="{{URL::asset('assets/images/star-fill.svg')}}" alt="star"></li>
                    <li><img src="{{URL::asset('assets/images/star-fill.svg')}}" alt="star"></li>
                    <li><img src="{{URL::asset('assets/images/star-fill.svg')}}" alt="star"></li>
                    <li><img src="{{URL::asset('assets/images/star-fill.svg')}}" alt="star"></li>
                </ul>
                <ul class="starline starline--outline">
                    <li><img src="{{URL::asset('assets/images/star-outline.svg')}}" alt="star"></li>
                    <li><img src="{{URL::asset('assets/images/star-outline.svg')}}" alt="star"></li>
                    <li><img src="{{URL::asset('assets/images/star-outline.svg')}}" alt="star"></li>
                    <li><img src="{{URL::asset('assets/images/star-outline.svg')}}" alt="star"></li>
                    <li><img src="{{URL::asset('assets/images/star-outline.svg')}}" alt="star"></li>
                </ul>
            </figure>
        </div>
    </div>
    <div class="request__box">
        <a href="https://voice-admin.fkn.cool/openprofile/<?php echo $user->id; ?>"><?php echo $getTranslate['web_profile']['request'][$language]; ?></a>
    </div>
</section>

<section class="topics wrapper">
    <div class="topics__title">
        <h1><?php echo $getTranslate['web_profile']['topics'][$language]; ?></h1>
    </div>
    <div class="topics__box">
        @foreach($user['topics'] as $row)
            <div class="box">
                <div class="content">
                    @if(file_exists($imageFolder.$row->title.$extension))
                        <img src="{{URL::asset($imageFolder.$row->title.$extension)}}" alt="topic">
                    @else
                        <img src="{{URL::asset($imageFolder.'Default.png')}}" alt="topic">
                    @endif
                    <h3><?php echo $getTranslate['web_profile']['topics_'.$row->title][$language] ?? $row->title.'_'.$language; ?></h3>
                </div>
            </div>
        @endforeach
    </div>
</section>

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
                    <a href="https://tilda.cc/page/?pageid=20471809&previewmode=yes">
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
