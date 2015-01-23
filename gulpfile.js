var gulp = require('gulp');
var clean = require('gulp-clean');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var sass = require('gulp-sass');
var minifyCSS = require('gulp-minify-css');
var prefix = require('gulp-autoprefixer');
var rename = require('gulp-rename');

var publicPath = 'Resources/public';
var srcPath = publicPath + '/src';
var distPath = publicPath + '/dist';

var isBuildMode = false;

function handleError(err) {
  console.error(err.toString());
  this.emit('end');
}

gulp.task('scripts', function() {
  var stream = gulp.src([
    srcPath+'/BlueImp/js/vendor/jquery.ui.widget.js',
    srcPath+'/BlueImp/js/jquery.fileupload.js',
    srcPath+'/FileUploader.js',
    srcPath+'/FileUploaderQueue.js'
  ]);

  if (isBuildMode) {
    stream.pipe(uglify());
  }

  stream
    .pipe(concat('main.js'))
    .pipe(gulp.dest(distPath));

  return stream;
});

gulp.task('styles', function() {
  var stream = gulp.src([
    srcPath+'/**/*.scss'
  ])
    .pipe(sass())
    .pipe(prefix(["last 3 versions", "> 1%"], { cascade: true }));

  if (isBuildMode) {
    stream.pipe(minifyCSS());
  }

  stream
    .pipe(gulp.dest(distPath));

  return stream;
});

gulp.task('clean', function() {
  return gulp.src([distPath], { read: false }).pipe(clean());
});

gulp.task('build', ['clean'], function() {
  isBuildMode = true;
  gulp.start('do_build');
});

gulp.task('do_build', ['default'], function() {
  isBuildMode = false;
});

gulp.task('default', ['clean'], function() {
  gulp.start('styles');
  gulp.start('scripts');
});

gulp.task('watch', ['default'], function() {
  gulp.watch([srcPath+'/**/*.js'], ['scripts']);
  gulp.watch([srcPath+'/**/*.scss'], ['styles']);
});
