<?php $user = session('data'); ?>
    <?php $filterdata = strval($user); ?>
    <?php echo $filterdata?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>CCStudio - FRIENDLY IT COMPANY THAT CAN HELP YOU</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('ccshomepage-develop/src/styles/calculator.css') }}">
</head>

<body>

<header class="with-width mobile-only">
    <a href="/"><img class="logo" src="{{ asset('ccshomepage-develop/src/assets/logo.svg') }}" alt="CCStudio"/></a>
    <div class="spacer"></div>
    <a class="small-only mobile-menu">
        <img src="{{ asset('ccshomepage-develop/src/assets/menu.svg') }}" alt="Open Menu" />
    </a>
</header>
<div class="top-divider"></div>
<main class="with-width">
    <div class="questions-row">
        <div class="questions">
            <form>
                <div class="form-steps">
                </div>
                <div class="buttons">
                    <button class="timeline-btn small-only">
                        <span>
                            Timeline
                            <img src="{{ asset('ccshomepage-develop/src/assets/right-arrow.svg') }}" alt=">">
                        </span>
                    </button>
                    <button class="next-question">
                        <span>
                            Next question
                            <img src="{{ asset('ccshomepage-develop/src/assets/right-arrow.svg') }}" alt=">">
                        </span>
                    </button>
                    <button type="submit" class="submit">
                        <span>
                            <span class="submit-text">Submit</span>
                            <span class="submit-arrow">
                                <img src="{{ asset('ccshomepage-develop/src/assets/right-arrow.svg') }}" alt=">">
                            </span>
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <div class="steps">
            <div class="close-steps small-only">
                <img src="{{ asset('ccshomepage-develop/src/assets/close.svg') }}" alt="Close">
            </div>
            <ul></ul>
            <div class="total" id="total_coast">Total</div>
        </div>

        <div class="timeline"></div>
    </div>
    <div class="processes">
        <div class="close-processes small-only">
            <img src="{{ asset('ccshomepage-develop/src/assets/close.svg') }}" alt="Close">
        </div>
        <div class="process-steps">
            <div class="process-step planning-step">
                <img src="{{ asset('ccshomepage-develop/src/assets/flow-chart.svg') }}" class="planning" alt="Planning" />
                <p>Planning</p>
                <p class="process-step-date"></p>
            </div>
            <div class="process-ste design-step">
                <img src="{{ asset('ccshomepage-develop/src/assets/web-design.svg') }}" class="design" alt="Design" />
                <p>Design</p>
                <p class="process-step-date"></p>
            </div>
            <div class="process-step development-step">
                <img src="{{ asset('ccshomepage-develop/src/assets/system.svg') }}" class="development" alt="Development" />
                <p>Development</p>
                <p class="process-step-date"></p>
            </div>
            <div class="process-step testing-step">
                <img src="{{ asset('ccshomepage-develop/src/assets/checklist.svg') }}" class="testing" alt="Testing" />
                <p>Testing</p>
                <p class="process-step-date"></p>
            </div>
            <div class="process-step launch-step">
                <img src="{{ asset('ccshomepage-develop/src/assets/rocket.svg') }}" class="launch" alt="Launch" />
                <p>Launch</p>
                <p class="process-step-date"></p>
            </div>
        </div>
        <div class="empty-process-step"></div>
    </div>
    <div class="backdrop small-only"></div>
</main>

<footer>
    <div class="with-width">
        <div class="our-links">
            <p>© CatsyCat Studio Ltd. 2020, All rights are reserved.</p>
            <div class="links">
                <a class="popup-link" data-popup-selector="#privacy-popup">Privacy Policy</a>
                <a class="popup-link" data-popup-selector="#terms-popup" data-p>Terms of Use</a>
            </div>
        </div>
        <div class="spacer"></div>
        <div class="social-link">
            <a href="https://www.linkedin.com/company/catsy-cat-studio/"><img src="{{ asset('ccshomepage-develop/src/assets/linkedin-hover.svg') }}" alt="LinkedIn"></a>
            <a href="https://www.facebook.com/CatsyCatStudio"><img src="{{ asset('ccshomepage-develop/src/assets/facebook-hover.svg') }}" alt="Facebook"></a>
        </div>
        <p class="small-only">© CatsyCat Studio Ltd. 2019,</p>
        <p class="small-only">All rights are reserved.</p>
    </div>
</footer>

<template id="choose-template">
    <div class="form-step">
        <div class="header">
            <h2></h2>
        </div>
        <div class="option-list">

        </div>
    </div>
</template>

<template id="option-template">
    <div class="option-item">
        <input />
        <label></label>
        <img alt="" src="" />
        <div class="description"></div>
    </div>
</template>

<template id="open-estimate-template">
    <img src="{{ asset('ccshomepage-develop/src/assets/calculator/estimate.svg') }}" alt="estimate" class="estimate small-only">
</template>

<input type="hidden" id="revisit_array" value='<?php echo $user; ?>' >
<script src="{{ asset('ccshomepage-develop/src/js/calculator.js') }}"></script>

</body>
</html>
