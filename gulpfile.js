//Project settings
const potFile = "mediakanjers";
const projectName = "mediakanjers";

//Gulp requirements
const { src, dest, series, parallel, watch } = require("gulp");
const sass = require("gulp-sass")(require('sass'));
const uglifycss = require("gulp-uglifycss");

let autoprefixer;
import('gulp-autoprefixer').then((module) => {
	autoprefixer = module;
});

const imagemin = require("gulp-imagemin");
const cache = require("gulp-cache");
const uglifyEs = require("gulp-uglify-es").default;
const babel = require("gulp-babel");
const livereload = require("gulp-livereload");
const wpPot = require('gulp-wp-pot');
const sassLint = require('gulp-sass-lint');
const fontello = require('gulp-fontello');
const concat = require('gulp-concat');
const clean = require('gulp-clean');

//Path variables
const srcPath = "web/assets";
const distPath = "web/dist";

//Path array
const paths = {
	scss: [`${srcPath}/scss/**/*.scss`],
	js: [`${srcPath}/js/**/*.js`],
	img: [`${srcPath}/images/**`],
	font: [`${srcPath}/fonts/**`],
	jsvendor: [`${srcPath}/js/vendor/*.js`],
};

// CSS task
async function css() {
	const autoprefixer = await import('gulp-autoprefixer');

	return src(paths.scss)
		.pipe(sassLint({
			options: {
				formatter: 'stylish'
			},
			configFile: 'sass-lint.yml'
		}))
		.pipe(sassLint.format())
		.pipe(sassLint.failOnError())
		// Sass comprimeert zelf; uglifycss verwijderde de verplichte spaties rond
		// + en - in clamp()/calc(), waardoor vloeiende lettergroottes ongeldig werden.
		.pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
		.pipe(
			autoprefixer.default({
				overrideBrowserslist: ["last 2 versions"],
				cascade: false,
			})
		)
		.pipe(dest(`${distPath}/css/`))
		.pipe(livereload());
}


// Javascript task
function js() {
	return src(paths.js)
		// .pipe(
		// 	babel({
		// 		presets: ["@babel/preset-env"],
		// 	})
		// )
		.pipe(uglifyEs())
		.pipe(concat('scripts.min.js'))
		.pipe(dest(`${distPath}/scripts/`))
		.pipe(livereload());
}

// Javascript vendor files
function jsVendor() {
	return src(paths.jsvendor)
		.pipe(dest(`${distPath}/scripts/vendor/`))
		.pipe(livereload());
}

// Font task
function font() {
	return src(paths.font)
		.pipe(dest(`${distPath}/font/`))
		.pipe(livereload());		
}

// Image task
function img() {
	return src(paths.img)
		.pipe(
			cache(
				imagemin({
					interlaced: true,
				})
			)
		)
		.pipe(dest(`${distPath}/images/`))
		.pipe(livereload());
}

// WordPress internationalization
function wpPotTask() {
	return src('web/**/*.php')
		.pipe(wpPot({
			domain: "TEXTDOMAIN",  // Looks for TEXTDOMAIN global PHP var.
			package: projectName
		}))
		.pipe(dest(langPath + "/" + potFile + ".pot"))
		.pipe(livereload());
}

// Clean task
function cleantask() {
	return src(`${distPath}/*`, { read: false, allowEmpty: true })
		.pipe(clean());
}


// theme.json to scss styling
const gulp = require('gulp');
const fs = require('fs');
const file = require('gulp-file');
const path = require('path');

