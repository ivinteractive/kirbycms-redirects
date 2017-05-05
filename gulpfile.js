var gulp = require('gulp'),
	sass = require('gulp-sass'),
	csso = require('gulp-csso'),
	autoprefixer = require('gulp-autoprefixer'),
	shorthand = require('gulp-shorthand'),
	notify = require('gulp-notify'),
	plumber = require('gulp-plumber'),
	watch = require('gulp-watch'),
	runSequence = require('run-sequence');

var paths = {
	scss: './resources/scss',
	css: './assets/css'
};

gulp.task('view', function() {

	gulp.src(paths.scss + '/redirecty.scss')
    .pipe(plumber({errorHandler: notify.onError("SASS error: <%= error.message %> in <%= error.filename %>")}))
    .pipe(sass({
        outputStyle: 'expanded',
    }))
    .pipe(csso())
    .pipe(autoprefixer({
        browsers: ['> 1%'],
        cascade: false
    }))
    .pipe(gulp.dest(paths.css));

});

gulp.task('widget', function() {

	gulp.src(paths.scss + '/widget.scss')
    .pipe(plumber({errorHandler: notify.onError("SASS error: <%= error.message %> in <%= error.filename %>")}))
    .pipe(sass({
        outputStyle: 'expanded',
    }))
    .pipe(csso())
    .pipe(autoprefixer({
        browsers: ['> 1%'],
        cascade: false
    }))
    .pipe(gulp.dest(paths.css));

});

gulp.task('sass', function() {
	runSequence('view', 'widget');
});

gulp.task('default', function() {
	gulp.watch(paths.scss + '/*.scss', ['view', 'widget']);
});