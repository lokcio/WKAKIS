'use strict';
var gulp = require('gulp'),
    shell = require('gulp-shell'),
    sass = require('gulp-sass'),
    webpack = require('webpack-stream'),
    uglify = require('gulp-uglify'),
    imagemin = require('gulp-imagemin'),
    cache = require('gulp-cache'),
    runSequence = require('run-sequence'),
    phpcs = require('gulp-phpcs'),
    del = require('del'),
    size = require('gulp-size'),
    jshint = require('gulp-jshint');

gulp.task('cleancache', function() {
  return cache.clearAll(done);
});

gulp.task('clean:dist', function() {
  return del.sync('dist/*');
});

// PHP code documentation
gulp.task('clean:docs/phpdoc', function() {
  return del.sync('docs/phpdoc');
});
gulp.task('phpdocumentator', shell.task(['vendor/bin/phpdoc -d . -t docs/phpdoc -i vendor/,node_modules/,server.php,dist/,wkakis.config.php']));
gulp.task('phpdoc', function() {
  runSequence('clean:docs/phpdoc', 'phpdocumentator');
});

gulp.task('move', function() {
  return gulp.src(['**/*.+(php|json|txt|ini)'], {base: './src'})
  .pipe(gulp.dest('./dist'));
});

// Sass (SCSS) compiling
gulp.task('sass', function() {
  return gulp.src('src/public/sass/**/*.scss')
  .pipe(size({
    title: 'before sass compiling & compression:',
    showFiles: true
  }))
  .pipe(cache(sass({outputStyle: 'compressed'}).on('error', sass.logError)))
  .pipe(size({
    title: 'after sass compiling & compression:',
    showFiles: true
  }))
  .pipe(gulp.dest('dist/public/css'));
});

// webpack bundler
gulp.task('webpack', function() {
  return gulp.src('src/public/js/**/*.js')
    .pipe(size())
    .pipe(cache(jshint('.jshintrc')))
    .pipe(jshint.reporter('jshint-stylish'))
    .pipe(cache(webpack(require('./webpack.config.js'))))
    // .pipe(uglify())
    .pipe(size())
    .pipe(gulp.dest('dist/public/js'));
});

gulp.task('images', function() {
  return gulp.src('src/public/imgs/**/*.+(png|jpg|gif|svg)')
    .pipe(size({showFiles: true}))
    .pipe(cache(imagemin()))
    .pipe(size({showFiles: true}))
    .pipe(gulp.dest('dist/public/imgs'));
});

gulp.task('php', function () {
  return gulp.src(['src/**/*.php', '!src/vendor/**/*.*'])
    .pipe(cache(phpcs({
      bin: 'vendor/bin/phpcs',
      standard: 'PSR2',
      warningSeverity: 0
    })))
    .pipe(phpcs.reporter('log'));
});



// gulp.task('jsdoc', function () {
//   var gjsduck = new GJSDuck(["--out", "docs/jsdoc"]);
//   gulp.src("src/public/js/**.js")
//   .pipe(gjsduck.doc());
// });


gulp.task('build', function() {
  runSequence('clean:dist', ['php', 'sass', 'webpack', 'images'], 'move');
});
