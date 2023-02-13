const { src, dest } = require('gulp');
const cleanCSS = require('gulp-clean-css');
const minify = require('gulp-minify');
var replace = require('gulp-replace');

function getGaScript() {
    return '<script async src="https://www.googletagmanager.com/gtag/js?id=UA-161958526-1"></script>' +
            "<script>" +
            "window.dataLayer = window.dataLayer || [];" +
            "function gtag(){dataLayer.push(arguments);}" +
            "gtag('js', new Date());" +
            "gtag('config', 'UA-161958526-1');" +
            "</script>";
}

function build(cb) {
    //styles
    src("src/styles/*.css")
        .pipe(cleanCSS({compatibility: 'ie8'}))
        .pipe(dest("output/styles"));
    //js
    src("src/js/*.js")
        .pipe(minify())
        .pipe(dest("output/js"));

    //static
    src("src/*.{png,jpg,svg,json,ico,txt,xml}")
        .pipe(dest("output/"));
    //static
    src("src/assets/**")
        .pipe(dest("output/assets"));

    src("src/data/*")
        .pipe(dest("output/data"));

    //google analytic
    let replacement = getGaScript()+"</head>";
    src("src/*.html")
        .pipe(replace("</head>", replacement))
        .pipe(replace("main.js", "main-min.js"))
        .pipe(replace("calculator.js", "calculator-min.js"))
        .pipe(dest("output/"));

    cb();
}

exports.build = build;
