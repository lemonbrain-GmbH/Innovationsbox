const mix = require('laravel-mix')
const fs = require("fs")
const path = require("path")

const getAllFiles = function (dirPath, arrayOfFiles) {
  files = fs.readdirSync(dirPath)
  arrayOfFiles = arrayOfFiles || []

  files.forEach(function (file) {
    if (fs.statSync(dirPath + "/" + file).isDirectory()) {
      arrayOfFiles = getAllFiles(dirPath + "/" + file, arrayOfFiles)
    } else {
      arrayOfFiles.push({
        fullpath: path.join(dirPath, "/", file),
      })
    }
  })

  return arrayOfFiles
}


// this one splits the code into "main.js", "manifest.js" and "vendor.js" that all have to be required (in the footer)
// so it won't bundle the dependencies into block-js files
// if extract: activated - main.js, manifest.js (vendor.js) ist taking care for namespace definitions. So you need to include them too.
mix.extract();

mix.webpackConfig({
  stats: {
    children: true,
  },
  watchOptions: {
    ignored: /node_modules/,
    aggregateTimeout: 300,
    poll: 1000,
    followSymlinks: false
  },
  module: {
    rules: [
      {
        test: /\.tsx?$/,
        loader: "ts-loader",
        exclude: /node_modules/
      }
    ]
  },
  resolve: {
    extensions: ["*", ".js", ".jsx", ".vue", ".ts", ".tsx"],
    alias: {
      '~@helpers': path.resolve(__dirname, 'resources/css/helper'),
      '~@global': path.resolve(__dirname, 'resources/css/global'),
    }
  }
});

// Component library → CMS block output mapping
// Output filenames must be view.css / view.js (Concrete CMS convention in BlockViewTemplate.php)
const blockComponents = [
  { src: 'public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.scss', out: 'blocks/autonav/templates/innovationsbox/view.css' },
  { src: 'public/packages/inno_frontend/components/organisms/navigation-drawer/navigation-drawer.ts',   out: 'blocks/autonav/templates/innovationsbox/view.js' },
  { src: 'public/packages/inno_frontend/components/organisms/hero-image/hero-image.scss',               out: 'blocks/hero_image/templates/innovationsbox/view.css' },
  { src: 'public/packages/inno_frontend/components/organisms/content-block/content-block.scss',          out: 'blocks/content/templates/innovationsbox/view.css' },
  { src: 'public/packages/inno_frontend/components/molecules/feature-button/feature-button.scss',        out: 'blocks/feature_link/templates/innovationsbox/view.css' },
  { src: 'public/packages/inno_frontend/components/organisms/page-list-grid/page-list-grid.scss',        out: 'blocks/page_list/templates/innovationsbox/view.css' },
  { src: 'public/packages/inno_frontend/components/organisms/innovation-teaser/innovation-teaser.scss', out: 'blocks/innovation_teaser/view.css' },
  { src: 'public/packages/inno_frontend/components/organisms/innovation-teaser/innovation-teaser.ts',   out: 'blocks/innovation_teaser/view.js' },
  { src: 'public/packages/inno_frontend/components/organisms/update-teaser/update-teaser.scss',         out: 'blocks/update_teaser/view.css' },
  { src: 'public/packages/inno_frontend/components/organisms/update-teaser/update-teaser.ts',           out: 'blocks/update_teaser/view.js' },
  { src: 'public/packages/inno_frontend/components/molecules/info-panel/info-panel.scss',             out: 'blocks/info_panel/view.css' },
]

blockComponents.forEach(({ src, out }) => {
  if (src.endsWith('.ts')) {
    mix.ts(src, out)
  } else {
    mix.sass(src, out)
  }
})

// singlePage relevant js gets built into themes/innovationsbox/js/single_pages directory
const singlePageJsFiles = getAllFiles('resources/js/single_pages');
singlePageJsFiles.forEach(f => {
  if (!f.fullpath.endsWith('.ts')) return;
  mix.ts(f.fullpath, 'themes/innovationsbox/js/single_pages/')
})

// singlePage relevant css gets built into themes/innovationsbox/css/single_pages directory
const singlePageCssFiles = getAllFiles('resources/css/single_pages');
singlePageCssFiles.forEach(f => {
  if (!f.fullpath.endsWith('.scss')) return;
  mix.sass(f.fullpath, 'themes/innovationsbox/css/single_pages/')
})

// Storybook combined bundle (all component SCSS + TS in one file)
mix.sass('public/packages/inno_frontend/components/_storybook/components.scss', 'storybook/components.css')
mix.ts('public/packages/inno_frontend/components/_storybook/components.ts', 'storybook/components.js')

// general main js
mix
  .setPublicPath('public/packages/inno_frontend/')
  .ts('resources/js/main.ts', 'public/packages/inno_frontend/themes/innovationsbox/js/main.js')
  .sass('resources/css/main.scss', 'public/packages/inno_frontend/themes/innovationsbox/css/skins/default.css')
  .browserSync({ proxy: 'http://' + process.env.PROJECT_NAME + '.local:' + process.env.APP_PORT, open: false })
  .disableSuccessNotifications();