function extractThemeJson(done) {

	console.log('📦 extractThemeJson triggered by file change');

  const themeJsonPath = 'web/theme.json'; // Pad naar theme.json (pas aan als nodig)
  const outputPath = 'web/assets/scss/theme/'; // Pad waar je _theme-vars.scss naartoe schrijft

  if (!fs.existsSync(themeJsonPath)) {
    console.error('theme.json niet gevonden!');
    done();
    return;
  }

  const json = JSON.parse(fs.readFileSync(themeJsonPath));

  let output = `// ⚠️ Automatisch gegenereerd uit theme.json – niet handmatig aanpassen!\n`;

  // Kleuren
  if (json.settings?.color?.palette) {
    json.settings.color.palette.forEach(color => {
      output += `$color-${color.slug}: ${color.color};\n`;
    });
    output += '\n';
  }

  // Layout (contentSize, wideSize) toevoegen als SCSS-variabelen
	if (json.settings?.layout) {
	Object.entries(json.settings.layout).forEach(([key, value]) => {
		// CamelCase naar kebab-case omzetten, bv. contentSize -> content-size
		const varName = `$layout-${key.replace(/([A-Z])/g, '-$1').toLowerCase()}`;
		output += `${varName}: ${value};\n`;
	});
	output += '\n';
	}

	if (json.settings?.typography?.fontSizes) {
	json.settings.typography.fontSizes.forEach(size => {
		output += `$font-size-${size.slug}: ${size.size};\n`;

		if (size.lineHeight) {
		output += `$line-height-${size.slug}: ${size.lineHeight};\n`;
		}

		if (size.fontWeight) {
		output += `$font-weight-${size.slug}: ${size.fontWeight};\n`;
		}

		if (size.fontColor) {
		output += `$font-color-${size.slug}: ${size.fontColor};\n`;
		}

		output += '\n';
	});
	}



  // Fonts
  if (json.settings?.typography?.fontFamilies) {
    json.settings.typography.fontFamilies.forEach(font => {
      output += `$font-family-${font.slug}: ${font.fontFamily};\n`;
    });
    output += '\n';
  }

	// Spacing presets (sectie-ruimtes etc.)
	if (json.settings?.spacing?.spacingSizes) {
		json.settings.spacing.spacingSizes.forEach(size => {
			output += `$spacing-${size.slug}: ${size.size};\n`;
		});
		output += '\n';
	}

	// Custom tokens (settings.custom) — geneste objecten worden platgeslagen:
	// custom.radius.card -> $custom-radius-card
	if (json.settings?.custom) {
		const flatten = (obj, prefix) => {
			Object.entries(obj).forEach(([key, value]) => {
				const name = `${prefix}-${key.replace(/([A-Z])/g, '-$1').toLowerCase()}`;
				if (value && typeof value === 'object') {
					flatten(value, name);
				} else {
					output += `$${name}: ${value};\n`;
				}
			});
		};
		flatten(json.settings.custom, 'custom');
		output += '\n';
	}

	// Headings (h1 t/m h6) uit styles.elements
  if (json.styles?.elements) {
    ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].forEach(heading => {
      const h = json.styles.elements[heading];
      if (h && h.typography) {
        const t = h.typography;
        if (t.fontSize) output += `$${heading}-font-size: ${t.fontSize};\n`;
        if (t.fontWeight) output += `$${heading}-font-weight: ${t.fontWeight};\n`;
        if (t.lineHeight) output += `$${heading}-line-height: ${t.lineHeight};\n`;
        if (t.color) output += `$${heading}-color: ${t.color};\n`;
      }
      output += '\n';
    });
  }

  return file('_theme-vars.scss', output, { src: true })
    .pipe(gulp.dest(outputPath));
}

// Theme.json watcher zit in watchFiles()
function watchFiles() {
	livereload.listen();
	watch(paths.scss, css);
	watch(paths.js, js);
	watch(paths.img, img);
	watch(paths.font, font);
	watch(paths.jsvendor, jsVendor);
	watch('web/theme.json', extractThemeJson); // toegevoegd
	watch(["**"], livereload); 
}

// Eenmalige build zonder watcher (handig voor deploy/CI)
exports.build = series(
	cleantask,
	extractThemeJson,
	parallel(css, js, font, img, jsVendor)
);

exports.default = series(
	cleantask,
	extractThemeJson,
	parallel(css, js, font, img, jsVendor),
	watchFiles
);